<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Aerys\Host;
use function Aerys\router;
use CC\Tracker\Controller\PixelController;
use CC\Tracker\Infrastructure\FilePixelLoader;
use CC\Tracker\Infrastructure\RabbitMessageQueue;
use CC\Tracker\Environments;

const AERYS_OPTIONS = [
    "connectionsPerIP" => 1000,
];

$rabbitMQConnectionParameters = [
    'host'      => getenv(Environments::CC_TRACKER_MQ_HOST)     ?: 'rabbit',
    'user'      => getenv(Environments::CC_TRACKER_MQ_USER)     ?: 'rabbit',
    'password'  => getenv(Environments::CC_TRACKER_MQ_PASSWORD) ?: 'rabbit.123',
];

$queue     =       getenv(Environments::CC_TRACKER_QUEUE_NAME)   ?: 'tracker';
$host      =       getenv(Environments::CC_TRACKER_HOST_ADDRESS) ?: '*';
$port      = (int) getenv(Environments::CC_TRACKER_HOST_PORT)    ?: 9000;
$pixelFile =       getenv(Environments::CC_TRACKER_PIXEL_FILE)   ?: __DIR__.'/../var/static/pixel.gif';

$pixelLoader = new FilePixelLoader($pixelFile);
$messageQueue = new RabbitMessageQueue($rabbitMQConnectionParameters, $queue);

$router = router()
    ->get("/pixel.gif", new PixelController($pixelLoader, $messageQueue));

$host = (new Host())
    ->expose($host, $port)
    ->use($router);
