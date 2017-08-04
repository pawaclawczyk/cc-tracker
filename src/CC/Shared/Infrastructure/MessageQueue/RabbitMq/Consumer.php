<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\RabbitMq;

use function Amp\call;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use CC\Shared\Model\MessageQueue\Consumer as ConsumerContract;
use CC\Shared\Model\MessageQueue\Queue;
use Ds\Map;
use React\Promise\PromiseInterface;

final class Consumer implements ConsumerContract
{
    private $client;
    private $channel;
    private $buffer;
    /** @var Deferred */
    private $deferred;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->buffer = new \Ds\Queue();
    }

    public function read(Queue $queue): Promise
    {
        return call(function () use ($queue) {
            if (null === $this->channel) {
                $this->channel = yield Promise\adapt($this->bind($queue));
            }

            if ($this->buffer->isEmpty()) {
                $this->deferred = new Deferred();

                $timeout = Loop::delay(500, function () {
                    $temp = $this->deferred;
                    $this->deferred = null;
                    $temp->resolve(null);
                });
                Loop::unreference($timeout);

                $promise = $this->deferred->promise();
                $promise->onResolve(function () use ($timeout) {
                    Loop::cancel($timeout);
                });

                return $promise;
            } else {
                return $this->buffer->pop();
            }
        });
    }

    public function bind(Queue $queue): PromiseInterface
    {
        $channel = (!$this->client->isConnected())
            ? $this->client->connect()->then(function (Client $client) { return $client->channel(); })
            : $this->client->channel();

        return $channel
            ->then(function (Channel $channel) use ($queue) {
                $channel->queueDeclare((string) $queue);

                return $channel;
            })
            ->then(function (Channel $channel) {
                return $channel->qos(0, 5)
                    ->then(function () use ($channel) {
                        return $channel;
                    });
            })
            ->then(function (Channel $channel) use ($queue) {
                $channel->consume(
                    function (Message $message, Channel $channel, Client $client) use ($queue) {
                        $modelMessage = (new \CC\Shared\Model\MessageQueue\Message($message->content))->withMetadata(new Map(["original" => $message]));

                        if (null === $this->deferred) {
                            $channel->ack($message);
                            $this->buffer->push($modelMessage);
                        } else {
                            $channel->ack($message);
                            $this->deferred->resolve($modelMessage);
                        }
                    },
                    (string) $queue
                );

                return $channel;
            });
    }
}
