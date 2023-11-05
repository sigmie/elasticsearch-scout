<?php

namespace Sigmie\ElasticsearchScout\Facades;

use Illuminate\Support\Facades\Facade;

class Sigmie extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sigmie';
    }
}
