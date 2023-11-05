<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Sigmie\Base\Http\Responses\Search;
use Sigmie\ElasticsearchScout\ElasticsearchEngine;
use Sigmie\ElasticsearchScout\ElasticsearchScout;
use Sigmie\ElasticsearchScout\Facades\Sigmie;
use Workbench\App\Models\Product;

class FacadeTest extends TestCase
{
    /**
     * @test
     */
    public function search()
    {
        $model = new Product();

        $indexName = config('scout.prefix') . $model->getTable();

        Sigmie::newIndex($indexName)->create();

        $indices = Sigmie::indices();

        $this->assertCount(1, $indices);
    }
}
