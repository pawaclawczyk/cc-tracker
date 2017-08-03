<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Aws\Sqs\SqsClient;
use CC\Shared\Model\MessageQueue\Consumer as ConsumerContract;
use CC\Shared\Model\MessageQueue\Message;
use Ds\Map;

final class Consumer implements ConsumerContract
{
    private $client;
    private $queueUrl;
    private $maxNumberOfMessages = 10;

    public function __construct(SqsClient $client, string $queueUrl)
    {
        $this->client = $client;
        $this->queueUrl = $queueUrl;
    }

    public function read(): Promise
    {
        $asyncRequest = $this->client->receiveMessageAsync([
            Params::MAX_NUMBER_OF_MESSAGES => $this->maxNumberOfMessages,
            Params::QUEUE_URL              => $this->queueUrl,
        ]);

        $deferred = new Deferred();

        $queueUrl = $this->queueUrl;

        $parseMessage = function (array $data) use ($queueUrl): Message {
            $message = new Message($data[Params::BODY]);
            unset($data[Params::BODY]);

            return $message
                ->withMetadata(new Map($data))
                ->withMetadata(new Map([Params::QUEUE_URL => $queueUrl]));
        };

        Loop::defer(function () use ($deferred, $asyncRequest, $parseMessage) {
            $result = $asyncRequest->wait(true);

            if (null === $result->get(Params::MESSAGES)) {
                $deferred->resolve([]);
            } else {
                $messages = \array_map($parseMessage, $result->get(Params::MESSAGES));

                $deferred->resolve($messages);
            }
        });

        return $deferred->promise();
    }
}
