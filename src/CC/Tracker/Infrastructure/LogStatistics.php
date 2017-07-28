<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use Aerys\Bootable;
use Aerys\Server;
use Amp\Loop;
use Psr\Log\LoggerInterface as PsrLogger;

final class LogStatistics implements Bootable
{
    public function boot(Server $server, PsrLogger $logger)
    {
        $collector = \Closure::fromCallable([$this, "collectStatistics"]);

        Loop::repeat(5000, function () use ($server, $logger, $collector) {
            $logger->debug(\json_encode($collector($server)));
        });
    }

    private function collectStatistics(Server $server): array
    {
        return [
            "memory_usage"      => \round(\memory_get_usage() / 1024, 2),
            "memory_peak_usage" => \round(\memory_get_usage() / 1024, 2),
            "server"            => $server->monitor(),
            "loop"              => Loop::getInfo(),
        ];
    }
}
