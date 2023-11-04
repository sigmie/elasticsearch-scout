<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Tests;

use Illuminate\Support\Facades\Artisan;
use Workbench\App\Models\Product;

class SearchTest extends TestCase
{
    /**
     * @test
     */
    public function foo()
    {
        $product = Product::factory()->create();

        Artisan::call('scout:import', ['model' => Product::class]);

        $indexName = config('scout.prefix') . $product->getTable();

        $this->assertEquals(1, $this->sigmie->collect($indexName)->count());
    }
}
