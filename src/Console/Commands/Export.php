<?php

declare(strict_types = 1);

namespace McMatters\RouteCommands\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SimpleXMLElement;

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
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->routes = $router->getRoutes();

        parent::__construct();
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function fire()
    {
        if (count($this->routes) === 0) {
            $this->error('Your application does not have any routes.');
        } else {
            $this->export($this->getRoutes());
        }
    }

    /**
     * @param array $data
     *
     * @throws InvalidArgumentException
     */
    protected function export(array $data)
    {
        $type = $this->option('type') ?: 'json';
        $this->checkType($type);

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
        $optionMethods = array_filter(explode(',', $this->option('methods') ?? ''));

        /** @var Route $route */
        foreach ($this->routes as $route) {
            if ($optionMethods) {
                $skip = true;
                $methods = Str::lower(implode('|', $route->methods()));

                foreach ($optionMethods as $method) {
                    if (preg_match("/\|?{$method}\|?/", $methods)) {
                        $skip = false;
                        break;
                    }
                }

                if ($skip) {
                    continue;
                }
            }

            $uri = $route->uri();

            if (!in_array($uri, $routes, true)) {
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
     * @throws InvalidArgumentException
     */
    protected function checkType(string $type)
    {
        if (!in_array($type, ['json', 'xml'], true)) {
            throw new InvalidArgumentException('Supported types are only "json" and "xml"');
        }
    }
}
