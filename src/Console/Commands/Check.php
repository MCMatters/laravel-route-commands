<?php

declare(strict_types=1);

namespace McMatters\RouteCommands\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

use function count, in_array, is_callable, is_string;

use const null, true;

/**
 * Class Check
 *
 * @package McMatters\RouteCommands\Console\Commands
 */
class Check extends Command
{
    /**
     * @var string
     */
    protected $signature = 'route:check';

    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $headers = ['Domain', 'URI', 'Name', 'Action'];

    /**
     * @var array
     */
    protected $dontCallable = [];

    /**
     * @var array
     */
    protected $names = [];

    /**
     * @var array
     */
    protected $duplicatedNames = [];

    /**
     * Check constructor.
     *
     * @param \Illuminate\Routing\Router $router
     * @param \Illuminate\Container\Container $container
     */
    public function __construct(Router $router, Container $container)
    {
        $this->router = $router;
        $this->container = $container;

        parent::__construct();
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->checkCallable($route);
            $this->checkUniqueNames($route);
        }

        $this->outputIssues();

        return $this->returnCode();
    }

    /**
     * @param \Illuminate\Routing\Route $route
     *
     * @return void
     */
    protected function checkCallable(Route $route)
    {
        $action = $route->getAction();

        if (null === ($action['uses'] ?? null)) {
            $this->dontCallable[] = $route;

            return;
        }

        if (!is_string($action['uses'])) {
            if (is_callable($action['uses'])) {
                return;
            }

            $this->dontCallable[] = $this->getRouteOptions($route);
        }

        $callback = Str::parseCallback($action['uses']);

        if (count($callback) < 2) {
            $this->dontCallable[] = $this->getRouteOptions($route);

            return;
        }

        $controller = $this->container->make($callback[0]);

        if (!is_callable([$controller, $callback[1]])) {
            $this->dontCallable[] = $this->getRouteOptions($route);
        }
    }

    /**
     * @param \Illuminate\Routing\Route $route
     *
     * @return void
     */
    protected function checkUniqueNames(Route $route)
    {
        $name = $route->getName();

        if (null === $name) {
            return;
        }

        if (!in_array($name, $this->names, true)) {
            $this->names[] = $name;
        } else {
            $this->duplicatedNames[] = $this->getRouteOptions($route);
        }
    }

    /**
     * @param \Illuminate\Routing\Route $route
     *
     * @return array
     */
    protected function getRouteOptions(Route $route): array
    {
        return [
            $route->domain(),
            $route->uri(),
            $route->getName(),
            $route->getActionName(),
        ];
    }

    /**
     * @return void
     */
    protected function outputIssues()
    {
        if (!$this->hasErrors()) {
            $this->output->success('There are no problems.');

            return;
        }

        if (!empty($this->dontCallable)) {
            $this->output->note('Don\'t callable:');
            $this->table($this->headers, $this->dontCallable);
        }

        if (!empty($this->duplicatedNames)) {
            $this->output->note('Duplicated names:');
            $this->table($this->headers, $this->duplicatedNames);
        }
    }

    /**
     * @return bool
     */
    protected function hasErrors(): bool
    {
        return !empty($this->dontCallable) || !empty($this->duplicatedNames);
    }

    /**
     * @return int
     */
    protected function returnCode(): int
    {
        if ($this->hasErrors()) {
            return 1;
        }

        return 0;
    }
}
