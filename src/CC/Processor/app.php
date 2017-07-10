<?php

declare(strict_types=1);

namespace CC\Processor;

require_once __DIR__.'/../../../vendor/autoload.php';

use Amp\Loop;
use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Amp\ReactAdapter\ReactAdapter;
use Amp\Mysql\Connection;
use function Amp\Promise\wait;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\PDOException;

$options = [
    'host'      => 'rabbit',
    'user'      => 'rabbit',
    'password'  => 'rabbit.123',
];

$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'user' => 'tracker',
    'password' => 'tracker.123',
    'host' => 'mysql',
    'port' => 3306,
    'dbname' => 'tracker',
]);

$connection->connect();

(new Client(ReactAdapter::get(), $options))
    ->connect()
    ->then(function (Client $client) {
        return $client->channel();
    })
    ->then(function (Channel $channel) {
        return $channel->qos(0, 5)->then(function () use ($channel) {
            return $channel;
        });
    })
    ->then(function (Channel $channel) use ($connection) {
        $channel->consume(
            function (Message $message, Channel $channel, Client $client) use ($connection) {
                $connection
                    ->executeQuery('INSERT INTO raw_requests VALUES(null, :content)', ['content' => $message->content,])
                    ->execute();

                $channel->ack($message);
            },
            'tracker'
        );
    });

Loop::repeat(10000, function () use ($connection) {
    print "Database connection is " . ($connection->isConnected() ? "OK" : "NOT OK") . PHP_EOL ;
});

Loop::run();
