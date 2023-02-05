<?php

use Illuminate\Support\Facades\Route;
use Sigmie\ElasticsearchScout\Http\Controllers\Search;

Route::post('/elasticsearch-scout/search', Search::class);
