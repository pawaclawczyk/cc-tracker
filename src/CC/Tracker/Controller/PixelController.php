<?php

declare(strict_types=1);

namespace CC\Tracker\Controller;

use Aerys\Request;
use Aerys\Response;
use Amp\File;
use Amp\Promise;
use Amp\ReactAdapter\ReactAdapter;
use Bunny\Async\Client;
use Bunny\Channel;
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

        $data = $this->prepareData($request);

        $this->send($data);
        $this->store($data);
    }

    private function open(): Promise
    {
        if (!$this->handle) {
            $this->handle = File\open(__DIR__ . '/../../../../var/log/requests.log', 'a+');
        }

        return $this->handle;
    }

    private function prepareData(Request $request): string
    {
        return json_encode(array_merge($request->getAllHeaders(), $request->getAllParams()));
    }

    private function store(string $data)
    {
        $this->open()->onResolve(function ($error, File\Handle $handle) use ($data) {
            if ($error) {
                throw new RuntimeException($error);
            }

            $handle->write($data);
        });
    }

    private function send(string $data)
    {
        $connection = [
            'host'      => 'rabbit',
            'user'      => 'rabbit',
            'password'  => 'rabbit.123',
        ];

        (new Client(ReactAdapter::get(), $connection))
            ->connect()
            ->then(function (Client $client) {
                return $client->channel();
            })
            ->then(function (Channel $channel) {
                $channel->queueDeclare('tracker');

                return $channel;
            })
            ->then(function (Channel $channel) use ($data) {
                $channel->publish($data, [], '', 'tracker');
            });
    }
}
