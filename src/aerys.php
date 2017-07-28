<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Aerys\Host;
use function Aerys\router;
use CC\Tracker\Controller\PixelController;
use CC\Tracker\Infrastructure\FilePixelLoader;
use CC\Tracker\Infrastructure\RabbitMessageQueue;

$config = require __DIR__ . "/../config/tracker/Config.php";

\define('AERYS_OPTIONS', $config["aerys"]["options"]);

$pixelLoader = new FilePixelLoader($config["pixel"]);
$messageQueue = new RabbitMessageQueue($config["queue"]["connection"], $config["queue"]["name"]);

$router = router()
    ->get('/pixel.gif', new PixelController($pixelLoader, $messageQueue));

list("address" => $address, "port" => $port) = $config["aerys"]["host"];

$host = (new Host())
    ->expose($address, (int) $port)
    ->use($router);

return $host;
