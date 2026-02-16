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

namespace Berlioz\Http\Core\Debug;

use Berlioz\Core\Debug\AbstractSection;
use Berlioz\Core\Debug\DebugHandler;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Router\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Router.
 */
class RouterSection extends AbstractSection implements Section
{
    protected ?ServerRequestInterface $serverRequest;
    protected ?RouteInterface $route;
    protected array $routes;

    /**
     * Debug Router constructor.
     */
    public function __construct(protected HttpApp $app)
    {
    }

    /**
     * Get section name.
     *
     * @return string
     */
    public function getSectionName(): string
    {
        return 'Router';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return '@Berlioz-HttpCore/Twig/Debug/router.html.twig';
    }

    /**
     * @inheritDoc
     */
    public function snap(DebugHandler $debug): void
    {
        $this->serverRequest = $this->app->getRequest();
        $this->route = $this->app->getRoute();
        $this->routes = iterator_to_array($this->app->getRouter()->getRoutes(), false);
    }

    /////////////////////////
    /// SECTION INTERFACE ///
    /////////////////////////

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return var_export($this, true);
    }

    /**
     * PHP serialize method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'serverRequest' => $this->serverRequest,
            'route' => $this->route,
            'routes' => $this->routes,
        ];
    }

    /**
     * PHP unserialize method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->serverRequest = $data['serverRequest'] ?? null;
        $this->route = $data['route'] ?? null;
        $this->routes = $data['routes'] ?? [];
    }

    /**
     * Get server request.
     *
     * @return ServerRequestInterface|null
     */
    public function getServerRequest(): ?ServerRequestInterface
    {
        return $this->serverRequest;
    }

    /**
     * Get route.
     *
     * @return RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    /**
     * Get routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}