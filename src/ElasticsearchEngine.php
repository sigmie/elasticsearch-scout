<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Sigmie\Base\Http\Responses\Search;
use Sigmie\Document\Document;
use Sigmie\Index\AliasedIndex;
use Sigmie\Index\UpdateIndex;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;
use Throwable;

class ElasticsearchEngine extends Engine
{
    public function __construct(protected Sigmie $sigmie) {}

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

        throw new Exception('Failed to update an Index without an alias. Please make sure to run scout:index before executing scout:import.');
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
        $hits = dot($results->getData(true))->get('hits.hits');

        $ids = collect($hits)->pluck('_id')->values()->all();

        $hits = collect($hits)->mapWithKeys(fn($hit) => [$hit['_id'] => $hit]);

        $idsOrder = array_flip($ids);

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

                $model->withScoutMetadata('_score', (float) $hit['_score']);
                $model->withScoutMetadata('_id', $hit['_id']);
                $model->withScoutMetadata('_source', $hit['_source']);

                return $model;
            })
            ->sortBy(fn($model) => $idsOrder[$model->getScoutKey()])
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
            ->shards((int) config('elasticsearch-scout.index-settings.shards'))
            ->replicas((int) config('elasticsearch-scout.index-settings.replicas'));

        // if we use a language builder inside the model the NewIndex builder
        // is replaced by the language Builder and the reference becomes useless.
        $newIndex = $model->elasticsearchIndex($newIndex);

        $newIndex->properties($properties);

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
            ->map(function ($model) {

                $searchableArray = $model->toSearchableArray();

                foreach ($searchableArray as $key => $value) {
                    if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
                        $searchableArray[$key] = $value->format('Y-m-d\TH:i:s.u\Z');
                    }
                }

                if (!empty($searchableArray)) {
                    return new Document($searchableArray, (string) $model->id);
                }

                return null;
            })
            ->filter()
            ->values()
            ->toArray();

        $this->sigmie->collect($indexName)->merge($docs);
    }

    public function delete($models)
    {
        $indexName = config('scout.prefix') . $models->first()->getTable();

        $ids = $models
            ->map(fn($model) => $model->getScoutKey())
            ->values()
            ->all();

        return $this->sigmie->collect($indexName)->remove($ids);
    }

    /**
     * @return Search
     */
    public function search(Builder $builder)
    {
        $model = $builder->model;

        $indexName = config('scout.prefix') . $model->getTable();

        $limit = $builder->limit ? $builder->limit : 10;

        $whereIns = collect($builder->whereIns)
            ->map(fn($vals, $field) => "{$field}:['" . implode('\',\'', $vals) . "']")
            ->values();

        $wheres = collect($builder->wheres)
            ->map(fn($val, $field) => "{$field}:'{$val}'")
            ->values();

        $whereNotIns = collect($builder->whereNotIns)
            ->map(fn($vals, $field) => "NOT {$field}:['" . implode('\',\'', $vals) . "']")
            ->values();

        $filters = $whereIns->merge($wheres)->merge($whereNotIns)->implode(' AND ');

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

        $res = $search->get();

        return new JsonResponse($res->json(), $res->code());
    }

    /**
     * @param  int  $perPage
     * @param  int  $page
     * @return JsonResponse
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

        if (!is_null($builder->callback)) {
            ($builder->callback)($newSearch);
        }

        $res = $newSearch
            ->size($perPage)
            ->from($perPage * ($page - 1))
            ->get();

        return new JsonResponse($res->json(), $res->code());
    }

    public function mapIds($results)
    {
        $hits = dot($results->getData(true))->get('hits.hits');

        $ids = array_map(fn($hit) => $hit['_id'], $hits);

        return collect($ids);
    }

    /**
     * @param JsonResponse $results
     * @param Model $model
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        $hits = dot($results->getData(true))->get('hits.hits');

        if (count($hits) === 0) {
            return $model->newCollection();
        }

        $ids = collect($hits)->pluck('_id')->values()->all();
        $hits = collect($hits)->mapWithKeys(fn($hit) => [$hit['_id'] => $hit]);

        $idsOrder = array_flip($ids);

        $models = $model->getScoutModelsByIds(
            $builder,
            $ids
        )
            ->map(function ($model) use ($hits) {

                $hit = $hits[$model->id];

                $model->withScoutMetadata('_score', (float) $hit['_score']);
                $model->withScoutMetadata('_id', $hit['_id']);
                $model->withScoutMetadata('_source', $hit['_source']);

                return $model;
            })
            ->sortBy(fn($model) => $idsOrder[$model->getScoutKey()])
            ->values();

        return $models;
    }

    /**
     * @param JsonResponse $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return dot($results->getData(true))->get('hits.total.value');
    }

    public function flush($model)
    {
        $indexName = config('scout.prefix') . $model->getTable();

        $this->sigmie->collect($indexName)->clear();
    }
}
