<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Exception;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Sigmie\Base\Http\Requests\Search;
use Sigmie\ElasticsearchScout\ElasticsearchEngine;
use Sigmie\Search\NewSearch;
use Sigmie\Testing\Assert;
use Workbench\App\Models\Product;

class ElasticsearchEngineTest extends TestCase
{

    /**
     * @test
     */
    public function exception_on_index_settings_sync_without_index()
    {
        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $model = new Product();

        $this->expectException(Exception::class);

        $engine->updateIndexSettings($model);
    }

    /**
     * @test
     */
    public function builder_callback()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $products = Product::factory()->count(5)->create();

        $indexName = config('scout.prefix') . ($products->first())->getTable();

        $this->sigmie->refresh($indexName);

        $results = Product::search(
            '',
            fn (NewSearch $search) => $search
        )->get();

        $this->assertCount(5, $results);
    }

    /**
     * @test
     */
    public function index_settings_sync()
    {
        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $model = new Product();

        $engine->createIndex($model);

        $products = Product::factory()->count(5)->create();

        $indexName = config('scout.prefix') . ($products->first())->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $this->assertIndexCount($indexName, 5);

        $index = $this->sigmie->index($indexName);

        $engine->updateIndexSettings($model);

        $newIndex = $this->sigmie->index($indexName);

        $this->assertNotEquals($index->name, $newIndex->name);

        $this->assertIndex($indexName, function (Assert $assert) {
            $assert->assertIndexHasMappings();
        });
    }

    /**
     * @test
     */
    public function delete_all()
    {
        $indexName = uniqid();

        $this->sigmie->newIndex($indexName)->create();

        $this->assertIndexExists($indexName);

        /** @var ElasticsearchEngine $engine */
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

    /**
     * @test
     */
    public function empty_lazy_map()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $indexName = config('scout.prefix') . $model->getTable();

        $searchResponse = $this->sigmie->newSearch($indexName)->get();

        $mapped = $engine->lazyMap(new Builder($model, ''), $searchResponse, $model);

        $this->assertInstanceOf(LazyCollection::class, $mapped);

        $this->assertCount(0, $mapped);
    }

    /**
     * @test
     */
    public function lazy_map()
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

        $mapped = $engine->lazyMap(new Builder($model, ''), $searchResponse, $model);

        $this->assertInstanceOf(LazyCollection::class, $mapped);

        $mapped->each(fn (Product $product) => $this->assertInstanceOf(Product::class, $product));
    }

    /**
     * @test
     */
    public function total_count()
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

        $count = $engine->getTotalCount($searchResponse);

        $this->assertEquals(5, $count);
    }

    /**
     * @test
     */
    public function map_ids()
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

        $mapIds = $engine->mapIds($searchResponse);

        $ids = Product::pluck('id');

        $this->assertEquals($ids, $mapIds);
    }

    /**
     * @test
     */
    public function paginate()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $products = Product::factory()->count(5)->create();

        $indexName = config('scout.prefix') . $model->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $searchResponse = $engine->paginate(new Builder($model, ''), perPage: 3, page: 1);

        $this->assertCount(3, $searchResponse->hits());

        $searchResponse = $engine->paginate(new Builder($model, ''), perPage: 3, page: 2);

        $this->assertCount(2, $searchResponse->hits());
    }

    /**
     * @test
     */
    public function create_index_exception()
    {
        $model = new Product();

        $indexName = config('scout.prefix') . $model->getTable();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $this->assertIndexExists($indexName);

        $this->expectException(Exception::class);

        $engine->createIndex($model);
    }

    /**
     * @test
     */
    public function create_index()
    {
        $model = new Product();

        $indexName = config('scout.prefix') . $model->getTable();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $this->assertIndexExists($indexName);
    }

    /**
     * @test
     */
    public function delete_index()
    {
        $model = new Product();

        $indexName = config('scout.prefix') . $model->getTable();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $this->assertIndexExists($indexName);

        $engine->deleteIndex($model);

        $this->assertIndexNotExists($indexName);
    }
}
