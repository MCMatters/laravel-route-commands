## Laravel Route Commands

Package with laravel route commands.

### Installation

```bash
composer require mcmatters/laravel-route-commands
```

Include the service provider within your `config/app.php` file.

```php
'providers' => [
    McMatters\RouteCommands\ServiceProvider::class,
]
```

## Usage

Available commands:

* `php artisan route:check` — checks all routes for existing the methods in controller and unique route names.
* `php artisan route:export` — export all routes to json or xml file.
