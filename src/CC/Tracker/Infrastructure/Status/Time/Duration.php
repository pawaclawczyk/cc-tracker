<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\Status\Time;

final class Duration
{
    private $timestamp;
    private $duration;

    public function __construct(int $timestamp, float $duration)
    {
        $this->timestamp = $timestamp;
        $this->duration = $duration;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public function duration(): float
    {
        return $this->duration;
    }
}
