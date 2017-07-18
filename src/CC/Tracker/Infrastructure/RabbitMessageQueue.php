<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use Amp\ReactAdapter\ReactAdapter;
use Bunny\Async\Client;
use Bunny\Channel;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use React\Promise\PromiseInterface;

final class RabbitMessageQueue implements MessageQueue
{
    private $options;
    private $queue;
    private $connection;

    public function __construct(array $options, string $queue)
    {
        $this->options = $options;
        $this->queue   = $queue;
    }

    public function send(Message $message): PromiseInterface
    {
        $queue = $this->queue;

        return $this->connect()
            ->then(function (Client $client) {
                return $client->channel();
            })
            ->then(function (Channel $channel) use ($queue) {
                $channel->queueDeclare($queue);

                return $channel;
            })
            ->then(function (Channel $channel) use ($message, $queue) {
                return $channel->publish((string) $message, [], '', $queue);
            });
    }

    private function connect(): PromiseInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        return $this->connection =
            (new Client(ReactAdapter::get(), $this->options))
                ->connect();
    }
}
