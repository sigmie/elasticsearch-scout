<?php

namespace Sigmie\ElasticsearchScout;

use Illuminate\Support\Facades\Facade;

class ElasticsearchScoutFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'elasticsearch-scout';
    }
}
