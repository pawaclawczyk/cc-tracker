<?php

declare(strict_types=1);

namespace CC\Tracker\Controller;

use Aerys\Request;
use Aerys\Response;
use function Amp\coroutine;
use Amp\File;
use CC\Tracker\Infrastructure\FileMessageQueue;
use CC\Tracker\Infrastructure\FilePixelLoader;
use CC\Tracker\Infrastructure\RabbitMessageQueue;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use CC\Tracker\Model\PixelLoader;

final class PixelController
{
    /** @var PixelLoader */
    private $pixelLoader;

    /** @var MessageQueue */
    private $messageQueue;

    /** @var MessageQueue */
    private $fileQueue;

    public function __invoke(Request $request, Response $response)
    {
        yield $response
            ->addHeader('Content-Type', 'image/gif')
            ->end((string) $this->pixelLoader->load());

        $message = $this->prepareData($request);

        $this->messageQueue->send($message);
        $this->fileQueue->send($message);
    }

    private function prepareData(Request $request): Message
    {
        return Message::fromString(json_encode(array_merge(
            $request->getAllHeaders(),
            $request->getAllParams()
        )));
    }

    public function __construct()
    {
        $this->pixelLoader = new FilePixelLoader();
        $this->messageQueue = new RabbitMessageQueue();
        $this->fileQueue = new FileMessageQueue();
    }
}
