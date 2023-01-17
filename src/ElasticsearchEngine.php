<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout;

use Exception;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Sigmie\Document\Document;
use Sigmie\Index\UpdateIndex;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

class ElasticsearchEngine extends Engine
{
    public function __construct(protected Sigmie $sigmie)
    {
    }

    public function updateIndexSettings($model, array $options = [])
    {
        $model = new $model();

        $indexName = config('scout.prefix') . $model->getTable();

        $properties = new NewProperties;

        $this->sigmie
            ->index($indexName)
            ->update(function (UpdateIndex $update) use ($model) {
                $properties = new NewProperties;

                $model->elasticsearchProperties($properties);

                $update->properties($properties)
                    ->shards((int) config('elasticsearch-scout.index-settings.replicas'))
                    ->replicas((int)config('elasticsearch-scout.index-settings.replicas'));

                $model->elasticsearchIndex($update);

                return $update;
            });
    }


    public function deleteAllIndexes()
    {
        $prefix = config('scout.prefix') . '*';

        $indices = $this->sigmie->indices($prefix);

        foreach ($indices as $index) {
            $this->sigmie->delete($index->name);
        }
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

        $indexName = config('scout.prefix') . $model->getTable();

        $index = $this->sigmie->index($indexName);

        if (!is_null($index)) {
            throw new Exception("Index {$indexName} already exists.");
        }

        $properties = new NewProperties;

        $model->elasticsearchProperties($properties);

        $newIndex = $this->sigmie
            ->newIndex($indexName)
            ->properties($properties)
            ->shards((int)config('elasticsearch-scout.index-settings.replicas'))
            ->replicas((int)config('elasticsearch-scout.index-settings.replicas'));

        $model->elasticsearchIndex($newIndex);

        $newIndex->create();
    }

    public function deleteIndex($model)
    {
        $model = new $model();

        $indexName = config('scout.prefix') . $model->getTable();

        $index = $this->sigmie->index($indexName);

        $this->sigmie->delete($index->name);
    }

    public function update($models)
    {
        $indexName = config('scout.prefix') . $models->first()->getTable();

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
        $indexName = config('scout.prefix') . $models->first()->getTable();

        $ids = $models
            ->map(fn ($model) => $model->getScoutKey())
            ->values()
            ->all();

        return $this->sigmie->collect($indexName)->remove($ids);
    }

    public function search(Builder $builder)
    {
        $model = $builder->model;

        $indexName = config('scout.prefix') . $model->getTable();

        $limit = $builder->limit ? $builder->limit : 10;

        $properties = new NewProperties;
        $model->elasticsearchProperties($properties);

        $newSearch = $this->sigmie
            ->newSearch($indexName)
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

        $indexName = config('scout.prefix') . $model->getTable();

        $properties = new NewProperties;
        $model->elasticsearchProperties($properties);

        $newSearch = $this->sigmie
            ->newSearch($indexName)
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
        $indexName = config('scout.prefix') . $model->getTable();

        $this->sigmie->collect($indexName)->clear();
    }
}
