<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout;

use Laravel\Scout\Searchable as ScoutSearchable;
use Sigmie\Index\NewIndex;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;

trait Searchable
{
    public readonly array $hit;

    use ScoutSearchable;

    abstract public function elasticsearchProperties(NewProperties $blueprint);

    public function elasticsearchSearch(NewSearch $newSearch)
    {
        //
    }

    public function elasticsearchIndex(NewIndex $newIndex)
    {
        $newIndex->tokenizeOnWordBoundaries()
            ->lowercase()
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
