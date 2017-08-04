<?php

declare(strict_types=1);

use Amp\Loop;
use Aws\Sqs\SqsClient;
use CC\Shared\Infrastructure\MessageQueue\Sqs\FindQueue;
use CC\Shared\Infrastructure\MessageQueue\Sqs\CreateQueue;
use CC\Shared\Infrastructure\MessageQueue\Sqs\FindOrCreateQueue;
use CC\Shared\Infrastructure\MessageQueue\Sqs\Producer;
use CC\Shared\Model\MessageQueue\Queue;
use CC\Shared\Model\MessageQueue\Message;
use CC\Shared\Infrastructure\MessageQueue\Sqs\DeleteQueue;

require_once __DIR__ . "/../../vendor/autoload.php";

$result = [];

Loop::run(function () use (&$result) {
    $config = [
        "endpoint" => "https://sqs.eu-west-1.amazonaws.com",
        "region"   => "eu-west-1",
        "version"  => "latest",
    ];

    $client = new SqsClient($config);
    $findQueue = new FindQueue($client);
    $createQueue = new CreateQueue($client);
    $findOrCreateQueue = new FindOrCreateQueue($findQueue, $createQueue);

    $producer = new Producer($client, $findOrCreateQueue);

    $deleteQueue = new DeleteQueue($client, $findQueue);

    $queue = new Queue(uniqid("examples_sqs_"));

    $start = microtime(true);

    for ($i = 0; $i < 10000; $i++) {
        yield $producer->write($queue, new Message("Example SQS: {$i}."));
    }

    yield $deleteQueue->delete($queue);

    $result["time"] = microtime(true) - $start;
});

print "Time: {$result["time"]}\n";