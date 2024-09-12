<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout;

use Laravel\Scout\Searchable as ScoutSearchable;
use Sigmie\Index\NewIndex;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;

trait Searchable
{
    use ScoutSearchable;

    abstract public function elasticsearchProperties(NewProperties $newProperties);

    public function elasticsearchSearch(NewSearch $newSearch)
    {
        //
    }

    public function elasticsearchIndexName()
    {
        return config('scout.prefix') . $this->getTable();
    }

    public function searchableAs()
    {
        return static::class;
    }

    public function elasticsearchIndex(NewIndex $newIndex)
    {
        return $newIndex->tokenizeOnWordBoundaries()
            ->lowercase()
            ->trim()
            ->shards(1)
            ->replicas(0);
    }
}
