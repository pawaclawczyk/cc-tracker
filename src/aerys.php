<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Aerys\Host;
use function Aerys\router;
use CC\Tracker\Controller\PixelController;
use CC\Tracker\Infrastructure\FilePixelLoader;
use CC\Tracker\Infrastructure\MessageQueue\ClientFactory;
use CC\Tracker\Configuration\Configuration;

$configuration = Configuration::load();

\define('AERYS_OPTIONS', $configuration["aerys"]["options"]);

$messageQueueFactory = new ClientFactory($configuration["message_queue"]);
$pixelLoader = new FilePixelLoader($configuration["pixel"]);
$messageQueue = $messageQueueFactory->default();

$router = router()
    ->get('/pixel.gif', new PixelController($pixelLoader, $messageQueue));

["address" => $address, "port" => $port] = $configuration["aerys"]["host"];

$host = (new Host())
    ->expose($address, (int) $port)
    ->use($router)
;

return $host;
