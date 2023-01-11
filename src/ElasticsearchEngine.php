<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Sigmie\Document\Document;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

class ElasticsearchEngine extends Engine
{
    public function __construct(protected Sigmie $sigmie)
    {
    }

    public function lazyMap(Builder $builder, $results, $model)
    {
        $ids = array_map(fn ($hit) => $hit['_id'], $results['hits']);
        $hits = collect($results['hits'])->mapWithKeys(fn ($hit) => [$hit['_id'] => $hit]);

        $models = $model->getScoutModelsByIds(
            $builder,
            $ids
        )->map(function ($model) use ($hits) {
            $hit = $hits[$model->id];

            $model->hit($hit);

            return  $model;
        })->sortByDesc(fn ($model) => (float) $model->hit['_score'])
            ->values();

        return  $models;
    }

    public function createIndex($model, array $options = [])
    {
        $model = new $model();

        $properties = new NewProperties;

        $model->elasticsearchProperties($properties);

        $newIndex = $this->sigmie
            ->newIndex($model->searchableAs())
            ->properties($properties);

        $model->elasticsearchIndex($newIndex);

        $newIndex->create();
    }

    public function deleteIndex($model)
    {
        $model = new $model();

        $index = $this->sigmie->index($model->searchableAs());

        $this->sigmie->delete($index->name);
    }

    public function update($models)
    {
        $indexName = $models->first()->searchableAs();

        $docs = $models
            ->filter(fn ($model) => empty($model->toSearchableArray()) === false)
            ->map(function ($model) {
                $id = $model->id;

                return new Document($model->toSearchableArray(), (string) $id);
            })->toArray();

        $res = $this->sigmie->collect($indexName, true)->merge($docs);
    }

    public function delete($models)
    {
        $indexName = $models->first()->searchableAs();

        $ids = $models
            ->map(fn ($model) => $model->getScoutKey())
            ->values()
            ->all();

        return $this->sigmie->collect($indexName)->remove($ids);
    }

    public function search(Builder $builder)
    {
        $model = $builder->model;

        $limit = $builder->limit ? $builder->limit : 10;

        $properties = new NewProperties;
        $model->elasticsearchProperties($properties);

        $newSearch = $this->sigmie
            ->newSearch($model->searchableAs())
            ->properties($properties)
            ->queryString($builder->query);

        $model->elasticsearchSearch($newSearch);

        $res = $newSearch->size($limit)
            ->get()
            ->json('hits');

        return $res;
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $model = $builder->model;

        $limit = $builder->limit ? $builder->limit : 10;

        $properties = new NewProperties;
        $model->elasticsearchProperties($properties);

        $newSearch = $this->sigmie
            ->newSearch($model->searchableAs())
            ->properties($properties)
            ->queryString($builder->query);

        $model->elasticsearchSearch($newSearch);

        $res = $newSearch
            ->size($limit)
            ->from($perPage * ($page - 1))
            ->get()
            ->json('hits');

        return $res;
    }

    public function mapIds($results)
    {
        $ids = array_map(fn ($hit) => $hit['_id'], $results['hits']);

        return collect($ids);
    }

    public function map(Builder $builder, $results, $model)
    {
        $ids = array_map(fn ($hit) => $hit['_id'], $results['hits']);
        $hits = collect($results['hits'])->mapWithKeys(fn ($hit) => [$hit['_id'] => $hit]);

        $models = $model->getScoutModelsByIds(
            $builder,
            $ids
        )
            ->map(function ($model) use ($hits) {
                $hit = $hits[$model->id];

                $model->hit($hit);

                return  $model;
            })
            ->sortByDesc(fn ($model) => (float) $model->hit['_score'])
            ->values();

        return  $models;
    }

    public function getTotalCount($results)
    {
        return $results['total']['value'];
    }

    public function flush($model)
    {
        $indexName = $model->searchableAs();

        $this->sigmie->collect($indexName)->clear();
    }
}
