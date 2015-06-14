## Schema Wireframe

This package was created to quickly stub/frame out controllers, models, and
views in a CRUD fashion.

## Instalation

Via Composer Require

```
composer require develme/schema-wireframe --dev
```

## Configuration

Configure schema connection settings withdin config/database.php, so the
package knows how to get table information from MySQL. This connection has to
be named schema.

```php
    //...
	'connections' => [
    //...
        'schema' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'database'  => 'information_schema',
            'username'  => env('DB_USERNAME', 'forge'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],
    //...
```

Make sure to register the service provider within config/app.php

```php
// ...
/*
 * Third Party Service Providers...
 */
'DevelMe\SchemaServiceProvider',
// ...
```

## Generating Files

To generate a controller, you would type:

```

