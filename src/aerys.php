<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Aerys\Host;
use function Aerys\router;
use CC\Tracker\Controller\PixelController;

$router = router()
    ->get("/pixel.gif", new PixelController());

$host = (new Host())
    ->expose("*", 9000)
    ->use($router);
