<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Illuminate\Support\Facades\Artisan;
use Workbench\App\Models\Post;
use Workbench\App\Models\Product;

class CommandsTest extends TestCase
{
    /**
     * @test
     */
    public function import()
    {
        $product = Product::factory()->create();

        Artisan::call('scout:import', ['model' => Product::class]);

        $indexName = config('scout.prefix') . $product->getTable();

        $this->assertEquals(1, $this->sigmie->collect($indexName)->count());
    }

    /**
     * @test
     */
    public function create_index()
    {
        $indexName = config('scout.prefix') . (new Product())->getTable();

        $this->assertIndexNotExists($indexName);

        Artisan::call('scout:index', ['name' => Product::class]);

        $this->assertIndexExists($indexName);
    }

    /**
    * @test
    */
    public function delete_index()
    {
        $indexName = config('scout.prefix') . (new Product())->getTable();

        Artisan::call('scout:index', ['name' => Product::class]);

        $this->assertIndexExists($indexName);

        Artisan::call('scout:delete-index', ['name' => Product::class]);

        $this->assertIndexNotExists($indexName);
    }

    /**
    * @test
    */
    public function delete_all_indices()
    {
        $productIndexName = config('scout.prefix') . (new Product())->getTable();
        Artisan::call('scout:index', ['name' => Product::class]);

        $this->assertIndexExists($productIndexName);

        $postIndexName = config('scout.prefix') . (new Post())->getTable();
        Artisan::call('scout:index', ['name' => Post::class]);

        $this->assertIndexExists($postIndexName);

        Artisan::call('scout:delete-all-indexes');

        $this->assertIndexNotExists($productIndexName);
        $this->assertIndexNotExists($postIndexName);
    }
}
