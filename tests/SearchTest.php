<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Sigmie\Base\Http\Responses\Search;
use Sigmie\ElasticsearchScout\ElasticsearchEngine;
use Workbench\App\Models\Product;
use Workbench\Database\Factories\ProductFactory;

class SearchTest extends TestCase
{
    /**
     * @test
     */
    public function search()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $products = Product::factory()->count(5)->create();

        $indexName = config('scout.prefix') . $model->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $searchResponse = $engine->search(new Builder($model, ''));

        $this->assertInstanceOf(Search::class, $searchResponse);
    }

    /**
     * @test
     */
    public function wheres()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $products = Product::factory()->count(5)->create(['category' => 'offer']);

        $product = Product::factory()->create([
            'category' => 'new'
        ]);

        $indexName = config('scout.prefix') . $model->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $builder = new Builder($model, '');
        $builder->where('category', 'new');

        $searchResponse = $engine->search($builder);

        $this->assertCount(1, $searchResponse->hits());
        $this->assertInstanceOf(Search::class, $searchResponse);
    }
}
