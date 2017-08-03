<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use CC\Shared\Model\MessageQueue\Queue;

final class FindOrCreateQueue
{
    private $findQueue;
    private $createQueue;

    public function __construct(FindQueue $findQueue, CreateQueue $createQueue)
    {
        $this->findQueue = $findQueue;
        $this->createQueue = $createQueue;
    }

    public function findOrCreate(Queue $queue): string
    {
        return ("" !== $queueUrl = $this->findQueue->find($queue))
            ? $queueUrl
            : $this->createQueue->create($queue);
    }
}
