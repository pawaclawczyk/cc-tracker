<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
use Amp\Promise;
use Aws\Result;
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
        return call(function (Queue $queue) {
            /** @var Result $result */
            $result = yield adapt($this->client->createQueueAsync([
                Params::QUEUE_NAME => $queue,
            ]));

            return $result->get(Params::QUEUE_URL);
        }, $queue);
    }
}
