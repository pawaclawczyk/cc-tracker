<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\Status\Time;

use Carbon\Carbon;
use Ds\Map;

final class AverageTimeCollector
{
    private $aggregated;
    private $measurements;

    public function __construct(Measurements $measurements)
    {
        $this->aggregated = new Map();
        $this->measurements = $measurements;
    }

    public function collect()
    {
        $data = $this->measurements->cut();

        while (0 < $data->count()) {
            /** @var Duration $measurement */
            $measurement = $data->pop();

            $time = (string) Carbon::createFromTimestamp($measurement->timestamp())->second(0);

            /** @var Average $average */
            $average = $this->aggregated->get($time, new Average(0, 0.0));

            $this->aggregated->put($time, $average->includeMeasurement($measurement));
        }

        return $this->aggregated;
    }
}
