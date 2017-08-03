<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Queue;

final class CreateQueue
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function create(Queue $queue): string
    {
        $created = $this->client->createQueue([
            Params::QUEUE_NAME => $queue,
        ]);

        $queueUrl = $created->get(Params::QUEUE_URL);

        return (string) $queueUrl ?? "";
    }
}
