<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
use Amp\Promise;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Queue;

final class DeleteQueue
{
    private $client;
    private $findQueue;

    public function __construct(SqsClient $client, FindQueue $findQueue)
    {
        $this->client = $client;
        $this->findQueue = $findQueue;
    }

    public function delete(Queue $queue): Promise
    {
        return call(function () use ($queue) {
            if ("" === $queueUrl = yield $this->findQueue->find($queue)) {
                return true;
            }

            $this->client->deleteQueue([
                Params::QUEUE_URL => $queueUrl,
            ]);

            return true;
        });
    }
}
