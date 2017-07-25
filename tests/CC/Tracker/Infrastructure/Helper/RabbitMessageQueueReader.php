<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure\Helper;

use Bunny\Client;
use CC\Tracker\Model\Message;

class RabbitMessageQueueReader
{
    private $client;
    private $channel;

    public function __construct(array $params)
    {
        $this->client = new Client($params);
        $this->client->connect();

        $this->channel = $this->client->channel();
    }

    public function purge(string $queue): bool
    {
        $this->channel->queueDeclare($queue, false, false, false, false, true);

        return $this->channel->queuePurge($queue, true);
    }

    public function delete(string $queue): bool
    {
        return $this->channel->queueDelete($queue, false, false, true);
    }

    public function readOneFrom(string $queue): Message
    {
        $message = $this->channel->get($queue);

        $this->channel->ack($message);

        return Message::fromString($message->content);
    }

    public function __destruct()
    {
        $this->client->disconnect();
    }
}
