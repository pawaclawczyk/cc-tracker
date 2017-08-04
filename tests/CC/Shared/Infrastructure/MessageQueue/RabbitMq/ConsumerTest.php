<?php

declare(strict_types=1);

namespace Tests\CC\Shared\Infrastructure\MessageQueue\RabbitMq;

use Amp\Loop;
use Amp\ReactAdapter\ReactAdapter;
use Bunny\Async\Client;
use CC\Shared\Infrastructure\MessageQueue\RabbitMq\Consumer;
use CC\Shared\Infrastructure\MessageQueue\RabbitMq\Producer;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Queue;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    /** @test */
    public function it_produces_and_consumes_message()
    {
        $options = [
            'host'      => 'rabbit',
            'user'      => 'rabbit',
            'password'  => 'rabbit.123',
        ];

        $client = new Client(ReactAdapter::get(), $options);
        $producer = new Producer($client);
        $consumer = new Consumer($client);

        Loop::run(function () use ($producer, $consumer) {
            $queue = new Queue(\uniqid("produce_consume_"));

            yield $producer->write($queue, new Message("Produced message"));

            $message = yield $consumer->read($queue);

            $this->assertInstanceOf(Message::class, $message);
        });
    }
}
