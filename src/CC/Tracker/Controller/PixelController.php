<?php

declare(strict_types=1);

namespace CC\Tracker\Controller;

use Aerys\Request;
use Aerys\Response;
use CC\Tracker\Model\Message;
use CC\Tracker\Model\MessageQueue;
use CC\Tracker\Model\PixelLoader;

final class PixelController
{
    /** @var PixelLoader */
    private $pixelLoader;

    /** @var MessageQueue */
    private $messageQueue;

    public function __invoke(Request $request, Response $response)
    {
        yield $response
            ->addHeader('Content-Type', 'image/gif')
            ->end((string) $this->pixelLoader->load());

        $message = $this->prepareData($request);

        yield $this->messageQueue->send($message);
    }

    private function prepareData(Request $request): Message
    {
        return Message::fromString(\json_encode(\array_merge(
            $request->getAllHeaders(),
            $request->getAllParams(),
            $request->getConnectionInfo()
        )));
    }

    public function __construct(PixelLoader $pixelLoader, MessageQueue $messageQueue)
    {
        $this->pixelLoader = $pixelLoader;
        $this->messageQueue = $messageQueue;
    }
}
