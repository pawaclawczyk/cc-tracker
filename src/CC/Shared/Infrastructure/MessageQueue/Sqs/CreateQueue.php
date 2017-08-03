<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Queue;

final class CreateQueue
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function create(Queue $queue): Promise
    {
        $asyncRequest = $created = $this->client->createQueueAsync([
            Params::QUEUE_NAME => $queue,
        ]);

        $deferred = new Deferred();

        Loop::defer(function () use ($deferred, $asyncRequest) {
            $response = $asyncRequest->wait(true);

            $queueUrl = $response->get(Params::QUEUE_URL);

            $deferred->resolve($queueUrl);
        });

        return $deferred->promise();
    }
}
