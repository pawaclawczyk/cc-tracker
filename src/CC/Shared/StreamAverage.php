<?php

declare(strict_types=1);

namespace CC\Shared;

final class StreamAverage
{
    private $average;
    private $count;

    public static function zero()
    {
        return new self(.0, 0);
    }

    public function __invoke(float $value): float
    {
        return $this->push($value);
    }

    public function push(float $value): float
    {
        $this->average = ($this->count * $this->average + $value) / ($this->count + 1);
        $this->count = $this->count + 1;

        return $value;
    }

    public function __toString(): string
    {
        return "Average: {$this->average}, Count: {$this->count}";
    }

    private function __construct(float $average, int $count)
    {
        $this->average = $average;
        $this->count = $count;
    }
}
