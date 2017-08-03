<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Producer as ProducerContract;
use CC\Shared\Model\MessageQueue\Queue;

final class Producer implements ProducerContract
{
    private $client;
    private $findOrCreateQueue;

    public function __construct(SqsClient $client, FindOrCreateQueue $findOrCreateQueue)
    {
        $this->client = $client;
        $this->findOrCreateQueue = $findOrCreateQueue;
    }

    public function write(Queue $queueName, Message $message): Promise
    {
        $coruotine = function () use ($queueName, $message) {
            $asyncRequest = $this->client->sendMessageAsync([
                Params::MESSAGE_BODY => (string) $message,
                Params::QUEUE_URL    => yield $this->findOrCreateQueue->findOrCreate($queueName),
            ]);

            $deferred = new Deferred();

            Loop::defer(function () use ($deferred, $asyncRequest) {
                $result = $asyncRequest->wait(true);

                $deferred->resolve($result);
            });

            return $deferred->promise();
        };

        return call($coruotine);
    }
}
