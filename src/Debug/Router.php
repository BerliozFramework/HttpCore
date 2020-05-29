<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HttpCore\Debug;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Debug\AbstractSection;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\RouterInterface;
use Berlioz\Router\RouteSetInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Router.
 *
 * @package Berlioz\HttpCore\Debug
 */
class Router extends AbstractSection implements CoreAwareInterface, Section
{
    use CoreAwareTrait;

    /** @var ServerRequestInterface Server request */
    protected $serverRequest;
    /** @var RouteInterface Route */
    protected $route;
    /** @var RouteSetInterface Route set */
    protected $routeSet;

    /**
     * Debug Router constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->setCore($core);
    }

    /////////////////////////
    /// SECTION INTERFACE ///
    /////////////////////////

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return var_export($this, true);
    }

    /**
     * @inheritdoc
     * @throws BerliozException
     */
    public function saveReport()
    {
        if (null === $this->getCore()) {
            return;
        }

        if (!$this->getCore()->getServiceContainer()->has(RouterInterface::class)) {
            return;
        }

        /** @var RouterInterface $router */
        $router = $this->getCore()->getServiceContainer()->get(RouterInterface::class);
        $this->serverRequest = $router->getServerRequest();
        $this->routeSet = $router->getRouteSet();

        if ($this->getCore()->getServiceContainer()->has(HttpApp::class)) {
            /** @var HttpApp $httpApp */
            $httpApp = $this->getCore()->getServiceContainer()->get(HttpApp::class);
            $this->route = $httpApp->getRoute();
        }
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
     * @inheritdoc
     */
    public function getTemplateName(): string
    {
        return '@Berlioz-HttpCore/Twig/Debug/router.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(
            [
                'serverRequest' => $this->getServerRequest(),
                'route' => $this->getRoute(),
                'routeSet' => $this->getRouteSet(),
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->serverRequest = $unserialized['serverRequest'] ?? null;
        $this->route = $unserialized['route'] ?? null;
        $this->routeSet = $unserialized['routeSet'] ?? null;
    }

    ////////////////////
    /// USER DEFINED ///
    ////////////////////

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
     * Get route set.
     *
     * @return RouteSetInterface|null
     */
    public function getRouteSet(): ?RouteSetInterface
    {
        return $this->routeSet;
    }
}