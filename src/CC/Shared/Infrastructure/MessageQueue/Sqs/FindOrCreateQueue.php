<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Amp\Promise;
use Amp\Success;
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

    public function findOrCreate(Queue $queue): Promise
    {
        return ("" !== $queueUrl = $this->findQueue->find($queue))
            ? new Success($queueUrl)
            : $this->createQueue->create($queue);
    }
}
