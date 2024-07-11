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

    public readonly array $hit;

    abstract public function elasticsearchProperties(NewProperties $newProperties);

    public function elasticsearchSearch(NewSearch $newSearch)
    {
        //
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

    public function toSearchableArray()
    {
        $array = $this->toArray();

        $array['created_at'] = $this->created_at?->format('Y-m-d H:i:s.u');
        $array['updated_at'] = $this->updated_at?->format('Y-m-d H:i:s.u');

        return $array;
    }

    public function hit(array $hit)
    {
        $this->hit = $hit;
    }
}
