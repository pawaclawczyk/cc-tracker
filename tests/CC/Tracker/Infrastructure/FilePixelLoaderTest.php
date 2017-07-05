<?php

declare(strict_types=1);

namespace Tests\CC\Tracker\Infrastructure;

use CC\Tracker\Infrastructure\FilePixelLoader;
use CC\Tracker\Model\Pixel;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FilePixelLoaderTest extends TestCase
{
    /** @test */
    public function it_loads_pixel()
    {
        $pixelLoader = new FilePixelLoader(__DIR__.'/../../../../var/static/pixel.gif');

        $pixel = $pixelLoader->load();

        $this->assertInstanceOf(Pixel::class, $pixel);
    }

    /** @test */
    public function it_throws_runtime_exception_when_file_does_not_exist()
    {
        $this->expectException(RuntimeException::class);

        $pixelLoader = new FilePixelLoader('not-existing-file.gif');

        $pixelLoader->load();
    }
}
