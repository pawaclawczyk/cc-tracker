<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure\Helper;

final class AppRunner
{
    /** @var resource */
    private $process;

    /** @var resource[] */
    private $pipes;

    public function start(array $environments = []): AppRunner
    {
        $command = 'php vendor/bin/aerys --config src/aerys.php --debug --workers 1';

        $descriptors = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];

        $cwd = __DIR__.'/../../../../../';

        $this->process = proc_open(
            $command,
            $descriptors,
            $this->pipes,
            $cwd,
            $environments
        );

        stream_set_blocking($this->pipes[0], false);
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        sleep(1);

        return $this;
    }

    public function stop()
    {
        proc_terminate($this->process);
    }

    public function debug()
    {
        echo PHP_EOL . "------------------------------" . PHP_EOL;
        echo PHP_EOL . stream_get_contents($this->pipes[1]) . PHP_EOL;
        echo PHP_EOL . "------------------------------" . PHP_EOL;
        echo PHP_EOL . stream_get_contents($this->pipes[2]) . PHP_EOL;
        echo PHP_EOL . "------------------------------" . PHP_EOL;
    }
}
