<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Aerys\Host;
use function Aerys\router;
use CC\Tracker\Controller\PixelController;
use CC\Tracker\Infrastructure\FilePixelLoader;
use CC\Tracker\Infrastructure\RabbitMessageQueue;

const AERYS_OPTIONS = [
    "connectionsPerIP" => 1000,
];

$params = [
    'host'      => getenv('CC_TRACKER_MQ_HOST') ?: 'rabbit',
    'user'      => 'rabbit',
    'password'  => 'rabbit.123',
];

$queue = getenv('CC_TRACKER_QUEUE') ?: 'tracker';
$port = (int) getenv('CC_TRACKER_PORT') ?: 9000;

$pixelLoader = new FilePixelLoader(__DIR__.'/../var/static/pixel.gif');
$messageQueue = new RabbitMessageQueue($params, $queue);

$router = router()
    ->get("/pixel.gif", new PixelController($pixelLoader, $messageQueue));

$host = (new Host())
    ->expose("*", $port)
    ->use($router);
