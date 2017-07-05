<?php

declare(strict_types=1);

namespace CC\Tracker\Model;

interface MessageQueue
{
    public function send(Message $message);
}
