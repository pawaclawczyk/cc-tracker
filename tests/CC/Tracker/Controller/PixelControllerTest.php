<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Controller;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Tests\CC\Tracker\Infrastructure\Helper\RabbitMessageQueueReader;

class PixelControllerTest extends TestCase
{
    /** @var RabbitMessageQueueReader */
    private $reader;

    /** @var Client */
    private $client;

    /** @var string */
    private $queue;

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
        $this->reader->purge($this->queue);

        \usleep(1000);

        $this->client->get('/pixel.gif');

        $message = $this->reader->readOneFrom("tracker");

        $data = \json_decode((string) $message, true);
        $client = $data['user-agent'][0];

        $this->assertContains('GuzzleHttp', $client);
    }

    protected function setUp()
    {
        parent::setUp();

        $config = require __DIR__ . "/../../../../config/tracker/Config.php";

        [
            "queue" => [
                "name"       => $this->queue,
                "connection" => $params,
                ],
            "aerys" => [
                "host" => [
                    "port" => $port,
                    ],
            ]
        ] = $config;

        $this->reader = new RabbitMessageQueueReader($params);

        $this->client = new Client([
            'base_uri' => "http://127.0.0.1:" . $port,
        ]);
    }
}
