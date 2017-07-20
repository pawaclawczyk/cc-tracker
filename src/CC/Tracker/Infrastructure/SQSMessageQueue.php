<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use function Amp\Promise\adapt;
use Aws\Sqs\SqsClient;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;

class SQSMessageQueue implements MessageQueue
{
    private $connectionConfiguration;
    private $queueUrl;

    public function __construct(array $connectionConfiguration, string $queueUrl)
    {
        $this->connectionConfiguration = $connectionConfiguration;
        $this->queueUrl                = $queueUrl;
    }

    public function send(Message $message)
    {
        $client = new SqsClient($this->connectionConfiguration);

        $guzzlePromise =  $client->sendMessageAsync([
            "QueueUrl"    => $this->queueUrl,
            "MessageBody" => (string) $message,
        ]);

        return adapt($guzzlePromise);
    }
}
