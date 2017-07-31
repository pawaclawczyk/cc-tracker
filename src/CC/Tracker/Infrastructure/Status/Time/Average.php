<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\Status\Time;

final class Average implements \JsonSerializable
{
    private $count;
    private $average;

    public function __construct(int $count, float $average)
    {
        $this->count = $count;
        $this->average = $average;
    }

    public function includeMeasurement(Duration $measurement): Average
    {
        return new self(
            $this->count + 1,
            ($this->count * $this->average + $measurement->duration()) / ($this->count + 1)
        );
    }

    public function jsonSerialize()
    {
        return [
            "count"   => $this->count,
            "average" => $this->average,
        ];
    }
}
