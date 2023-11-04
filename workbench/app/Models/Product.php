<?php

namespace Workbench\App\Models;

use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
use Workbench\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class Product extends Model
{
    use Searchable;

    public function elasticsearchProperties(NewProperties $newProperties)
    {
        $newProperties->title('title');
        $newProperties->price();
    }

    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }
}
