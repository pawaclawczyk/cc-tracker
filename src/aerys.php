<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Aerys\Host;
use function Aerys\router;
use CC\Tracker\Controller\PixelController;
use CC\Tracker\Infrastructure\FilePixelLoader;
use CC\Tracker\Infrastructure\RabbitMessageQueue;

$config = require __DIR__ . "/../config/tracker/Config.php";

const AERYS_OPTIONS = [
    'maxConnections'   => 2048,
    'connectionsPerIP' => 2048,
    "user"             => "app",
];

$pixelLoader  = new FilePixelLoader($config["pixel"]);
$messageQueue = new RabbitMessageQueue($config["queue"]["connection"], $config["queue"]["name"]);

$router = router()
    ->get('/pixel.gif', new PixelController($pixelLoader, $messageQueue));

$host = (new Host())
    ->expose($config["host"]["address"], (int) $config["host"]["port"])
    ->use($router);

return $host;
