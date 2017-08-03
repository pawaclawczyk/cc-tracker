<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Controller;

use CC\Tracker\Configuration\Configuration;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Tests\CC\Tracker\Infrastructure\Helper\RabbitMessageQueueReader;

class PixelControllerTest extends TestCase
{
    private const PIXEL_QUERY = "/pixel.gif";

    private const CONTENT_TYPE_HEADER = 'content-type';
    private const EXPECTED_CONTENT_TYPE = 'image/gif';

    private const USER_AGENT_HEADER = 'user-agent';
    private const EXPECTED_USER_AGENT = 'GuzzleHttp';

    /** @var RabbitMessageQueueReader */
    private $reader;

    /** @var Client */
    private $client;

    /** @var string */
    private $queueName;

    /** @test */
    public function it_returns_pixel()
    {
        $response = $this->client->get(self::PIXEL_QUERY);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(self::EXPECTED_CONTENT_TYPE, $response->getHeader(self::CONTENT_TYPE_HEADER));
    }

    /** @test */
    public function it_sends_message_to_queue()
    {
        $this->markTestSkipped("Refactoring time!.");

        $this->reader->purge($this->queueName);

        \usleep(1000);

        $this->client->get(self::PIXEL_QUERY);

        $message = $this->reader->readOneFrom($this->queueName);

        $data = \json_decode((string) $message, true);
        $client = $data[self::USER_AGENT_HEADER][0];

        $this->assertContains(self::EXPECTED_USER_AGENT, $client);
    }

    protected function setUp()
    {
        parent::setUp();

        $configuration = Configuration::load();

        [
            "message_queue" => [
                "queue_name" => $this->queueName,
                "client"     => $client,
                "configs"    => $configs,
                ],
            "aerys" => ["host" => ["port" => $port]]
        ] = $configuration;

        $this->reader = new RabbitMessageQueueReader($configs[$client]);

        $this->client = new Client([
            'base_uri' => "http://tracker:" . $port,
        ]);
    }
}
