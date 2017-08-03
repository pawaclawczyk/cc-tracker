<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Aws\Sqs\SqsClient;

final class CreateQueue
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function create(string $queueName): string
    {
        $created = $this->client->createQueue([
            Params::QUEUE_NAME => $queueName,
        ]);

        $queueUrl = $created->get(Params::QUEUE_URL);

        return (string) $queueUrl ?? "";
    }
}
