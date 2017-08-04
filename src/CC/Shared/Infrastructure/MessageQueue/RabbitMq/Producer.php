<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\RabbitMq;

use Amp\Promise;
use Bunny\Async\Client;
use Bunny\Channel;
use function Amp\Promise\adapt;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Producer as ProducerContract;
use CC\Shared\Model\MessageQueue\Queue;
use React\Promise\PromiseInterface;

final class Producer implements ProducerContract
{
    private $client;
    private $channel;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function write(Queue $queue, Message $message): Promise
    {
        return adapt($this->send($queue, $message));
    }

    public function send(Queue $queue, Message $message): PromiseInterface
    {
        return $this->connect($queue)
            ->then(function (Channel $channel) use ($queue, $message) {
                return $channel->publish((string) $message, [], '', (string) $queue);
            });
    }

    private function connect(Queue $queue): PromiseInterface
    {
        if ($this->channel) {
            return $this->channel;
        }

        return $this->channel =
            $this->client
                ->connect()
                ->then(function (Client $client) {
                    return $client->channel();
                })
                ->then(function (Channel $channel) use ($queue) {
                    $channel->queueDeclare($queue);

                    return $channel;
                });
    }

    public function __destruct()
    {
        $this->channel && $this->channel->then(function (Channel $channel) {
            $channel->getClient()->disconnect();
        });
    }
}
