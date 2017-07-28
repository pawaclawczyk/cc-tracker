<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure;

use function Amp\Promise\all;
use function Amp\Promise\wait;
use Aws\Result;
use CC\Tracker\Configuration\Configuration;
use CC\Tracker\Infrastructure\MessageQueue\ClientFactory;
use CC\Tracker\Infrastructure\MessageQueue\MessageQueueClients;
use CC\Tracker\Model\Message;
use PHPUnit\Framework\TestCase;

class SQSMessageQueueTest extends TestCase
{
    /** @var ClientFactory */
    private $factory;

    /**
     * @param string $client
     *
     * @test
     * @dataProvider clients
     */
    public function it_sends_message(string $client)
    {
        $mq = $this->factory->custom($client);

        $result = $mq->send(Message::fromString("Hello World"));

        /** @var Result $resolved */
        $resolved = wait($result);

        $this->assertEquals(200, $resolved->get('@metadata')['statusCode']);
    }

    /**
     * @param string $client
     *
     * @test
     * @dataProvider clients
     */
    public function it_sends_full_batch(string $client)
    {
        $mq = $this->factory->custom($client);

        $promises = [];

        for ($i = 0; $i < 10; ++$i) {
            $promises[] = $mq->send(Message::fromString("Hello World"));
        }

        /** @var Result $resolved */
        $resolved = wait(all($promises));

        foreach ($resolved as $result) {
            $this->assertEquals(200, $result->get('@metadata')['statusCode']);
        }
    }

    public function clients(): array
    {
        return [
            [MessageQueueClients::AMAZON_SQS],
            [MessageQueueClients::ELASTIC_MQ],
        ];
    }

    protected function setUp()
    {
        parent::setUp();

        $configuration = Configuration::load();

        $this->factory = new ClientFactory($configuration["message_queue"]);
    }
}
