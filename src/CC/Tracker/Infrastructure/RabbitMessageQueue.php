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
    /** @var array */
    private $options;

    /** @var string */
    private $queue;

    /** @var PromiseInterface */
    private $channel;

    public function __construct(array $options, string $queue)
    {
        $this->options = $options;
        $this->queue = $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Message $message): PromiseInterface
    {
        return $this->connect($this->queue)
            ->then(function (Channel $channel) use ($message) {
                return $channel->publish((string) $message, [], '', $this->queue);
            });
    }

    private function connect(string $queue): PromiseInterface
    {
        if ($this->channel) {
            return $this->channel;
        }

        return $this->channel =
            (new Client(ReactAdapter::get(), $this->options))
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
        $this->channel->then(function (Channel $channel) {
            $channel->getClient()->disconnect();
        });
    }
}
