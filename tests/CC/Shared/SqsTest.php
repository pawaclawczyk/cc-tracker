<?php

declare(strict_types=1);

namespace Tests\CC\Shared;

use Amp\Loop;
use Aws\Sqs\Exception\SqsException;
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
use PHPUnit\Framework\TestCase;

class SqsTest extends TestCase
{
    const QUEUE_NAME = "test-queue";

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

    /** @test */
    public function it_cannot_write_to_not_existing_queue()
    {
        $this->expectException(SqsException::class);

        $producer = new Producer($this->client, "not-existing");

        Loop::run(function () use ($producer) {
            $producer->write(new Message("Test"));
        });
    }

    /** @test */
    public function it_creates_queue()
    {
        $queueName = \uniqid("existing");
        $queueUrl = $this->createQueue->create($queueName);

        $this->assertInternalType("string", $queueUrl);
        $this->assertStringEndsWith("/{$queueName}", $queueUrl);

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_finds_existing_queue()
    {
        $queueName = \uniqid("existing");
        $this->createQueue->create($queueName);

        $queueUrl = $this->findQueue->find($queueName);

        $this->assertInternalType("string", $queueUrl);
        $this->assertStringEndsWith("/{$queueName}", $queueUrl);

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_finds_empty_queue_url_if_queue_does_not_exist()
    {
        $queueName = "not-existing";

        $queueUrl = $this->findQueue->find($queueName);

        $this->assertEquals("", $queueUrl);
    }

    /** @test */
    public function it_finds_or_creates_queue_when_queue_does_not_exist_yet()
    {
        $queueName = \uniqid("not-existing-yet");

        $queueUrl = $this->findOrCreateQueue->findOrCreate($queueName);

        $this->assertInternalType("string", $queueUrl);
        $this->assertStringEndsWith("/{$queueName}", $queueUrl);

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_finds_or_creates_queue_when_queue_already_exists()
    {
        $queueName = \uniqid("already-existing");
        $this->createQueue->create($queueName);

        $queueUrl = $this->findOrCreateQueue->findOrCreate($queueName);

        $this->assertInternalType("string", $queueUrl);
        $this->assertStringEndsWith("/{$queueName}", $queueUrl);

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_consumes_messages_from_empty_queue()
    {
        $queueName = \uniqid("empty");
        $queueUrl = $this->createQueue->create($queueName);

        $consumer = new Consumer($this->client, $queueUrl);

        Loop::run(function () use ($consumer) {
            $messages = yield $consumer->read();

            $this->assertCount(0, $messages);
        });

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_produces_and_consumes_messages()
    {
        $queueName = \uniqid("produce-consume");
        $queueUrl = $this->createQueue->create($queueName);

        $producer = new Producer($this->client, $queueUrl);
        $consumer = new Consumer($this->client, $queueUrl);

        Loop::run(function () use ($producer, $consumer) {
            yield $producer->write(new Message("Produced message."));
            $messages = yield $consumer->read();

            $this->assertCount(1, $messages);
            $this->assertEquals("Produced message.", (string) $messages[0]);
        });

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_consumes_messages_in_batches_up_to_ten()
    {
        $queueName = \uniqid("up-to-ten");
        $queueUrl = $this->createQueue->create($queueName);

        $producer = new Producer($this->client, $queueUrl);
        $consumer = new Consumer($this->client, $queueUrl);

        Loop::run(function () use ($producer, $consumer) {
            for ($i = 1; $i <= 11; ++$i) {
                yield $producer->write(new Message("Produced message: {$i}."));
            }

            $totalCount = 0;
            $batchesCount = 0;
            while ($messages = yield $consumer->read()) {
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

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_purges_queue()
    {
        $queueName = \uniqid("purge");
        $queueUrl = $this->createQueue->create($queueName);

        $producer = new Producer($this->client, $queueUrl);
        $consumer = new Consumer($this->client, $queueUrl);

        $purgeQueue = $this->purgeQueue;

        Loop::run(function () use ($producer, $purgeQueue, $queueUrl, $consumer) {
            for ($i = 1; $i <= 10; ++$i) {
                yield $producer->write(new Message("Produced message: {$i}."));
            }

            $purgeQueue->purge($queueUrl);

            $messages = yield $consumer->read();

            $this->assertCount(0, $messages);
        });

        $this->deleteQueue->delete($queueUrl);
    }

    /** @test */
    public function it_deletes_queue()
    {
        $queueName = \uniqid("delete");
        $queueUrl = $this->createQueue->create($queueName);

        $this->deleteQueue->delete($queueUrl);

        $notFoundQueueUrl = $this->findQueue->find($queueName);

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
        $this->purgeQueue = new PurgeQueue($this->client);
        $this->deleteQueue = new DeleteQueue($this->client);
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
    }
}
