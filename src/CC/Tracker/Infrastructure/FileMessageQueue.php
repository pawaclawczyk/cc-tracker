<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use Amp\Promise;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use Amp\File;

final class FileMessageQueue implements MessageQueue
{
    private $handle;

    public function send(Message $message)
    {
        $this->open()->onResolve(function ($error, File\Handle $handle) use ($message) {
            $handle->write((string) $message);
        });
    }

    private function open(): Promise
    {
        if (!$this->handle) {
            $this->handle = File\open(__DIR__ . '/../../../../var/log/requests.log', 'a+');
        }

        return $this->handle;
    }
}
