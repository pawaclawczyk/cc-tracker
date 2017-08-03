<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;

final class FindQueue
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function find(string $queueName): string
    {
        try {
            $queue = (string) $this
                ->client
                ->getQueueUrl([Params::QUEUE_NAME => $queueName])
                ->get(Params::QUEUE_URL);
        } catch (SqsException $exception) {
            if ("AWS.SimpleQueueService.NonExistentQueue" !== $exception->getAwsErrorCode()) {
                throw $exception;
            }

            $queue = "";
        }

        return $queue;
    }
}
