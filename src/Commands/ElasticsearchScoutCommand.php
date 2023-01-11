<?php

namespace Sigmie\ElasticsearchScout\Commands;

use Illuminate\Console\Command;

class ElasticsearchScoutCommand extends Command
{
    public $signature = 'elasticsearch-scout';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
