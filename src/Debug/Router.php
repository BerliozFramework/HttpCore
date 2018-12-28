<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
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
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\Router\RouterInterface;

/**
 * Class Router.
 *
 * @package Berlioz\HttpCore\Debug
 */
class Router extends AbstractSection implements CoreAwareInterface, Section
{
    use CoreAwareTrait;
    /** @var \Psr\Http\Message\ServerRequestInterface Server request */
    protected $serverRequest;
    /** @var \Berlioz\Router\RouteInterface Route */
    protected $route;
    /** @var \Berlioz\Router\RouteSetInterface Route set */
    protected $routeSet;

    /**
     * Debug Router constructor.
     *
     * @param \Berlioz\Core\Core $core
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
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function saveReport()
    {
        if (is_null($this->getCore())) {
            return;
        }

        if (!$this->getCore()->getServiceContainer()->has(RouterInterface::class)) {
            return;
        }

        /** @var \Berlioz\Router\RouterInterface $router */
        $router = $this->getCore()->getServiceContainer()->get(RouterInterface::class);
        $this->serverRequest = $router->getServerRequest();
        $this->routeSet = $router->getRouteSet();

        if ($this->getCore()->getServiceContainer()->has(HttpApp::class)) {
            /** @var \Berlioz\HttpCore\App\HttpApp $httpApp */
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
        return serialize(['serverRequest' => $this->getServerRequest(),
                          'route'         => $this->getRoute(),
                          'routeSet'      => $this->getRouteSet()]);
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
     * @return \Psr\Http\Message\ServerRequestInterface|null
     */
    public function getServerRequest(): ?\Psr\Http\Message\ServerRequestInterface
    {
        return $this->serverRequest;
    }

    /**
     * Get route.
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function getRoute(): ?\Berlioz\Router\RouteInterface
    {
        return $this->route;
    }

    /**
     * Get route set.
     *
     * @return \Berlioz\Router\RouteSetInterface|null
     */
    public function getRouteSet(): ?\Berlioz\Router\RouteSetInterface
    {
        return $this->routeSet;
    }
}