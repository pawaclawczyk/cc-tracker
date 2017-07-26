<?php

return [
    "queue" => [
        "name" => getenv("CC_TRACKER_MQ_NAME") ?: "tracker",
        "type" => getenv("CC_TRACKER_MQ_TYPE") ?: "rabbit",
        "connection" => [
            "user"     => getenv("CC_TRACKER_MQ_USER") ?: "rabbit",
            "password" => getenv("CC_TRACKER_MQ_PASS") ?: "rabbit.123",
            "host"     => getenv("CC_TRACKER_MQ_HOST") ?: "rabbit",
        ],
    ],
    "host" => [
        "address" => "*",
        "port" => getenv("CC_TRACKER_HOST_PORT") ?: "9000",
    ],
    "pixel" => __DIR__ . "/../../var/static/pixel.gif",
];
