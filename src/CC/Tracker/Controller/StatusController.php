<?php

declare(strict_types=1);

namespace CC\Tracker\Controller;

use Aerys\Request;
use Aerys\Response;
use Amp\Promise;
use CC\Tracker\Infrastructure\Aerys\JsonResponse;
use CC\Tracker\Infrastructure\Status\Collector;

final class StatusController
{
    private $collector;

    public function __construct(Collector $collector)
    {
        $this->collector = $collector;
    }

    public function __invoke(Request $request, Response $response): Promise
    {
        return JsonResponse::send($response, ($this->collector)());
    }
}
