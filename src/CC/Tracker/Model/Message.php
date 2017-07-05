<?php

declare(strict_types=1);

namespace CC\Tracker\Model;

final class Message
{
    private $message;

    public static function fromString(string $message): Message
    {
        return new self($message);
    }

    public function __toString(): string
    {
        return $this->message;
    }

    private function __construct(string $message)
    {
        $this->message = $message;
    }
}
