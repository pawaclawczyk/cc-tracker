<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Message;

final class AcknowledgeMessage
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function ack(Message $message): Promise
    {
        $asyncRequest = $this->client->deleteMessageAsync([
            Params::QUEUE_URL      => $message->metadata()[Params::QUEUE_URL],
            Params::RECEIPT_HANDLE => $message->metadata()[Params::RECEIPT_HANDLE],
        ]);

        $deferred = new Deferred();

        Loop::defer(function () use ($deferred, $asyncRequest) {
            $asyncRequest->wait();

            $deferred->resolve(true);
        });

        return $deferred->promise();
    }
}
