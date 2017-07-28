<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure;

use function Amp\Promise\all;
use function Amp\Promise\wait;
use Aws\Result;
use CC\Tracker\Infrastructure\SQSMessageQueue;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use PHPUnit\Framework\TestCase;

class SQSMessageQueueTest extends TestCase
{
    private const AMAZON_SQS_ENDPOINT = "https://sqs.eu-west-1.amazonaws.com";
    private const ELASTICMQ_ENDPOINT = "http://elasticmq:9324";
    private const QUEUE_NAME = "cc-tracker";

    /**
     * @param string $endpoint
     * @param string $queueName
     *
     * @test
     * @dataProvider mqConfiguration
     */
    public function it_sends_message(string $endpoint, string $queueName)
    {
        $mq = $this->createMqClient($endpoint, $queueName);

        $result = $mq->send(Message::fromString("Hello World"));

        /** @var Result $resolved */
        $resolved = wait($result);

        $this->assertEquals(200, $resolved->get('@metadata')['statusCode']);
    }

    /**
     * @param string $endpoint
     * @param string $queueName
     *
     * @test
     * @dataProvider mqConfiguration
     */
    public function it_sends_full_batch(string $endpoint, string $queueName)
    {
        $mq = $this->createMqClient($endpoint, $queueName);

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

    public function mqConfiguration(): array
    {
        return [
            [self::AMAZON_SQS_ENDPOINT, self::QUEUE_NAME],
            [self::ELASTICMQ_ENDPOINT, self::QUEUE_NAME],
        ];
    }

    private function createMqClient(string $endpoint, string $queueName): MessageQueue
    {
        return new SQSMessageQueue(
            [
                "region"   => "eu-west-1",
                "version"  => "latest",
                "endpoint" => $endpoint,
            ],
            $queueName
        );
    }
}
