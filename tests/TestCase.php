<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Sigmie\Base\APIs\Analyze;
use Sigmie\Base\APIs\Explain;
use Sigmie\Base\Http\ElasticsearchConnection;
use Sigmie\Document\Actions as DocumentActions;
use Sigmie\ElasticsearchScout\ElasticsearchScout;
use Sigmie\ElasticsearchScout\ElasticsearchScoutServiceProvider;
use Sigmie\Http\JSONClient;
use Sigmie\Index\Actions as IndexAction;
use Sigmie\Sigmie;
use Sigmie\Testing\Assertions;
use Sigmie\Testing\ClearElasticsearch;

abstract class TestCase extends BaseTestCase
{
    use Analyze, Explain;
    use Assertions;
    use ClearElasticsearch;
    use DocumentActions;
    use IndexAction;
    use RefreshDatabase;
    use WithWorkbench;

    protected Sigmie $sigmie;

    protected JSONClient $jsonClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->jsonClient = JSONClient::create(['localhost:9200']);

        $this->elasticsearchConnection = new ElasticsearchConnection($this->jsonClient);

        $this->clearElasticsearch($this->elasticsearchConnection);

        $this->setElasticsearchConnection($this->elasticsearchConnection);

        $this->sigmie = new Sigmie($this->elasticsearchConnection);
    }

    protected function getPackageAliases($app)
    {
        return [
            'elasticsearch-scout' => ElasticsearchScout::class,
        ];
    }

    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            ElasticsearchScoutServiceProvider::class,
        ];
    }
}
