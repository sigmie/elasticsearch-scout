<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Sigmie\Base\Http\Responses\Search;
use Sigmie\Document\Document;
use Sigmie\Index\AliasedIndex;
use Sigmie\Index\UpdateIndex;
use Sigmie\Mappings\NewProperties;
use Sigmie\Parse\ParseException;
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

        $index = $this->sigmie
            ->index($indexName);

        if ($index instanceof AliasedIndex) {

            $index->update(function (UpdateIndex $update) use ($model) {
                $properties = new NewProperties;

                $model->elasticsearchProperties($properties);

                $update->properties($properties)
                    ->shards((int) config('elasticsearch-scout.index-settings.shards'))
                    ->replicas((int) config('elasticsearch-scout.index-settings.replicas'));

                $model->elasticsearchIndex($update);

                return $update;
            });

            return;
        }

        throw new Exception("Failed to update an Index without an alias. Please make sure to run scout:index before executing scout:import.");
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
        $hits = $results->json('hits.hits');
        $ids = array_map(fn ($hit) => $hit['_id'], $hits);
        $hits = collect($hits)->mapWithKeys(fn ($hit) => [$hit['_id'] => $hit]);

        if (count($hits) === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $models = $model->queryScoutModelsByIds(
            $builder,
            $ids
        )
            ->cursor()
            ->map(function ($model) use ($hits) {
                $hit = $hits[$model->id];

                $model->hit($hit);

                return  $model;
            })
            ->sortByDesc(
                fn ($model) => (float) $model->hit['_score']
            )
            ->values();

        return $models;
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
            ->shards((int) config('elasticsearch-scout.index-settings.shards'))
            ->replicas((int) config('elasticsearch-scout.index-settings.replicas'));

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
            ->map(
                fn ($model) =>
                new Document($model->toSearchableArray(), (string) $model->id)
            )->toArray();

        $this->sigmie->collect($indexName)->merge($docs);
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

    /**
     * @param Builder $builder
     *
     * @return Search
     */
    public function search(Builder $builder)
    {
        $model = $builder->model;

        $indexName = config('scout.prefix') . $model->getTable();

        $limit = $builder->limit ? $builder->limit : 10;

        $whereIns = collect($builder->whereIns)
            ->map(fn ($vals, $field) => "{$field}:['" . implode('\',\'', $vals) . "']")
            ->values();

        $wheres = collect($builder->wheres)
            ->map(fn ($val, $field) => "{$field}:'{$val}'")
            ->values();

        $filters = $whereIns->merge($wheres)->implode(' AND ');

        $properties = new NewProperties;
        $model->elasticsearchProperties($properties);

        $newSearch = $this->sigmie
            ->newSearch($indexName)
            ->properties($properties)
            ->filters($filters)
            ->queryString($builder->query ?? '');

        $search = $newSearch->size($limit);

        $model->elasticsearchSearch($newSearch);

        if (!is_null($builder->callback)) {
            ($builder->callback)($search);
        }

        return $search->get();
    }

    /**
     * @param Builder $builder
     * @param int $perPage
     * @param int $page
     *
     * @return Search
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $model = $builder->model;

        $indexName = config('scout.prefix') . $model->getTable();

        $properties = new NewProperties;
        $model->elasticsearchProperties($properties);

        $newSearch = $this->sigmie
            ->newSearch($indexName)
            ->properties($properties)
            ->queryString($builder->query ?? '');

        $model->elasticsearchSearch($newSearch);

        return $newSearch
            ->size($perPage)
            ->from($perPage * ($page - 1))
            ->get();
    }

    public function mapIds($results)
    {
        $hits = $results->json('hits.hits');

        $ids = array_map(fn ($hit) => $hit['_id'], $hits);

        return collect($ids);
    }

    /**
     *
     * @param Builder $builder
     * @param Search $results
     * @param Model $model
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        $hits = $results->json('hits.hits');
        $ids = array_map(fn ($hit) => $hit['_id'], $hits);
        $hits = collect($hits)->mapWithKeys(fn ($hit) => [$hit['_id'] => $hit]);

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

    /**
     *
     * @param Search $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results->total();
    }

    public function flush($model)
    {
        $indexName = config('scout.prefix') . $model->getTable();

        $this->sigmie->collect($indexName)->clear();
    }
}
