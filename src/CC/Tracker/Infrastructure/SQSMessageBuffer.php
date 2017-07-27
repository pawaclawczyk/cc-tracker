<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use Amp\Deferred;
use CC\Tracker\Model\Message;

class SQSMessageBuffer
{
    private $buffer = [];

    public function add(Deferred $deferred, Message $message): void
    {
        $this->buffer[] = [$deferred, $message];
    }

    public function count(): int
    {
        return \count($this->buffer);
    }

    public function get(): array
    {
        $out = [];

        $count = \min($this->count(), 10);

        for ($i = 0; $i < $count; ++$i) {
            $out[] = \array_shift($this->buffer);
        }

        return $out;
    }
}
