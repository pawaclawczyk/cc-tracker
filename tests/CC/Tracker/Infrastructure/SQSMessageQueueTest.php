<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure;

use function Amp\Promise\all;
use function Amp\Promise\wait;
use Aws\Result;
use CC\Tracker\Infrastructure\SQSMessageQueue;
use CC\Tracker\Model\Message;
use PHPUnit\Framework\TestCase;

class SQSMessageQueueTest extends TestCase
{
    /** @test */
    public function it_sends_message()
    {
        $mq = new SQSMessageQueue(
            [
                "region"  => "eu-west-1",
                "version" => "latest",
            ],
            "https://sqs.eu-west-1.amazonaws.com/864947613734/cc-tracker"
        );

        $result = $mq->send(Message::fromString("Hello World"));

        /** @var Result $resolved */
        $resolved = wait($result);

        $this->assertEquals(200, $resolved->get('@metadata')['statusCode']);
    }

    /** @test */
    public function it_sends_full_batch()
    {
        $mq = new SQSMessageQueue(
            [
                "region"  => "eu-west-1",
                "version" => "latest",
            ],
            "https://sqs.eu-west-1.amazonaws.com/864947613734/cc-tracker"
        );

        $promises = [];

        for ($i = 0; $i < 10; ++$i) {
            $promises[] = $mq->send(Message::fromString("Hello World"));
        }

        /** @var Result $resolved */
        $resolved = wait(all($promises));

        foreach ($resolved as $result) {
            $this->assertEquals(200, $result->get('@metadata')['statusCode']);
        }
    }
}
