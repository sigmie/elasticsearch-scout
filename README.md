# Laravel Scout adapter for Elasticsearch

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sigmie/elasticsearch-scout.svg?style=flat-square)](https://packagist.org/packages/sigmie/elasticsearch-scout)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/sigmie/elasticsearch-scout/run-tests?label=tests)](https://github.com/sigmie/elasticsearch-scout/actions?query=workflow%3ATests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/sigmie/elasticsearch-scout.svg?style=flat-square)](https://packagist.org/packages/sigmie/elasticsearch-scout)


This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require sigmie/elasticsearch-scout
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Sigmie\ElasticsearchScout\ElasticsearchScoutServiceProvider" --tag="elasticsearch-scout-migrations"

php artisan migrate
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Sigmie\ElasticsearchScout\ElasticsearchScoutServiceProvider" --tag="elasticsearch-scout-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
 
class Movie extends Model
{
    use Searchable;
 
    public function elasticsearchProperties(NewProperties $properties) 
    {
        $properties->title('title');
        $properties->name('director');
        $properties->category();
        $properties->date('created_at');
        $properties->date('updated_at');
    } 
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [nicoorfi](https://github.com/nicoorfi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
