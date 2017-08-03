<?php

declare(strict_types=1);

namespace Tests\CC\Shared;

use Amp\Loop;
use Aws\Sqs\SqsClient;
use CC\Shared\Infrastructure\MessageQueue\Sqs\AcknowledgeMessage;
use CC\Shared\Infrastructure\MessageQueue\Sqs\CreateQueue;
use CC\Shared\Infrastructure\MessageQueue\Sqs\Consumer;
use CC\Shared\Infrastructure\MessageQueue\Sqs\DeleteQueue;
use CC\Shared\Infrastructure\MessageQueue\Sqs\FindOrCreateQueue;
use CC\Shared\Infrastructure\MessageQueue\Sqs\FindQueue;
use CC\Shared\Infrastructure\MessageQueue\Sqs\Producer;
use CC\Shared\Infrastructure\MessageQueue\Sqs\PurgeQueue;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Model\MessageQueue\Queue;
use PHPUnit\Framework\TestCase;

class SqsTest extends TestCase
{
    /** @var SqsClient */
    private $client;

    /** @var FindQueue */
    private $findQueue;

    /** @var CreateQueue */
    private $createQueue;

    /** @var FindOrCreateQueue */
    private $findOrCreateQueue;

    /** @var AcknowledgeMessage */
    private $acknowledgeMessage;

    /** @var PurgeQueue */
    private $purgeQueue;

    /** @var DeleteQueue */
    private $deleteQueue;

    /** @var Consumer */
    private $consumer;

    /** @var Producer */
    private $producer;

    /** @test */
    public function it_creates_queue()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("existing"));

            $queueUrl = yield $this->createQueue->create($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_finds_existing_queue()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("existing"));

            yield $this->createQueue->create($queue);

            $queueUrl = yield $this->findQueue->find($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_finds_empty_queue_url_if_queue_does_not_exist()
    {
        Loop::run(function () {
            $queue = new Queue("not-existing");

            $queueUrl = yield $this->findQueue->find($queue);

            $this->assertEquals("", $queueUrl);
        });
    }

    /** @test */
    public function it_finds_or_creates_queue_when_queue_does_not_exist_yet()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("not-existing-yet"));

            $queueUrl = yield $this->findOrCreateQueue->findOrCreate($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_finds_or_creates_queue_when_queue_already_exists()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("already-existing"));

            yield $this->createQueue->create($queue);

            $queueUrl = yield $this->findOrCreateQueue->findOrCreate($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_consumes_messages_from_empty_queue()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("empty"));

            $messages = yield $this->consumer->read($queue);

            $this->assertCount(0, $messages);

            $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_produces_and_consumes_messages()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("produce-consume"));

            yield $this->producer->write($queue, new Message("Produced message."));
            $messages = yield $this->consumer->read($queue);

            $this->assertCount(1, $messages);
            $this->assertEquals("Produced message.", (string) $messages[0]);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_produces_and_consumes_messages_and_creates_queue_if_it_does_not_exist()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("not-existing-yet"));

            yield $this->producer->write($queue, new Message("Produced message."));
            $messages = yield $this->consumer->read($queue);

            $this->assertCount(1, $messages);
            $this->assertEquals("Produced message.", (string) $messages[0]);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_consumes_messages_in_batches_up_to_ten()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("up-to-ten"));

            for ($i = 1; $i <= 11; ++$i) {
                yield $this->producer->write($queue, new Message("Produced message: {$i}."));
            }

            $totalCount = 0;
            $batchesCount = 0;
            while ($messages = yield $this->consumer->read($queue)) {
                $count = \count($messages);

                $this->assertGreaterThanOrEqual(1, $count);
                $this->assertLessThanOrEqual(10, $count);

                $totalCount += $count;
                ++$batchesCount;

                if (empty($messages)) {
                    break;
                }
            }

            $this->assertEquals(11, $totalCount);
            $this->assertLessThan(11, $batchesCount);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_acknowledges_messages()
    {
        Loop::run(function () {
            $reflection = new \ReflectionObject($this->consumer);
            $property = $reflection->getProperty("visibilityTimeout");
            $property->setAccessible(true);
            $property->setValue($this->consumer, 1);

            $queue = new Queue(\uniqid("acknowledge"));
            yield $this->producer->write($queue, new Message("Produced message."));
            $messages = yield $this->consumer->read($queue);

            $this->assertCount(1, $messages);

            \sleep(2);

            $ack = yield $this->acknowledgeMessage->ack($messages[0]);

            $this->assertTrue($ack);

            $messages = yield $this->consumer->read($queue);

            $this->assertCount(0, $messages);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_purges_queue()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("purge"));

            for ($i = 1; $i <= 10; ++$i) {
                yield $this->producer->write($queue, new Message("Produced message: {$i}."));
            }

            yield $this->purgeQueue->purge($queue);

            $messages = yield $this->consumer->read($queue);

            $this->assertCount(0, $messages);

            yield $this->deleteQueue->delete($queue);
        });
    }

    /** @test */
    public function it_deletes_queue()
    {
        Loop::run(function () {
            $queue = new Queue(\uniqid("delete"));

            yield $this->createQueue->create($queue);
            yield $this->deleteQueue->delete($queue);

            $notFoundQueueUrl = yield $this->findQueue->find($queue);

            $this->assertEquals("", $notFoundQueueUrl);
        });
    }

    protected function setUp()
    {
        parent::setUp();

        $config = [
            "endpoint" => "https://sqs.eu-west-1.amazonaws.com",
            "region"   => "eu-west-1",
            "version"  => "latest",
        ];

        $this->client = new SqsClient($config);
        $this->findQueue = new FindQueue($this->client);
        $this->createQueue = new CreateQueue($this->client);
        $this->findOrCreateQueue = new FindOrCreateQueue($this->findQueue, $this->createQueue);
        $this->acknowledgeMessage = new AcknowledgeMessage($this->client);
        $this->purgeQueue = new PurgeQueue($this->client, $this->findQueue);
        $this->deleteQueue = new DeleteQueue($this->client, $this->findQueue);

        $this->consumer = new Consumer($this->client, $this->findOrCreateQueue);
        $this->producer = new Producer($this->client, $this->findOrCreateQueue);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->client = null;
        $this->findQueue = null;
        $this->createQueue = null;
        $this->findOrCreateQueue = null;
        $this->acknowledgeMessage = null;
        $this->purgeQueue = null;
        $this->deleteQueue = null;

        $this->consumer = null;
        $this->producer = null;
    }
}
