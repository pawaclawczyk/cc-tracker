<?php

declare(strict_types=1);

namespace CC\Shared\Model\MessageQueue;

final class Queue
{
    private $name;

    public function __construct(string $queue)
    {
        $this->name = $queue;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
