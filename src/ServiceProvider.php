<?php

declare(strict_types = 1);

namespace McMatters\RouteCommands;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use McMatters\RouteCommands\Console\Commands\Check;
use McMatters\RouteCommands\Console\Commands\Export;

/**
 * Class ServiceProvider
 *
 * @package McMatters\RouteCommands
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

        $this->app->singleton('command.route.export', function ($app) {
            return new Export($app['router']);
        });

        $this->commands([
            'command.route.check',
            'command.route.export',
        ]);
    }
}
