<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure\MessageQueue;

use CC\Tracker\Configuration\Configuration;
use CC\Tracker\Infrastructure\MessageQueue\ClientFactory;
use CC\Tracker\Infrastructure\MessageQueue\MessageQueueClients;
use CC\Tracker\Infrastructure\RabbitMessageQueue;
use CC\Tracker\Infrastructure\SQSMessageQueue;
use PHPUnit\Framework\TestCase;

class ClientFactoryTest extends TestCase
{
    /**
     * @param array  $configurations
     * @param string $expectedInstanceOf
     *
     * @test
     * @dataProvider configurations
     */
    public function it_creates_default_client(array $configurations, string $expectedInstanceOf)
    {
        $this->markTestSkipped("Refactoring time!.");

        $factory = new ClientFactory($configurations);

        $mq = $factory->default();

        $this->assertInstanceOf($expectedInstanceOf, $mq);
    }

    public function configurations(): array
    {
        $configuration = Configuration::load();
        $mqConfiguration = $configuration["message_queue"];

        return [
            [
                $this->configurationWithClient($mqConfiguration, MessageQueueClients::RABBIT_MQ),
                RabbitMessageQueue::class,
            ],
            [
                $this->configurationWithClient($mqConfiguration, MessageQueueClients::AMAZON_SQS),
                SQSMessageQueue::class,
            ],
            [
                $this->configurationWithClient($mqConfiguration, MessageQueueClients::ELASTIC_MQ),
                SQSMessageQueue::class,
            ],
        ];
    }

    private function configurationWithClient(array $configuration, string $client): array
    {
        $configuration["client"] = $client;

        return $configuration;
    }
}
