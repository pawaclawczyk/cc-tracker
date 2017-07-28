<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure;

use CC\Tracker\Configuration\Configuration;
use CC\Tracker\Infrastructure\MessageQueue\ClientFactory;
use CC\Tracker\Infrastructure\MessageQueue\MessageQueueClients;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use PHPUnit\Framework\TestCase;
use function Amp\Promise\wait;
use Tests\CC\Tracker\Infrastructure\Helper\RabbitMessageQueueReader;

class RabbitMessageQueueTest extends TestCase
{
    /** @var string */
    private $queue;

    /** @var MessageQueue */
    private $messageQueue;

    /** @var RabbitMessageQueueReader */
    private $reader;

    /** @test */
    public function it_sends_message()
    {
        $messageToSend = Message::fromString('Hello world!');

        $promise = $this->messageQueue->send($messageToSend);
        $result = wait($promise);

        $this->assertTrue($result);

        $this->assertEquals(
            $messageToSend,
            $this->reader->readOneFrom($this->queue)
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $configuration = Configuration::load();

        $this->queue = \uniqid('channel_');

        $factory = new ClientFactory($configuration["message_queue"]);

        $this->messageQueue = $factory->custom(MessageQueueClients::RABBIT_MQ, $this->queue);
        $this->reader = new RabbitMessageQueueReader($configuration["message_queue"]["configs"][MessageQueueClients::RABBIT_MQ]);
    }

    protected function tearDown()
    {
        $this->reader->delete($this->queue);

        $this->messageQueue = null;
        $this->reader = null;
        $this->queue = null;

        parent::tearDown();
    }
}
