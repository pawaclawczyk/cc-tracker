<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

final class Params
{
    const QUEUE_URL = "QueueUrl";
    const RECEIPT_HANDLE = "ReceiptHandle";
    const MAX_NUMBER_OF_MESSAGES = "MaxNumberOfMessages";
    const BODY = "Body";
    const MESSAGES = "Messages";
    const QUEUE_NAME = "QueueName";
    const QUEUE_NAME_PREFIX = "QueueNamePrefix";
    const QUEUE_URLS = "QueueUrls";
    const MESSAGE_BODY = "MessageBody";
    const VISIBILITY_TIMEOUT = "VisibilityTimeout";
}
