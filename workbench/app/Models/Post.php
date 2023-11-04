<?php

namespace Workbench\App\Models;

use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\Database\Factories\PostFactory;

class Post extends Model
{
    use Searchable;

    public function elasticsearchProperties(NewProperties $newProperties)
    {
        $newProperties->title('title');
        $newProperties->longText('text');
    }

    protected static function newFactory(): Factory
    {
        return PostFactory::new();
    }
}
