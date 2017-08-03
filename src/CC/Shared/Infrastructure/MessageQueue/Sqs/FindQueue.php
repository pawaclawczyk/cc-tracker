<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Queue;

final class FindQueue
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function find(Queue $queue): string
    {
        try {
            $queueUrl = (string) $this
                ->client
                ->getQueueUrl([Params::QUEUE_NAME => $queue])
                ->get(Params::QUEUE_URL);
        } catch (SqsException $exception) {
            if ("AWS.SimpleQueueService.NonExistentQueue" !== $exception->getAwsErrorCode()) {
                throw $exception;
            }

            $queueUrl = "";
        }

        return $queueUrl;
    }
}
