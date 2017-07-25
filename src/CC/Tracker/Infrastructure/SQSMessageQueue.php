<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use function Amp\call;
use Aws\Sqs\SqsClient;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use GuzzleHttp\Promise\Promise as GuzzlePromise;

class SQSMessageQueue implements MessageQueue
{
    private $client;
    private $queueUrl;

    public function __construct(array $connectionConfiguration, string $queueUrl)
    {
        $this->client                  = new SqsClient($connectionConfiguration);
        $this->queueUrl                = $queueUrl;
    }

    /**
     * @inheritdoc
     */
    public function send(Message $message)
    {
        $guzzlePromise = $this->client->sendMessageAsync([
            "QueueUrl"    => $this->queueUrl,
            "MessageBody" => (string) $message,
        ]);

        return call(function (GuzzlePromise $promise) {
            return $promise->wait(true);
        }, $guzzlePromise);
    }
}
