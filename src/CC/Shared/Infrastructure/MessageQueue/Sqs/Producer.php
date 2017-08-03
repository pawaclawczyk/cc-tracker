<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Producer as ProducerContract;

final class Producer implements ProducerContract
{
    private $client;
    private $queueUrl;

    public function __construct(SqsClient $client, string $queueUrl)
    {
        $this->client = $client;
        $this->queueUrl = $queueUrl;
    }

    public function write(Message $message): Promise
    {
        $asyncRequest = $this->client->sendMessageAsync([
            Params::MESSAGE_BODY => (string) $message,
            Params::QUEUE_URL    => $this->queueUrl,
        ]);

        $deferred = new Deferred();

        Loop::defer(function () use ($deferred, $asyncRequest) {
            $result = $asyncRequest->wait(true);

            $deferred->resolve($result);
        });

        return $deferred->promise();
    }
}
