<?php

declare(strict_types=1);

namespace CC\Shared\Model\MessageQueue;

use Amp\Promise;

interface Consumer
{
    public function read(Queue $queue): Promise;
}
