<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\Status\Time;

use Ds\Queue;

final class Measurements implements \Countable
{
    private $queue;

    public function __construct()
    {
        $this->queue = new Queue();
    }

    public function push(Duration $measurement): Measurements
    {
        $this->queue->push($measurement);

        return $this;
    }

    public function cut(): Queue
    {
        $cut = $this->queue;
        $this->queue = new Queue();

        return $cut;
    }

    public function count(): int
    {
        return \count($this->queue);
    }
}
