<?php

declare(strict_types=1);

namespace CC\Tracker\Configuration;

use CC\Tracker\Infrastructure\MessageQueue\MessageQueueClients;

final class Configuration
{
    public static function load(): array
    {
        return
            [
                "message_queue" => [
                    "queue_name" => \getenv("CC_TRACKER_MQ_QUEUE_NAME") ?: "cc-tracker",
                    "client"     => \getenv("CC_TRACKER_MQ_CLIENT") ?: MessageQueueClients::RABBIT_MQ,
                    "configs"    => [
                        MessageQueueClients::RABBIT_MQ  => [
                            "host"     => \getenv("CC_TRACKER_MQ_ENDPOINT") ?: "rabbit",
                            "user"     => \getenv("CC_TRACKER_MQ_USER") ?: "rabbit",
                            "password" => \getenv("CC_TRACKER_MQ_PASSWORD") ?: "rabbit.123",
                        ],
                        MessageQueueClients::AMAZON_SQS => [
                            "endpoint" => \getenv("CC_TRACKER_MQ_ENDPOINT") ?: "https://sqs.eu-west-1.amazonaws.com",
                            "region"   => "eu-west-1",
                            "version"  => "latest",
                        ],
                        MessageQueueClients::ELASTIC_MQ => [
                            "endpoint" => \getenv("CC_TRACKER_MQ_ENDPOINT") ?: "http://elasticmq:9324",
                            "region"   => "eu-west-1",
                            "version"  => "latest",
                        ],
                    ],
                ],
                "queue" => [
                    "name"       => \getenv("CC_TRACKER_MQ_NAME") ?: "tracker",
                    "type"       => \getenv("CC_TRACKER_MQ_TYPE") ?: "rabbit",
                    "connection" => [
                        "user"     => \getenv("CC_TRACKER_MQ_USER") ?: "rabbit",
                        "password" => \getenv("CC_TRACKER_MQ_PASS") ?: "rabbit.123",
                        "host"     => \getenv("CC_TRACKER_MQ_HOST") ?: "rabbit",
                    ],
                ],
                "aerys" => [
                    "options" => [
                        "maxConnections"   => 2048,
                        "connectionsPerIP" => 2048,
                        "user"             => "app",
                    ],
                    "host" => [
                        "address" => "*",
                        "port"    => \getenv("CC_TRACKER_HOST_PORT") ?: "9000",
                    ],
                ],
                "pixel" => __DIR__ . "/../../../../var/static/pixel.gif",
            ];
    }
}
