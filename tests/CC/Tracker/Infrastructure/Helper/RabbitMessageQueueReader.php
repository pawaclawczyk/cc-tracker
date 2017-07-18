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
    }

    public function readOneFrom(string $queue): Message
    {
        $this->client->connect();

        $channel = $this->client->channel();
        $message = $channel->get($queue);

        $channel->ack($message);

        $this->client->disconnect();

        return Message::fromString($message->content);
    }
}
