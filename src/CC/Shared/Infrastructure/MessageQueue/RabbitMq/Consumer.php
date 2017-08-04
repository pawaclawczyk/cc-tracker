<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\RabbitMq;

use function Amp\call;
use Amp\Promise;
use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use CC\Shared\Model\MessageQueue\Consumer as ConsumerContract;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Queue;
use Ds\Map;

final class Consumer implements ConsumerContract
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function read(Queue $queue): Promise
    {
        return call(function (Queue $queue) {
            if (!$this->client->isConnected()) {
                yield $this->client->connect();
            }

            /** @var Channel $channel */
            $channel = yield $this->client->channel();

            yield $channel->queueDeclare((string) $queue);
            yield $channel->qos(0, 10);

            $bunnyMessage = null;

            yield $channel->consume(function (BunnyMessage $message) use (&$bunnyMessage) {
                $bunnyMessage = $message;
            }, (string) $queue);

            $parseMessage = function (BunnyMessage $bunnyMessage): Message {
                $message = new Message($bunnyMessage->content);

                return $message
                    ->withMetadata(new Map($bunnyMessage->headers))
                    ->withMetadata(new Map([
                        "consumerTag" => $bunnyMessage->consumerTag,
                        "deliveryTag" => $bunnyMessage->deliveryTag,
                        "redelivered" => $bunnyMessage->redelivered,
                        "exchange"    => $bunnyMessage->exchange,
                        "routingKey"  => $bunnyMessage->routingKey,
                    ]));
            };

            return $parseMessage($bunnyMessage);
        }, $queue);
    }
}
