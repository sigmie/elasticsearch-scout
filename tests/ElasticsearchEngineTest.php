<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Sigmie\Document\Document;
use Sigmie\ElasticsearchScout\ElasticsearchEngine;
use Sigmie\Testing\Assert;
use Workbench\App\Models\Post;
use Workbench\App\Models\Product;

class ElasticsearchEngineTest extends TestCase
{
    /**
     * @test
     */
    public function delete_all()
    {
        $indexName = uniqid();

        $this->sigmie->newIndex($indexName)->create();

        $this->assertIndexExists($indexName);

        /** @var  ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->deleteAllIndexes();

        $this->assertIndexNotExists($indexName);
    }

    /**
     * @test
     */
    public function update()
    {
        $products = Product::factory()->count(5)->create();

        $indexName = config('scout.prefix') . ($products->first())->getTable();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $this->assertIndexCount($indexName, 5);
    }

    /**
     * @test
     */
    public function delete_models()
    {
        $products = Product::factory()->count(5)->create();

        $indexName = config('scout.prefix') . ($products->first())->getTable();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $this->assertIndexCount($indexName, 5);

        $engine->delete($products);

        $this->sigmie->refresh($indexName);

        $this->assertIndexCount($indexName, 0);
    }

    /**
     * @test
     */
    public function map()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $products = Product::factory()->count(5)->create();

        $indexName = config('scout.prefix') . $model->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $searchResponse = $this->sigmie->newSearch($indexName)->get();

        $mapped = $engine->map(new Builder($model, ''), $searchResponse, $model);

        $mapped->each(fn (Product $product) => $this->assertInstanceOf(Product::class, $product));
    }
}
