<?php

namespace Sigmie\ElasticsearchScout;

class ElasticsearchScout
{
    public static $aliases = [];

    public static function aliases(array $aliases)
    {
        static::$aliases = collect(array_merge(static::$aliases, $aliases))
            ->unique()
            ->all();
    }
}
