<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Aws\Sqs\SqsClient;

final class PurgeQueue
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function purge(string $queueUrl): bool
    {
        $this->client->purgeQueue([
            Params::QUEUE_URL => $queueUrl,
        ]);

        return true;
    }
}
