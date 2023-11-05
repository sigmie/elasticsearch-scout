<?php

namespace Sigmie\ElasticsearchScout\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Sigmie\Search\NewIndex newIndex(string $name)
 * @method static null|\Sigmie\Search\AliasedIndex|\Sigmie\Search\Index index(string $name)
 * @method static \Sigmie\Search\AliveCollection collect(string $name, bool $refresh = false)
 * @method static \Sigmie\Search\NewQuery newQuery(string $index)
 * @method static \Sigmie\Search\NewSearch newSearch(string $index)
 * @method static \Sigmie\Search\NewTemplate newTemplate(string $id)
 * @method static void refresh(string $indexName)
 * @method static \Sigmie\Search\ExistingScript template(string $id)
 * @method static array indices(string $pattern = '*')
 * @method static bool isConnected()
 * @method static \Sigmie\Sigmie create(array|string $hosts, array $config = [])
 * @method static bool delete(string $index)
 *
 * @see \Sigmie\Sigmie
 *
 * @mixin \Sigmie\Sigmie
 */
class Sigmie extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sigmie';
    }
}
