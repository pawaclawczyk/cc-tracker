<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
use Amp\Promise;
use Aws\Result;
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
        return call(function (Queue $queue) {
            try {
                /** @var Result $result */
                $result = yield adapt($this->client->getQueueUrlAsync([Params::QUEUE_NAME => $queue]));

                return $result->get(Params::QUEUE_URL);
            } catch (SqsException $exception) {
                if ("AWS.SimpleQueueService.NonExistentQueue" !== $exception->getAwsErrorCode()) {
                    throw $exception;
                }

                return "";
            }
        }, $queue);
    }
}
