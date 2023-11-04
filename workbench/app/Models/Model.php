<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;

class Model extends BaseModel
{
    use HasFactory;

    protected $guarded = [];
}
