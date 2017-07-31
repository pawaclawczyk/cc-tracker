<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure\Aerys;

use Aerys\Response;
use Amp\Promise;

final class JsonResponse
{
    public static function send(Response $response, $data): Promise
    {
        return $response
            ->setHeader("Content-Type", "application/json")
            ->end(\json_encode($data));
    }
}
