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
    private $connection;

    public function send(Message $message)
    {
        $this->connect()
            ->then(function (Client $client) {
                return $client->channel();
            })
            ->then(function (Channel $channel) {
                $channel->queueDeclare('tracker');

                return $channel;
            })
            ->then(function (Channel $channel) use ($message) {
                $channel->publish((string) $message, [], '', 'tracker');
            });
    }

    private function connect(): PromiseInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $options = [
            'host'      => 'rabbit',
            'user'      => 'rabbit',
            'password'  => 'rabbit.123',
        ];

        return $this->connection =
            (new Client(ReactAdapter::get(), $options))
                ->connect();
    }
}
