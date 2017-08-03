<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Queue;

final class DeleteQueue
{
    private $client;
    private $findQueue;

    public function __construct(SqsClient $client, FindQueue $findQueue)
    {
        $this->client = $client;
        $this->findQueue = $findQueue;
    }

    public function delete(Queue $queue): bool
    {
        if ("" === $queueUrl = $this->findQueue->find($queue)) {
            return true;
        }

        $this->client->deleteQueue([
            Params::QUEUE_URL => $queueUrl,
        ]);

        return true;
    }
}
