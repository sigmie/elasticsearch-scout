<?php

namespace Sigmie\ElasticsearchScout;

use Illuminate\Support\Facades\Config;
use Laravel\Scout\EngineManager;
use Sigmie\Base\Http\ElasticsearchConnection;
use Sigmie\ElasticsearchScout\Commands\SyncIndexSettingsCommand;
use Sigmie\Http\JSONClient;
use Sigmie\Sigmie;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ElasticsearchScoutServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('elasticsearch-scout')
            ->hasCommand(SyncIndexSettingsCommand::class)
            ->hasConfigFile();


        resolve(EngineManager::class)->extend('elasticsearch', function ($app) {

            Config::set('scout.elasticsearch.index-settings', config('elasticsearch-scout.index-settings'));

            $hosts = config('elasticsearch-scout.hosts');
            $auth = config('elasticsearch-scout.auth');
            $config = config('elasticsearch-scout.guzzle_config');

            $hosts = str_contains($hosts, ',') ? explode(',', $hosts) : [$hosts];

            $jsonClient = match ($auth['type']) {
                'none' => JSONClient::create($hosts, $config),
                'basic' => JSONClient::createWithBasic($hosts, $auth['user'], $auth['password'], $config),
                'token' => JSONClient::createWithToken($hosts, $auth['token'], $config),
                'headers' => JSONClient::createWithHeaders($hosts, $auth['headers'], $config),
                default => JSONClient::create($hosts, $config)
            };

            $elasticsearchConnection = new ElasticsearchConnection($jsonClient);

            $sigmie = new Sigmie($elasticsearchConnection);

            return new ElasticsearchEngine($sigmie);
        });
    }
}
