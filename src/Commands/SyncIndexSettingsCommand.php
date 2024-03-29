<?php

namespace Sigmie\ElasticsearchScout\Commands;

use Exception;
use Illuminate\Console\Command;
use Laravel\Scout\EngineManager;

class SyncIndexSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:sync-index-settings {name : The name of the index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync your configured index settings with your search engine (Elasticsearch)';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(EngineManager $manager)
    {
        $engine = $manager->engine();

        $settings = (array) config('elasticsearch-scout.index-settings', []);

        $engine->updateIndexSettings($indexName = $this->argument('name'), $settings);

        $this->info('Settings for the [' . $indexName . '] index synced successfully.');
    }
}
