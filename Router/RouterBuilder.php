<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Http\Core\Router;

use Berlioz\Config\Config;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\Attribute;
use Berlioz\Router\Exception\RoutingException;
use Berlioz\Router\Route;
use Berlioz\Router\Router;
use Berlioz\Router\RouteSetInterface;
use ReflectionAttribute as RAttribute;
use ReflectionClass as RClass;
use ReflectionException;
use ReflectionMethod as RMethod;

/**
 * Class RouterBuilder.
 */
class RouterBuilder
{
    protected Router $router;

    public function __construct(protected Config $config)
    {
        $this->reset();
    }

    /**
     * Reset.
     */
    public function reset(): void
    {
        $this->router = new Router(options: (array)$this->config->get('berlioz.router', []));
    }

    /**
     * Get router.
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Add routes from controllers.
     *
     * @throws BerliozException
     * @throws ConfigException
     */
    public function addRoutesFromControllers(): void
    {
        foreach ($this->config->get('controllers', []) as $controller) {
            if (!is_string($controller)) {
                throw new BerliozException('Controller must be a class name');
            }

            if (false === class_exists($controller)) {
                throw new BerliozException(sprintf('Controller "%s" class does not exists', $controller));
            }

            try {
                $rClass = new RClass($controller);
            } catch (ReflectionException $exception) {
                throw new BerliozException('Controller reflection error', 0, $exception);
            }

            // Find a route group
            $routeGroup = null;
            $attributes = $rClass->getAttributes(Attribute\RouteGroup::class, RAttribute::IS_INSTANCEOF);
            if (count($attributes) === 1) {
                /** @var Route $routeGroup */
                $routeGroup = $attributes[0]->newInstance()->getRouteGroup();
            }

            foreach ($rClass->getMethods(RMethod::IS_STATIC | RMethod::IS_PUBLIC) as $rMethod) {
                $attributes = $rMethod->getAttributes(Attribute\Route::class, RAttribute::IS_INSTANCEOF);

                // Instantiate routes
                $routes = array_map(
                    fn(RAttribute $rAttr) => $rAttr->newInstance()->getRoute([$rMethod->class, $rMethod->name]),
                    $attributes
                );

                // Add routes
                ($routeGroup ?? $this->router)->addRoute(...$routes);
            }

            if (null !== $routeGroup && count($routeGroup) > 0) {
                $this->router->addRoute($routeGroup);
            }
        }
    }

    /**
     * Add routes from config.
     *
     * @throws ConfigException
     * @throws RoutingException
     */
    public function addRoutesFromConfig(): void
    {
        $this->createRoutesFromArray($this->config->get('routes', []), $this->router);
    }

    /**
     * Create routes from an array.
     *
     * @param array $routes
     * @param RouteSetInterface $parent
     *
     * @throws RoutingException
     */
    protected function createRoutesFromArray(array $routes, RouteSetInterface $parent): void
    {
        foreach ($routes as $routeConfig) {
            // Get children
            $children = $routeConfig['routes'] ?? [];
            unset($routeConfig['children']);

            $parent->addRoute($route = new Route(...$routeConfig));

            $this->createRoutesFromArray($children, $route);
        }
    }
}