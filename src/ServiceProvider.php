<?php

declare(strict_types = 1);

namespace McMatters\RouteCommands;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use McMatters\RouteCommands\Console\Commands\Check;

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

        $this->commands([
            'command.route.check',
        ]);
    }
}
