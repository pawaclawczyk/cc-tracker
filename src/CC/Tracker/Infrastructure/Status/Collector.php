<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\Status;

use Amp\Loop;
use CC\Tracker\Infrastructure\Status\Time\AverageTimeCollector;

final class Collector
{
    private $timeCollector;

    public function __construct(AverageTimeCollector $timeCollector)
    {
        $this->timeCollector = $timeCollector;
    }

    public function __invoke(): array
    {
        return \array_merge(
            $this->memory(),
            $this->averageTime(),
            $this->loop()
        );
    }

    private function memory(): array
    {
        return [
            "memory" => [
                "usage" => \round(\memory_get_usage() / 1024, 2) . " KiB",
                "peak"  => \round(\memory_get_peak_usage() / 1024, 2) . " KiB",
            ],
        ];
    }

    private function averageTime(): array
    {
        return [
            "average_time" => $this->timeCollector->collect(),
        ];
    }

    private function loop(): array
    {
        $info = Loop::getInfo();
        $running = $info["running"];
        unset($info["running"]);

        return [
            "loop" => [
                "driver"   => \get_class(Loop::get()),
                "running"  => $running,
                "watchers" => $info,
            ],
        ];
    }
}
