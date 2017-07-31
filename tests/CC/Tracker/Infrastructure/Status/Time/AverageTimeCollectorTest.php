<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure\Status\Time;

use Carbon\Carbon;
use CC\Tracker\Infrastructure\Status\Time\Average;
use CC\Tracker\Infrastructure\Status\Time\AverageTimeCollector;
use CC\Tracker\Infrastructure\Status\Time\Duration;
use CC\Tracker\Infrastructure\Status\Time\Measurements;
use PHPUnit\Framework\TestCase;

class AverageTimeCollectorTest extends TestCase
{
    /** @test */
    public function it_aggregates_durations_into_one_minute_blocks()
    {
        $queue = new Measurements();

        $collector = new AverageTimeCollector($queue);

        $queue
            ->push(new Duration(Carbon::createFromTime(10, 5, 15)->timestamp, 0.5))
            ->push(new Duration(Carbon::createFromTime(10, 6, 15)->timestamp, 0.5))
            ->push(new Duration(Carbon::createFromTime(10, 6, 30)->timestamp, 0.5))
            ->push(new Duration(Carbon::createFromTime(10, 7, 15)->timestamp, 0.5))
            ->push(new Duration(Carbon::createFromTime(10, 7, 30)->timestamp, 0.5))
            ->push(new Duration(Carbon::createFromTime(10, 7, 45)->timestamp, 0.5))
        ;

        $aggregated = $collector->collect();

        $this->assertTrue($aggregated->hasKey((string) Carbon::createFromTime(10, 5, 0)));
        $this->assertTrue($aggregated->hasKey((string) Carbon::createFromTime(10, 6, 0)));
        $this->assertTrue($aggregated->hasKey((string) Carbon::createFromTime(10, 7, 0)));

        $this->assertCount(3, $aggregated->keys());
    }

    /** @test */
    public function it_calculates_average_duration()
    {
        $queue = new Measurements();

        $collector = new AverageTimeCollector($queue);

        $queue
            ->push(new Duration(Carbon::createFromTime(10, 7, 15)->timestamp, 0.5))
            ->push(new Duration(Carbon::createFromTime(10, 7, 30)->timestamp, 1.0))
            ->push(new Duration(Carbon::createFromTime(10, 7, 45)->timestamp, 1.5))
        ;

        $aggregated = $collector->collect();

        $this->assertEquals(new Average(3, 1.0), $aggregated->get((string) Carbon::createFromTime(10, 7, 0)));
    }

    /** @test */
    public function it_cuts_measurements_queue()
    {
        $queue = new Measurements();

        $collector = new AverageTimeCollector($queue);

        $queue
            ->push(new Duration(Carbon::createFromTime(10, 7, 15)->timestamp, 0.5))
            ->push(new Duration(Carbon::createFromTime(10, 7, 30)->timestamp, 1.0))
            ->push(new Duration(Carbon::createFromTime(10, 7, 45)->timestamp, 1.5))
        ;

        $this->assertCount(3, $queue);

        $collector->collect();

        $this->assertCount(0, $queue);
    }
}
