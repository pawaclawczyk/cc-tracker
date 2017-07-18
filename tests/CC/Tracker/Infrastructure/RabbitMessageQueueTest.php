<?php

namespace Tests\CC\Tracker\Infrastructure;

use CC\Tracker\Environments;
use CC\Tracker\Infrastructure\RabbitMessageQueue;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use PHPUnit\Framework\TestCase;
use function Amp\Promise\wait;
use Tests\CC\Tracker\Infrastructure\Helper\RabbitMessageQueueReader;

class RabbitMessageQueueTest extends TestCase
{
    /** @var array */
    private $params;

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

        $this->params = [
            'host'     => getenv(Environments::CC_TRACKER_MQ_HOST)     ?: "rabbit",
            'user'     => getenv(Environments::CC_TRACKER_MQ_USER)     ?: "rabbit",
            'password' => getenv(Environments::CC_TRACKER_MQ_PASSWORD) ?: "rabbit.123",
        ];

        $this->queue = uniqid('channel_');

        $this->messageQueue = new RabbitMessageQueue($this->params, $this->queue);
        $this->reader = new RabbitMessageQueueReader($this->params);
    }

    protected function tearDown()
    {
        $this->messageQueue = null;
        $this->reader = null;

        parent::tearDown();
    }
}
