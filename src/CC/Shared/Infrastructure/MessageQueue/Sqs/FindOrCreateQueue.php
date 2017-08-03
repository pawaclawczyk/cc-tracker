<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
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
        $findQueue = $this->findQueue;
        $createQueue = $this->createQueue;

        return call(function () use ($findQueue, $createQueue, $queue) {
            return ("" !== $queueUrl = yield $findQueue->find($queue))
                ? new Success($queueUrl)
                : $createQueue->create($queue);
        });
    }
}
