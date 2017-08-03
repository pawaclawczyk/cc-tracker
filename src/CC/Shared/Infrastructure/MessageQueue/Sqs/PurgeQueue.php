<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Queue;

final class PurgeQueue
{
    private $client;
    private $findQueue;

    public function __construct(SqsClient $client, FindQueue $findQueue)
    {
        $this->client = $client;
        $this->findQueue = $findQueue;
    }

    public function purge(Queue $queue): Promise
    {
        return call(function (Queue $queue) {
            if ("" === $queueUrl = yield $this->findQueue->find($queue)) {
                return true;
            }

            $asyncRequest = $this->client->purgeQueueAsync([
                Params::QUEUE_URL => $queueUrl,
            ]);

            $deferred = new Deferred();

            Loop::defer(function () use ($asyncRequest, $deferred) {
                $asyncRequest->wait(true);

                $deferred->resolve(true);
            });

            return $deferred->promise();
        }, $queue);
    }
}
