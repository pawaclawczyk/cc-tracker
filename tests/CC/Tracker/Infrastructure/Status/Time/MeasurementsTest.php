<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure\Status\Time;

use CC\Tracker\Infrastructure\Status\Time\Duration;
use CC\Tracker\Infrastructure\Status\Time\Measurements;
use Ds\Queue;
use PHPUnit\Framework\TestCase;

class MeasurementsTest extends TestCase
{
    /** @test */
    public function it_cuts_the_internal_queue()
    {
        $measurements = new Measurements();

        $measurements
            ->push(new Duration(1, 1.0))
            ->push(new Duration(2, 1.0))
            ->push(new Duration(3, 1.0))
        ;

        $this->assertCount(3, $measurements);

        $cut = $measurements->cut();

        $this->assertCount(0, $measurements);
        $this->assertInstanceOf(Queue::class, $cut);
        $this->assertCount(3, $cut);
    }
}
