<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
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

    public function find(Queue $queue): Promise
    {
        $asyncRequest = $this->client->getQueueUrlAsync([Params::QUEUE_NAME => $queue]);

        $deferred = new Deferred();

        Loop::defer(function () use ($deferred, $asyncRequest) {
            try {
                $result = $asyncRequest->wait(true);
                $queueUrl = $result->get(Params::QUEUE_URL);
            } catch (SqsException $exception) {
                if ("AWS.SimpleQueueService.NonExistentQueue" !== $exception->getAwsErrorCode()) {
                    throw $exception;
                }

                $queueUrl = "";
            }

            $deferred->resolve($queueUrl);
        });

        return $deferred->promise();
    }
}
