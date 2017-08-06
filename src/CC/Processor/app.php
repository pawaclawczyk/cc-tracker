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
use CC\Shared\AverageExecution;
use CC\Shared\Memoize;
use DeviceDetector\Cache\Cache;
use DeviceDetector\Cache\StaticCache;
use DeviceDetector\DeviceDetector;
use Doctrine\DBAL\DriverManager;
use Ds\Queue;
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

$countingStaticCache = new class() implements Cache, \Countable {
    protected static $cache = [];
    protected static $hits = 0;

    public function fetch($id)
    {
        if ($this->contains($id)) {
            ++self::$hits;

            return self::$cache[$id];
        }

        return false;
    }

    public function contains($id)
    {
        return isset(self::$cache[$id]) || \array_key_exists($id, self::$cache);
    }

    public function save($id, $data, $lifeTime = 0)
    {
        self::$cache[$id] = $data;

        return true;
    }

    public function delete($id)
    {
        unset(self::$cache[$id]);

        return true;
    }

    public function flushAll()
    {
        self::$cache = [];

        return true;
    }

    public function count()
    {
        return \count(self::$cache);
    }

    public function hits()
    {
        return self::$hits;
    }
};

$deviceDetectorCache = new StaticCache();
$deviceDetector = new DeviceDetector();
$deviceDetector->setCache($countingStaticCache);

$_osAndBrowser = function (string $userAgent) use ($deviceDetector) {
    $deviceDetector->setUserAgent($userAgent);
    $deviceDetector->parse();

    return [
        $deviceDetector->getOs("name"),
        $deviceDetector->getClient("name"),
    ];
};

$osAndBrowser = AverageExecution::lift(Memoize::lift($_osAndBrowser));

$geoIP = new Reader("/var/local/geolite2/GeoLite2-Country.mmdb");

$ip = function (array $data): string {
    return $data["x-forwarded-for"][0] ?? $data["client_addr"];
};

$_country = function (array $data) use ($ip, $geoIP): string {
    $result = null;

    try {
        $result = $geoIP->country($ip($data))->country->name ?? UNKNOWN;
    } catch (AddressNotFoundException $notFound) {
    }

    return $result ?? "not-found";
};

$country = AverageExecution::lift($_country);

$inserts = new Queue();

$counter = 0;

$server = listen("127.0.0.1:9898");

$handler = asyncCoroutine(function (ServerSocket $socket) use (&$counter, $countingStaticCache, $osAndBrowser, $country, $inserts) {
    $stats = [
        "memory" => [
            "usage" => \round(\memory_get_usage() / 1024, 2) . " KiB",
            "peak"  => \round(\memory_get_peak_usage() / 1024, 2) . " KiB",
        ],
        "cache" => [
            "count" => $countingStaticCache->count(),
            "hits"  => $countingStaticCache->hits(),
        ],
        "averages" => [
            "device_detection"      => (string) $osAndBrowser->average(),
            "device_detection_hits" => $osAndBrowser->hits(),
            "country_detection"     => (string) $country->average(),
        ],
        "inserts" => $inserts->count(),
    ];

    yield $socket->write("I'm alive! Processed: $counter items.\n");
    yield $socket->write(\json_encode($stats));
    yield $socket->end("\n");
});

$flushInsertBuffer = function () use ($inserts, $connection) {
    $count = 0;
    $query = "INSERT INTO raw_requests VALUES ";
    $params = [];

    while (!$inserts->isEmpty() && $count < 100) {
        $requestData = $inserts->pop();
        $keys = \array_map(function (string $key) use ($count): string { return ':' . $key . $count; }, \array_keys($requestData));
        $query = \sprintf("%s (null, %s),", $query, \implode(',', $keys));
        $params = \array_merge($params, \array_combine($keys, \array_values($requestData)));
    }

    $query = \trim($query, ',');

    if (!empty($params)) {
        $connection->executeQuery($query, $params);
    }
};

Loop::repeat(100, $flushInsertBuffer);

Loop::defer(function () use ($server, $handler) {
    while ($socket = yield $server->accept()) {
        $handler($socket);
    }
});

Loop::run(function () use ($options, $connection, $country, $osAndBrowser, $inserts, &$counter) {
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
            return $channel->qos(0, 20)->then(function () use ($channel) {
                return $channel;
            });
        })
        ->then(function (Channel $channel) use ($connection, $country, $osAndBrowser, $inserts, &$counter) {
            $channel->consume(
                function (Message $message, Channel $channel, Client $client) use ($connection, $country, $osAndBrowser, $inserts, &$counter) {
                    $data = \json_decode($message->content, true);

                    [$os, $browser] = $osAndBrowser($data["user-agent"][0]);

                    $requestData = [
                        "content" => $message->content,
                        "country" => $country($data),
                        "os"      => $os,
                        "browser" => $browser,
                    ];

                    $inserts->push($requestData);
                    ++$counter;
                    $channel->ack($message);
                },
                'cc-tracker'
            );
        });
});
