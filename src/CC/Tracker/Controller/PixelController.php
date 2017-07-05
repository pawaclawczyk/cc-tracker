<?php

declare(strict_types=1);

namespace CC\Tracker\Controller;

use Aerys\Request;
use Aerys\Response;
use Amp\File;
use CC\Tracker\Infrastructure\FileMessageQueue;
use CC\Tracker\Infrastructure\RabbitMessageQueue;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;

final class PixelController
{
    /** @var MessageQueue */
    private $messageQueue;

    /** @var MessageQueue */
    private $fileQueue;

    public function __invoke(Request $request, Response $response)
    {
        $pixel = yield File\get(__DIR__.'/../../../../var/static/pixel.gif');

        yield $response
            ->addHeader('Content-Type', 'image/gif')
            ->end($pixel);

        $data = $this->prepareData($request);

        $this->messageQueue->send(Message::fromString($data));
        $this->fileQueue->send(Message::fromString($data));
    }

    private function prepareData(Request $request): string
    {
        return json_encode(array_merge($request->getAllHeaders(), $request->getAllParams()));
    }

    public function __construct()
    {
        $this->messageQueue = new RabbitMessageQueue();
        $this->fileQueue = new FileMessageQueue();
    }
}
