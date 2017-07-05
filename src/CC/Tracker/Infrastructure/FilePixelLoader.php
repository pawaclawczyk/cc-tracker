<?php

declare(strict_types=1);

namespace CC\Tracker\Infrastructure;

use CC\Tracker\Model\Pixel;
use CC\Tracker\Model\PixelLoader;

final class FilePixelLoader implements PixelLoader
{
    private $pixel;

    private $path = __DIR__ . '/../../../../var/static/pixel.gif';

    public function load()
    {
        if ($this->pixel) {
            return $this->pixel;
        }

        return $this->pixel = new Pixel($this->readFile());
    }

    private function readFile()
    {
        $f = fopen($this->path, 'rb');
        $bin = fread($f, 1024);
        fclose($f);

        return $bin;
    }
}
