<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Hosts
    |--------------------------------------------------------------------------
    |
    | Here you can specify the available Elasticsearch hosts. Separate them with a comma.
    |
    | Example: "10.0.0.2, 10.0.0.3, 10.0.0.4"
    |
    */

    'hosts' => ENV('ELASTICSEARCH_HOSTS', '127.0.0.1:9200'),

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Authentication
    |--------------------------------------------------------------------------
    |
    | This option controls the authentication that gets used for Elasticsearch.
    |.You should adjust this based on your needs.
    |
    */

    'auth' => [
        'type' => env('ELASTICSEARCH_AUTH_TYPE', 'none'),
        'user' => env('ELASTICSEARCH_USER', ''),
        'password' => env('ELASTICSEARCH_PASSWORD', ''),
        'token' => env('ELASTICSEARCH_TOKEN', ''),
        'headers' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guzzle Configuration for Elasticsearch
    |--------------------------------------------------------------------------
    |
    | This option controls the Guzzle configuration that gets used.
    |
    */

    'guzzle_config' => [
        'allow_redirects' => false,
        'http_errors' => false,
        'connect_timeout' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Index Settings
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Elasticsearch index settings. You can state
    | the default number of shards and replicas for your Elasticsearch index.
    |
    */

    'index-settings' => [
        'shards' => env('ELASTICSEARCH_INDEX_SHARDS', 1),
        'replicas' => env('ELASTICSEARCH_INDEX_REPLICAS', 2),
    ],
];
