<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Aws\Sqs\SqsClient;

final class DeleteQueue
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function delete(string $queueUrl): bool
    {
        $this->client->deleteQueue([
            Params::QUEUE_URL => $queueUrl,
        ]);

        return true;
    }
}
