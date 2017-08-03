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
        $queue = new Queue(\uniqid("existing"));

        Loop::run(function () use ($queue) {
            $queueUrl = yield $this->createQueue->create($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_finds_existing_queue()
    {
        $queue = new Queue(\uniqid("existing"));

        Loop::run(function () use ($queue) {
            yield $this->createQueue->create($queue);

            $queueUrl = $this->findQueue->find($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_finds_empty_queue_url_if_queue_does_not_exist()
    {
        $queue = new Queue("not-existing");

        $queueUrl = $this->findQueue->find($queue);

        $this->assertEquals("", $queueUrl);
    }

    /** @test */
    public function it_finds_or_creates_queue_when_queue_does_not_exist_yet()
    {
        $queue = new Queue(\uniqid("not-existing-yet"));

        Loop::run(function () use ($queue) {
            $queueUrl = yield $this->findOrCreateQueue->findOrCreate($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_finds_or_creates_queue_when_queue_already_exists()
    {
        $queue = new Queue(\uniqid("already-existing"));

        Loop::run(function () use ($queue) {
            yield $this->createQueue->create($queue);

            $queueUrl = yield $this->findOrCreateQueue->findOrCreate($queue);

            $this->assertInternalType("string", $queueUrl);
            $this->assertStringEndsWith("/{$queue}", $queueUrl);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_consumes_messages_from_empty_queue()
    {
        $queue = new Queue(\uniqid("empty"));

        $consumer = $this->consumer;

        Loop::run(function () use ($queue, $consumer) {
            $messages = yield $consumer->read($queue);

            $this->assertCount(0, $messages);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_produces_and_consumes_messages()
    {
        $queue = new Queue(\uniqid("produce-consume"));

        $producer = $this->producer;
        $consumer = $this->consumer;

        Loop::run(function () use ($queue, $producer, $consumer) {
            yield $producer->write($queue, new Message("Produced message."));
            $messages = yield $consumer->read($queue);

            $this->assertCount(1, $messages);
            $this->assertEquals("Produced message.", (string) $messages[0]);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_produces_and_consumes_messages_and_creates_queue_if_it_does_not_exist()
    {
        $queue = new Queue(\uniqid("not-existing-yet"));

        $producer = $this->producer;
        $consumer = $this->consumer;

        Loop::run(function () use ($queue, $producer, $consumer) {
            yield $producer->write($queue, new Message("Produced message."));
            $messages = yield $consumer->read($queue);

            $this->assertCount(1, $messages);
            $this->assertEquals("Produced message.", (string) $messages[0]);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_consumes_messages_in_batches_up_to_ten()
    {
        $queue = new Queue(\uniqid("up-to-ten"));

        $producer = $this->producer;
        $consumer = $this->consumer;

        Loop::run(function () use ($queue, $producer, $consumer) {
            for ($i = 1; $i <= 11; ++$i) {
                yield $producer->write($queue, new Message("Produced message: {$i}."));
            }

            $totalCount = 0;
            $batchesCount = 0;
            while ($messages = yield $consumer->read($queue)) {
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
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_acknowledges_messages()
    {
        $queue = new Queue(\uniqid("acknowledge"));

        $producer = $this->producer;
        $consumer = $this->consumer;

        $reflection = new \ReflectionObject($consumer);
        $property = $reflection->getProperty("visibilityTimeout");
        $property->setAccessible(true);
        $property->setValue($consumer, 1);

        $acknowledgeMessage = $this->acknowledgeMessage;

        Loop::run(function () use ($queue, $producer, $consumer, $acknowledgeMessage) {
            yield $producer->write($queue, new Message("Produced message."));
            $messages = yield $consumer->read($queue);

            $this->assertCount(1, $messages);

            \sleep(1);

            $ack = yield $acknowledgeMessage->ack($messages[0]);

            $this->assertTrue($ack);

            $messages = yield $consumer->read($queue);

            $this->assertCount(0, $messages);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_purges_queue()
    {
        $queue = new Queue(\uniqid("purge"));

        $producer = $this->producer;
        $consumer = $this->consumer;

        $purgeQueue = $this->purgeQueue;

        Loop::run(function () use ($queue, $producer, $purgeQueue, $consumer) {
            for ($i = 1; $i <= 10; ++$i) {
                yield $producer->write($queue, new Message("Produced message: {$i}."));
            }

            $purgeQueue->purge($queue);

            $messages = yield $consumer->read($queue);

            $this->assertCount(0, $messages);
        });

        $this->deleteQueue->delete($queue);
    }

    /** @test */
    public function it_deletes_queue()
    {
        $queue = new Queue(\uniqid("delete"));

        $this->deleteQueue->delete($queue);

        $notFoundQueueUrl = $this->findQueue->find($queue);

        $this->assertEquals("", $notFoundQueueUrl);
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
