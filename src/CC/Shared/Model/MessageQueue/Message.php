<?php

declare(strict_types=1);

namespace CC\Shared\Model\MessageQueue;

use Ds\Map;

final class Message
{
    private $message;
    private $metadata;

    public function __construct(string $message)
    {
        $this->message = $message;
        $this->metadata = new Map();
    }

    public function withMetadata(Map $metadata): Message
    {
        $message = new self($this->message);
        $message->metadata = $this->metadata()->merge($metadata);

        return $message;
    }

    public function metadata(): Map
    {
        return $this->metadata;
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
