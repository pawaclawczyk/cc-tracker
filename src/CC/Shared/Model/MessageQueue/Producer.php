<?php

declare(strict_types=1);

namespace CC\Shared\Model\MessageQueue;

use Amp\Promise;

interface Producer
{
    public function write(Queue $queue, Message $message): Promise;
}
