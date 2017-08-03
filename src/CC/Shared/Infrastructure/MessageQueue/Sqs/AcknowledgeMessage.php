<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
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
        return call(function (Message $message) {
            yield adapt($this->client->deleteMessageAsync([
                Params::QUEUE_URL      => $message->metadata()[Params::QUEUE_URL],
                Params::RECEIPT_HANDLE => $message->metadata()[Params::RECEIPT_HANDLE],
            ]));

            return true;
        }, $message);
    }
}
