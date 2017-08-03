<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use function Amp\call;
use Amp\Promise;
use Aws\Result;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Consumer as ConsumerContract;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Queue;
use Ds\Map;

final class Consumer implements ConsumerContract
{
    private $client;
    private $findOrCreateQueue;
    private $maxNumberOfMessages = 10;
    private $visibilityTimeout = 30;

    public function __construct(SqsClient $client, FindOrCreateQueue $findOrCreateQueue)
    {
        $this->client = $client;
        $this->findOrCreateQueue = $findOrCreateQueue;
    }

    public function read(Queue $queue): Promise
    {
        return call(function (Queue $queue) {
            $queueUrl = yield $this->findOrCreateQueue->findOrCreate($queue);

            /** @var Result $result */
            $result = yield adapt($this->client->receiveMessageAsync([
                Params::MAX_NUMBER_OF_MESSAGES => $this->maxNumberOfMessages,
                Params::QUEUE_URL              => $queueUrl,
                Params::VISIBILITY_TIMEOUT     => $this->visibilityTimeout,
            ]));

            $messages = $result->get(Params::MESSAGES);

            if (null === $messages) {
                return [];
            }

            $parseMessage = function (array $data) use ($queueUrl): Message {
                $message = new Message($data[Params::BODY]);
                unset($data[Params::BODY]);

                return $message
                    ->withMetadata(new Map($data))
                    ->withMetadata(new Map([Params::QUEUE_URL => $queueUrl]));
            };

            return \array_map($parseMessage, $messages);
        }, $queue);
    }
}
