<?php

declare(strict_types=1);

namespace CC\Tracker\Model;

use Amp\Promise;
use React\Promise\PromiseInterface;

interface MessageQueue
{
    /**
     * @param Message $message
     *
     * @return bool|Promise|PromiseInterface
     */
    public function send(Message $message);
}
