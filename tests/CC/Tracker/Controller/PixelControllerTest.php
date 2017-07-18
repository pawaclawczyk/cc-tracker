<?php

namespace Tests\CC\Tracker\Controller;

use CC\Tracker\Environments;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Tests\CC\Tracker\Infrastructure\Helper\AppRunner;
use Tests\CC\Tracker\Infrastructure\Helper\RabbitMessageQueueReader;

class PixelControllerTest extends TestCase
{
    const HOST_PORT = 12345;
    const LOCALHOST   = "127.0.0.1";

    /** @var string */
    private $queue;

    /** @var AppRunner */
    private $appRunner;

    /** @var RabbitMessageQueueReader */
    private $reader;

    /** @var Client */
    private $client;

    /** @test */
    public function it_returns_pixel()
    {
        $response = $this->client->get('/pixel.gif');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['image/gif'], $response->getHeader('content-type'));
    }

    /** @test */
    public function it_sends_message_to_queue()
    {
        $this->client->get('/pixel.gif');

        sleep(1);

        $message = $this->reader->readOneFrom($this->queue);

        $data = json_decode((string) $message, true);
        $client = $data["user-agent"][0];

        $this->assertContains('GuzzleHttp', $client);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->queue = uniqid('queue_');

        $this->appRunner = (new AppRunner())->start([
            Environments::CC_TRACKER_HOST_PORT  => getenv(Environments::CC_TRACKER_HOST_PORT) ?: 9000,
            Environments::CC_TRACKER_QUEUE_NAME => $this->queue,
            Environments::CC_TRACKER_MQ_HOST    => getenv(Environments::CC_TRACKER_MQ_HOST) ?: "rabbit",
        ]);

        $this->reader = new RabbitMessageQueueReader([
            'host'     => getenv(Environments::CC_TRACKER_MQ_HOST)     ?: "rabbit",
            'user'     => getenv(Environments::CC_TRACKER_MQ_USER)     ?: "rabbit",
            'password' => getenv(Environments::CC_TRACKER_MQ_PASSWORD) ?: "rabbit.123",
        ]);

        $this->client = new Client([
            'base_uri' => sprintf("http://127.0.0.1:%d", getenv(Environments::CC_TRACKER_HOST_PORT) ?: 9000),
        ]);
    }

    protected function tearDown()
    {
        $this->appRunner->stop();

        parent::tearDown();
    }
}
