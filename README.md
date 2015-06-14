## Schema Wireframe

This package was created to quickly stub/frame out controllers, models, and
views in a CRUD fashion.

## Installation

Via Composer Require

```bash
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

This process should be done after you run your migrations. It pulls table and
column information from MySQL's information\_schema database.

### Controllers
To generate a controller, you would type:

```bash
php artisan make:schema-controller <controller class name> [--table=<table name>] [--model=<model class name>]
```

Example:

```bash
php artisan make:schema-controller Admin\\User --table="users" --model="App\\User"
```

Please note that when providing namespaces, include two back slashes (\\)
Also note that when providing the model class, the root namespace should also
be included

### Models
To generate a model, you would type:

```bash
php artisan make:schema-model <model class name> [--table=<table name>]
```

Example:

```bash
php artisan make:schema-model User --table="users" 
```

Please note that the root namespace does not have to be provided here 

### Views
To generate a view, you would type:

```bash
php artisan make:schema-view <model class name> [--table=<table name>] [--theme=<bootstrap|foundation>] [--path=<directory>]
```

Example 1:

```bash
php artisan make:schema-view User --table="users" --theme="bootstrap"
```

Example 2:

```bash
php artisan make:schema-view User --table="users" --path="resources/views/example"
```

Please note that the root namespace does not have to be provided here

#### Themes
Currently, only two themes are provided. Bootstrap and Foundation.

Theoretically, you could create a custom theme ad [package root]/src/themes/[theme name]/view
but there's a possibility of composer overwriting those files. Support for
loading views from another location can be added if the need arises.

### MVC Generation
It is possible to generate the model, view, and controller all at once. This is
currently an experimental command as it is somewhat restrictive and incomplete

A resource controller can also be appended to your routes.php file. If you say
yes when the question arises.

For an MVC Generation, you would type:

```bash
php artisan make:schema-app <model class name> <controller class name> [--table=<table name>] [--theme=<bootstrap|foundation>]
```

Example:

```bash
php artisan make:schema-app User Admin\\User --table="users" --theme="bootstrap"
```

