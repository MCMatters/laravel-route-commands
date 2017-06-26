<?php

declare(strict_types = 1);

namespace McMatters\LaravelRouteCommands;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use McMatters\LaravelRouteCommands\Console\Commands\Check;

/**
 * Class ServiceProvider
 *
 * @package McMatters\LaravelRouteCommands
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.route.check', function ($app) {
            return new Check($app['router'], $app);
        });

        $this->commands([
            'command.route.check',
        ]);
    }
}
