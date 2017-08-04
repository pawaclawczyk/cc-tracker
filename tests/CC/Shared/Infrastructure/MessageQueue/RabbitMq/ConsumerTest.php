<?php

declare(strict_types=1);

namespace Tests\CC\Shared\Infrastructure\MessageQueue\RabbitMq;

use Amp\Loop;
use function Amp\Promise\wait;
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

        $queue = new Queue("produce_consume");
        $message = new Message("Test.");

        $promise = $producer->write($queue, $message);

        $result = wait($promise);

        $this->assertTrue($result);

        $consumer = new Consumer($client);

        $promise = $consumer->read($queue);

        $result = wait($promise);

        $this->assertEquals("Test.", (string) $result);
    }

    /** @test */
    public function it_produces_and_consumes_message_in_loop()
    {
        Loop::run(function () {
            $options = [
                'host'      => 'rabbit',
                'user'      => 'rabbit',
                'password'  => 'rabbit.123',
            ];

            $clientProducer = new Client(ReactAdapter::get(), $options);
            $clientConsumer = new Client(ReactAdapter::get(), $options);

            $producer = new Producer($clientProducer);
            $consumer = new Consumer($clientConsumer);

            $queue = new Queue("produce_consume");
            $message = new Message("Test.");

            $counter = 0;
            Loop::repeat(100, function (string $watcherId) use ($producer, $queue, $message, &$counter) {
                yield $producer->write($queue, $message);

                if (++$counter === 10) {
                    Loop::cancel($watcherId);
                }
            });

            while ($message = yield $consumer->read($queue)) {
                $this->assertInstanceOf(Message::class, $message);
            }

            $clientConsumer->disconnect();
        });
    }
}
