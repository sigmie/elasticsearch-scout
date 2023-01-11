<?php

return [

    'auth' => [
        'type' => 'none',
        'user' => 'user',
        'password' => 'pass',
        'token' => 'token',
        'headers' => [
            'Authorization' => "auth"
        ]
    ],

    'hosts' => ['127.0.0.1:9200'],

    'guzzle_config' => []

];
