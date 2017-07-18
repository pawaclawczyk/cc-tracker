<?php

namespace Tests\CC\Tracker\Controller;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Tests\CC\Tracker\Infrastructure\Helper\AppRunner;
use Tests\CC\Tracker\Infrastructure\Helper\RabbitMessageQueueReader;

class PixelControllerTest extends TestCase
{
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

        $this->appRunner = (new AppRunner())->start(12345, $this->queue);

        $this->reader = new RabbitMessageQueueReader([
            'host' => '127.0.0.1',
            'user' => 'rabbit',
            'password' => 'rabbit.123',
        ]);

        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:12345'
        ]);
    }

    protected function tearDown()
    {
        $this->appRunner->debug();
        $this->appRunner->stop();

        parent::tearDown();
    }
}
