<?php

declare(strict_types=1);

namespace CC\Shared\Infrastructure\MessageQueue\Sqs;

use Amp\Deferred;
use Amp\Loop;
use GuzzleHttp\Promise\Promise as GuzzlePromise;
use Amp\Promise;

function adapt(GuzzlePromise $promise): Promise
{
    $deferred = new Deferred();

    Loop::defer(function (string $watcherId, GuzzlePromise $promise) use ($deferred) {
        try {
            $deferred->resolve($promise->wait(true));
        } catch (\Throwable $error) {
            $deferred->fail($error);
        }
    }, $promise);

    return $deferred->promise();
}
