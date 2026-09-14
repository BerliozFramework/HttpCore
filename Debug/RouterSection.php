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
use Stringable;

/**
 * Class Router.
 */
class RouterSection extends AbstractSection implements Section, Stringable
{
    protected ?ServerRequestInterface $serverRequest = null;
    /** @var array{statusCode: int, reasonPhrase: string, protocolVersion: string, headers: array<string, string[]>}|null */
    protected ?array $responseInfo = null;
    protected ?RouteInterface $route = null;
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
        return 'HTTP / Router';
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
        $this->responseInfo = $this->app->getResponseInfo();
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
            'responseInfo' => $this->responseInfo,
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
        $this->responseInfo = $data['responseInfo'] ?? null;
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
     * Get captured response metadata.
     *
     * @return array{statusCode: int, reasonPhrase: string, protocolVersion: string, headers: array<string, string[]>}|null
     */
    public function getResponseInfo(): ?array
    {
        return $this->responseInfo;
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
