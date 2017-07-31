<?php

declare(strict_types=1);

namespace CC\Tracker\Controller;

use Aerys\Request;
use Aerys\Response;
use CC\Tracker\Infrastructure\Status\Time\Duration;
use CC\Tracker\Infrastructure\Status\Time\Measurements;

final class TimeMeasuringController
{
    private $controller;
    private $measurements;

    public function __construct(callable $controller, Measurements $measurements)
    {
        $this->controller = $controller;
        $this->measurements = $measurements;
    }

    public function __invoke(Request $request, Response $response)
    {
        $start = \microtime(true);

        $result = yield from ($this->controller)($request, $response);

        $this->measurements->push(new Duration(\time(), \microtime(true) - $start));

        return $result;
    }
}
