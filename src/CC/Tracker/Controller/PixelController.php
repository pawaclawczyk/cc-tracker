<?php

declare(strict_types=1);

namespace CC\Tracker\Controller;

use Aerys\Request;
use Aerys\Response;
use Amp\File;
use Amp\Promise;
use SebastianBergmann\GlobalState\RuntimeException;

final class PixelController
{
    /** @var File\Handle */
    private $handle;

    public function __invoke(Request $request, Response $response)
    {
        $pixel = yield File\get(__DIR__.'/../../../../var/static/pixel.gif');

        yield $response
            ->addHeader('Content-Type', 'image/gif')
            ->end($pixel);

        $this->store($request);
    }

    private function open(): Promise
    {
        if (!$this->handle) {
            $this->handle = File\open(__DIR__ . '/../../../../var/log/requests.log', 'a+');
        }

        return $this->handle;
    }

    private function store(Request $request)
    {
        $data = json_encode(array_merge($request->getAllHeaders(), $request->getAllParams()));

        $this->open()->when(function ($error, File\Handle $handle) use ($data) {
            if ($error) {
                throw new RuntimeException($error);
            }

            $handle->write($data);
        });
    }
}
