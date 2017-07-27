<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use Amp\Deferred;
use Amp\Loop;
use Aws\Sqs\SqsClient;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;

class SQSMessageQueue implements MessageQueue
{
    private $client;
    private $queueUrl;
    private $buffer;
    private $repeatWatcher;

    public function __construct(array $connectionConfiguration, string $queueUrl)
    {
        $this->client   = $client   = new SqsClient($connectionConfiguration);
        $this->queueUrl = $queueUrl;
        $this->buffer   = $buffer   = new SQSMessageBuffer();

        $this->repeatWatcher = Loop::repeat(100, $this->sendAnyWatcher($this->queueUrl));
    }

    /**
     * {@inheritdoc}
     */
    public function send(Message $message)
    {
        $deferred = new Deferred();

        $this->buffer->add($deferred, $message);

        Loop::defer($this->sendBatchWatcher($this->queueUrl, $this->repeatWatcher));

        return $deferred->promise();
    }

    private function sendBatchWatcher(string $queueUrl, string $repeatWatcher)
    {
        $send = \Closure::fromCallable([$this, "sendMessages"]);

        return function () use ($send, $queueUrl, $repeatWatcher) {
            if (10 > $this->buffer->count()) {
                return;
            }

            Loop::disable($repeatWatcher);

            $messages = $this->buffer->get();

            $send($messages, $queueUrl);

            Loop::enable($repeatWatcher);
        };
    }

    private function sendAnyWatcher(string $queueUrl)
    {
        $send = \Closure::fromCallable([$this, "sendMessages"]);

        return function () use ($send, $queueUrl) {
            if (0 === $this->buffer->count()) {
                return;
            }

            $messages = $this->buffer->get();

            $send($messages, $queueUrl);
        };
    }

    private function sendMessages(array $messages, string $queueUrl)
    {
        $entries = [];
        foreach ($messages as $key => [, $message]) {
            $entries[] = [
                "Id"          => $key,
                "MessageBody" => (string) $message,
            ];
        }

        $res = $this->client->sendMessageBatch([
            "Entries"  => $entries,
            "QueueUrl" => $queueUrl,
        ]);

        foreach ($messages as [$deferred]) {
            $deferred->resolve($res);
        }
    }
}
