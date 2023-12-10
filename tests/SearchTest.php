<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Illuminate\Http\JsonResponse;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Sigmie\Base\Http\Responses\Search;
use Sigmie\ElasticsearchScout\ElasticsearchEngine;
use Workbench\App\Models\Product;

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

        $this->assertInstanceOf(JsonResponse::class, $searchResponse);
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

        $products = Product::factory()->count(5)->create();

        $product = Product::factory()->create([
            'category' => 'new',
        ]);

        $indexName = config('scout.prefix') . $model->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $builder = new Builder($model, '');
        $builder->where('category', 'new');

        $searchResponse = $engine->search($builder);

        $hits = dot($searchResponse->getData(true))->get('hits.hits');

        $this->assertCount(1, $hits);
        $this->assertInstanceOf(JsonResponse::class, $searchResponse);
    }

    /**
     * @test
     */
    public function where_ins()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $products = collect([
            Product::factory()->create([
                'category' => 'offers',
            ]),
            Product::factory()->create([
                'category' => 'new',
            ]),
        ]);

        $indexName = config('scout.prefix') . $model->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $builder = new Builder($model, '');
        $builder->whereIn('category', ['new', 'offers']);

        $searchResponse = $engine->search($builder);

        $hits = dot($searchResponse->getData(true))->get('hits.hits');

        $this->assertCount(2, $hits);
        $this->assertInstanceOf(JsonResponse::class, $searchResponse);

        $builder = new Builder($model, '');
        $builder->whereIn('category', []);

        $searchResponse = $engine->search($builder);

        $hits = dot($searchResponse->getData(true))->get('hits.hits');

        $this->assertCount(0, $hits);
        $this->assertInstanceOf(JsonResponse::class, $searchResponse);
    }

    /**
     * @test
     */
    public function where_not_ins()
    {
        $model = new Product();

        /** @var ElasticsearchEngine $engine */
        $engine = app(EngineManager::class);

        $engine->createIndex($model);

        $products = collect([
            Product::factory()->create([
                'category' => 'offers',
            ]),
            Product::factory()->create([
                'category' => 'new',
            ]),
        ]);

        $indexName = config('scout.prefix') . $model->getTable();

        $engine->update($products);

        $this->sigmie->refresh($indexName);

        $builder = new Builder($model, '');
        $builder->whereNotIn('category', ['new', 'offers']);

        $searchResponse = $engine->search($builder);

        $hits = dot($searchResponse->getData(true))->get('hits.hits');

        $this->assertCount(0, $hits);
        $this->assertInstanceOf(JsonResponse::class, $searchResponse);

        $builder = new Builder($model, '');
        $builder->whereNotIn('category', []);

        $searchResponse = $engine->search($builder);

        $hits = dot($searchResponse->getData(true))->get('hits.hits');

        $this->assertCount(2, $hits);
        $this->assertInstanceOf(JsonResponse::class, $searchResponse);

        $builder = new Builder($model, '');
        $builder->whereNotIn('category', ['new']);

        $searchResponse = $engine->search($builder);

        $hits = dot($searchResponse->getData(true))->get('hits.hits');

        $this->assertCount(1, $hits);
        $this->assertInstanceOf(JsonResponse::class, $searchResponse);
    }
}
