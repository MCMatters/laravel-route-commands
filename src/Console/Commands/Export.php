<?php

declare(strict_types=1);

namespace McMatters\RouteCommands\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SimpleXMLElement;

use function array_filter, array_intersect, array_map, count, explode, json_encode, rtrim, ucfirst;

use const true;

/**
 * Class Export
 *
 * @package McMatters\RouteCommands\Console\Commands
 */
class Export extends Command
{
    /**
     * @var string
     */
    protected $signature = 'route:export
    { --path= : Path where file must be saved }
    { --name= : Filename }
    { --type= : Extension of the file to be saved }
    { --methods= : Filter routes by methods comma separated }';

    /**
     * @var array
     */
    protected $routes;

    /**
     * Export constructor.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function __construct(Router $router)
    {
        $this->routes = $router->getRoutes();

        parent::__construct();
    }

    /**
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public function handle(): int
    {
        if (count($this->routes) === 0) {
            $this->error('Your application does not have any routes.');

            return 1;
        }

        $this->export($this->getRoutes());

        return 0;
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function export(array $data)
    {
        $this->checkType($type = $this->option('type') ?: 'json');

        $file = $this->option('name') ?: 'routes';
        $path = rtrim(
            $this->option('path') ?: $this->getLaravel()->storagePath().'/app',
            '/'
        );
        $path .= "/{$file}.{$type}";

        $method = 'to'.ucfirst($type);

        File::put($path, $this->$method($data));
    }

    /**
     * @return array
     */
    protected function getRoutes(): array
    {
        $routes = [];
        $optionMethods = array_map(
            static function ($method) {
                return Str::upper($method);
            },
            array_filter(explode(',', $this->option('methods') ?? ''))
        );

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->routes as $route) {
            if ($optionMethods && empty(array_intersect($route->methods(), $optionMethods))) {
                continue;
            }

            if (!in_array($uri = $route->uri(), $routes, true)) {
                $routes[] = $uri;
            }
        }

        return $routes;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function toJson(array $data): string
    {
        return json_encode($data);
    }

    /**
     * @param array $data
     *
     * @return string|bool
     */
    protected function toXml(array $data)
    {
        $xml = new SimpleXMLElement('<routes/>');

        foreach ($data as $item) {
            $xml->addChild('route', $item);
        }

        return $xml->asXML();
    }

    /**
     * @param string $type
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function checkType(string $type)
    {
        if (!in_array($type, ['json', 'xml'], true)) {
            throw new InvalidArgumentException('Supported types are only "json" and "xml"');
        }
    }
}
