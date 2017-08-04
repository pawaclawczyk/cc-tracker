<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\RabbitMq;

use function Amp\call;
use Amp\Promise;
use Bunny\Async\Client;
use Bunny\Channel;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Producer as ProducerContract;
use CC\Shared\Model\MessageQueue\Queue;

final class Producer implements ProducerContract
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function write(Queue $queue, Message $message): Promise
    {
        return call(function (Queue $queue, Message $message) {
            if (!$this->client->isConnected()) {
                yield $this->client->connect();
            }

            /** @var Channel $channel */
            $channel = yield $this->client->channel();

            yield $channel->queueDeclare((string) $queue);

            return $channel->publish((string) $message, [], "", (string) $queue);
        }, $queue, $message);
    }
}
