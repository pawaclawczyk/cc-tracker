<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
use Amp\Promise;
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
        return call(function (Queue $queue) {
            return ("" !== $queueUrl = yield $this->findQueue->find($queue))
                ? $queueUrl
                : yield $this->createQueue->create($queue);
        }, $queue);
    }
}
