<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use CC\Tracker\Model\Pixel;
use CC\Tracker\Model\PixelLoader;
use Throwable;
use RuntimeException;

final class FilePixelLoader implements PixelLoader
{
    private $path;
    private $pixel;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function load()
    {
        if ($this->pixel) {
            return $this->pixel;
        }

        return $this->pixel = new Pixel($this->readFile());
    }

    private function readFile()
    {
        try {
            $f = fopen($this->path, 'rb');
            $bin = fread($f, 1024);
            fclose($f);
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $bin;
    }
}
