<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\MessageQueue;

final class MessageQueueClients
{
    const RABBIT_MQ = "RabbitMQ";
    const AMAZON_SQS = "Amazon SQS";
    const ELASTIC_MQ = "ElasticMQ";
}
