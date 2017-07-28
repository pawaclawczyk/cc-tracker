<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\MessageQueue;

use CC\Tracker\Infrastructure\RabbitMessageQueue;
use CC\Tracker\Infrastructure\SQSMessageQueue;
use CC\Tracker\Model\MessageQueue;

final class ClientFactory
{
    private $queueName;
    private $client;
    private $configs;

    public function __construct(array $config)
    {
        [
            "queue_name" => $this->queueName,
            "client"     => $this->client,
            "configs"    => $this->configs
        ] = $config;
    }

    public function default(): MessageQueue
    {
        return $this->custom($this->client, $this->queueName, $this->configs[$this->client]);
    }

    public function custom(string $client, string $queueName = "", array $config = []): MessageQueue
    {
        if ([] === $config) {
            $config = $this->configs[$client];
        }

        if ("" === $queueName) {
            $queueName = $this->queueName;
        }

        if (MessageQueueClients::RABBIT_MQ === $client) {
            return $this->rabbitMQ($queueName, $config);
        }

        return $this->sqs($queueName, $config);
    }

    private function rabbitMQ(string $queueName, array $config): MessageQueue
    {
        return new RabbitMessageQueue($config, $queueName);
    }

    private function sqs(string $queueName, array $config): MessageQueue
    {
        return new SQSMessageQueue($config, $queueName);
    }
}
