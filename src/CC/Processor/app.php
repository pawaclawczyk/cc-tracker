<?php

declare(strict_types=1);

namespace CC\Processor;

const UNKNOWN = "Unknown";
require_once __DIR__ . '/../../../vendor/autoload.php';

use function Amp\asyncCoroutine;
use Amp\Loop;
use function Amp\Socket\listen;
use Amp\Socket\ServerSocket;
use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Amp\ReactAdapter\ReactAdapter;
use DeviceDetector\Cache\StaticCache;
use DeviceDetector\DeviceDetector;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

$options = [
    'host'      => 'rabbit',
    'user'      => 'rabbit',
    'password'  => 'rabbit.123',
];

$connection = DriverManager::getConnection([
    'driver'   => 'pdo_mysql',
    'user'     => 'tracker',
    'password' => 'tracker.123',
    'host'     => 'mysql',
    'port'     => 3306,
    'dbname'   => 'tracker',
]);

try {
    $connection->connect();
} catch (\Throwable $error) {
}

while (!$connection->isConnected()) {
    echo "Waiting for database...\n";
    \sleep(2);

    try {
        $connection->connect();
    } catch (\Throwable $error) {
    }
}

$geoIP = new Reader("/var/local/geolite2/GeoLite2-Country.mmdb");

$ip = function (array $data): string {
    return $data["x-forwarded-for"][0] ?? $data["client_addr"];
};

$country = function (array $data) use ($ip, $geoIP): string {
    try {
        return $geoIP->country($ip($data))->country->name ?? UNKNOWN;
    } catch (AddressNotFoundException $notFound) {
    }

    return "not-found";
};

$deviceDetectorCache = new StaticCache();
$deviceDetector = new DeviceDetector();
$deviceDetector->setCache($deviceDetectorCache);

$osAndBrowser = function (string $userAgent) use ($deviceDetector) {
    $deviceDetector->setUserAgent($userAgent);
    $deviceDetector->parse();

    return [
        $deviceDetector->getOs("name"),
        $deviceDetector->getClient("name"),
    ];
};

$counter = 0;

$server = listen("127.0.0.1:9898");

$handler = asyncCoroutine(function (ServerSocket $socket) use (&$counter) {
    yield $socket->end("I'm alive! Processed: $counter items.\n");
});

Loop::defer(function () use ($server, $handler) {
    while ($socket = yield $server->accept()) {
        $handler($socket);
    }
});

Loop::run(function () use ($options, $connection, $country, $osAndBrowser, &$counter) {
    (new Client(ReactAdapter::get(), $options))
        ->connect()
        ->then(function (Client $client) {
            return $client->channel();
        })
        ->then(function (Channel $channel) {
            $channel->queueDeclare("cc-tracker");

            return $channel;
        })
        ->then(function (Channel $channel) {
            return $channel->qos(0, 5)->then(function () use ($channel) {
                return $channel;
            });
        })
        ->then(function (Channel $channel) use ($connection, $country, $osAndBrowser, &$counter) {
            $channel->consume(
                function (Message $message, Channel $channel, Client $client) use ($connection, $country, $osAndBrowser, &$counter) {
                    $data = \json_decode($message->content, true);

                    [$os, $browser] = $osAndBrowser($data["user-agent"][0]);

                    try {
                        $connection
                            ->executeQuery(
                                'INSERT INTO raw_requests VALUES(null, :content, :country, :os, :browser)',
                                [
                                    "content" => $message->content,
                                    "country" => $country($data),
                                    "os"      => $os,
                                    "browser" => $browser,
                                ]
                            );

                        ++$counter;

                        $channel->ack($message);
                    } catch (DBALException $exception) {
                        $channel->nack($message);
                    }
                },
                'cc-tracker'
            );
        });
});
