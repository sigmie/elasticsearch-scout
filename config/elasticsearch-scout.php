<?php

return [
    'hosts' => ENV('ELASTICSEARCH_HOSTS', '127.0.0.1:9200'),
    'auth' => [
        'type' => env('ELASTICSEARCH_AUTH_TYPE', 'none'),
        'user' => env('ELASTICSEARCH_USER', ''),
        'password' => env('ELASTICSEARCH_PASSWORD', ''),
        'token' => env('ELASTICSEARCH_TOKEN', ''),
        'headers' => [],
    ],
    'guzzle_config' => [
        'allow_redirects' => false,
        'http_errors' => false,
        'connect_timeout' => 15,
    ]
];
