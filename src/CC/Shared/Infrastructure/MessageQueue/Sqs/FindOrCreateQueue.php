<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

final class FindOrCreateQueue
{
    private $findQueue;
    private $createQueue;

    public function __construct(FindQueue $findQueue, CreateQueue $createQueue)
    {
        $this->findQueue = $findQueue;
        $this->createQueue = $createQueue;
    }

    public function findOrCreate(string $queueName): string
    {
        return ("" !== $queueUrl = $this->findQueue->find($queueName))
            ? $queueUrl
            : $this->createQueue->create($queueName);
    }
}
