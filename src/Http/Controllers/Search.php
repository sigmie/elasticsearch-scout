<?php

declare(strict_types=1);

namespace Sigmie\ElasticsearchScout\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Sigmie\ElasticsearchScout\ElasticsearchScout;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

use function Sigmie\Functions\await;

class Search
{
    public function __invoke(Request $request, Sigmie $sigmie)
    {
        $aliases = Str::of($request->json('models'))->explode(',');

        $promises = collect($aliases)
            ->map(fn ($alias) => ElasticsearchScout::$aliases[$alias] ?? '')
            ->filter(fn ($model) => $model !== '')
            ->map(function ($model) use ($request, $sigmie) {
                $model = (new $model);

                $indexName = config('scout.prefix') . $model->getTable();

                $properties = new NewProperties;
                $model->elasticsearchProperties($properties);

                $newSearch = $sigmie
                    ->newSearch($indexName)
                    ->properties($properties)
                    ->queryString($request->get('query', '') ?? '');

                $model->elasticsearchSearch($newSearch);

                return $newSearch
                    ->size($request->get('per_page', 15))
                    ->from(0)
                    ->promise();
            })->toArray();

        $responses = await($promises);

        $results = collect($responses)->map(
            fn ($response) => $response['value']->json('hits')
        );

        $models = collect($aliases)->combine($results);

        return response()->json($models->toArray());
    }
}
