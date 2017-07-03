<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Aerys\Host;
use Aerys\Request;
use Aerys\Response;
use function Aerys\router;
use Amp\File;
use function Amp\File\open;

$log = open(__DIR__.'/../var/log/requests.log', 'a+');

$storeRequest = function (array $headers) use ($log) {
    $log->when(function ($err, $handle) use ($headers) {
        $handle->write(json_encode($headers));
    });
};

$router = router()
    ->get("/pixel.gif", function (Request $request, Response $response) use ($storeRequest) {
        $storeRequest($request->getAllHeaders());

        $pixel = yield File\get(__DIR__.'/../var/static/pixel.gif');

        $response
            ->addHeader('Content-Type', 'image/gif')
            ->end($pixel);
    });

$host = (new Host())
    ->expose("*", 9000)
    ->use($router);
