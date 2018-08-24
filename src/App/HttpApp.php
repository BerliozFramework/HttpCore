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

namespace Berlioz\HttpCore\App;

use Berlioz\Core\App\AbstractApp;
use Berlioz\Core\Config;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Exception\CacheException;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\Stream;
use Berlioz\HttpCore\Controller\DebugController;
use Berlioz\HttpCore\Debug\Router as DebugRouter;
use Berlioz\HttpCore\Exception\HttpException;
use Berlioz\HttpCore\Exception\Http\InternalServerErrorHttpException;
use Berlioz\HttpCore\Exception\Http\NotFoundHttpException;
use Berlioz\HttpCore\Exception\RoutingException;
use Berlioz\HttpCore\Http\DefaultHttpErrorHandler;
use Berlioz\HttpCore\Http\HttpErrorHandler;
use Berlioz\Router\RouteGenerator;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\RouterInterface;
use Berlioz\Router\RouteSetInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

class HttpApp extends AbstractApp
{
    const CACHE_ROUTER_KEY = '_BERLIOZ_ROUTER';
    /** @var bool Router initialized? */
    private $routerInitialized;
    /** @var \Berlioz\Router\RouteInterface|null Current route */
    private $route;

    /**
     * HttpApp destructor.
     */
    public function __destruct()
    {
        if ($this->getDebug()->isEnabled()) {
            $this->getDebug()->addSection(new DebugRouter($this));
        }

        parent::__destruct();
    }

    //////////////
    /// CONFIG ///
    //////////////

    /**
     * @inheritdoc
     */
    public function getConfig(): ?Config
    {
        if (!$this->isConfigInitialized()) {
            parent::getConfig()->extendsJson(implode(DIRECTORY_SEPARATOR,
                                                     [__DIR__, '..', '..', 'resources', 'config.default.json']),
                                             true,
                                             true);
        }

        return parent::getConfig();
    }

    //////////////
    /// ROUTER ///
    //////////////

    /**
     * Initialize router.
     *
     * @return \Berlioz\Router\RouterInterface
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getRouter(): RouterInterface
    {
        try {
            if (!$this->routerInitialized) {
                $routerActivity = (new Debug\Activity('Router (initialization)', 'Berlioz'))->start();

                $serviceCacheExists = $this->getServiceContainer()->has(CacheInterface::class);
                /** @var \Berlioz\Router\Router $router */
                $router = $this->getServiceContainer()->get(RouterInterface::class);

                // Get from cache
                if ($serviceCacheExists && ($routeSet = $this->getServiceContainer()->get(CacheInterface::class)->get(self::CACHE_ROUTER_KEY)) instanceof RouteSetInterface) {
                    $router->setRouteSet($routeSet);
                } // Read controllers in configuration
                else {
                    // Get controllers from PHP file
                    $controllers = $this->getConfig()->get('controllers', []);
                    $controllers[] = DebugController::class;

                    if (!empty($controllers)) {
                        $routeGenerator = new RouteGenerator;
                        foreach ($controllers as $controller) {
                            $router->getRouteSet()->merge($routeGenerator->fromClass($controller));
                        }
                    }

                    if ($serviceCacheExists) {
                        $this->getServiceContainer()->get(CacheInterface::class)->set(self::CACHE_ROUTER_KEY, $router->getRouteSet());
                    }
                }

                $this->routerInitialized = true;
                $this->getDebug()->getTimeLine()->addActivity($routerActivity->end());

                return $router;
            }

            return $this->getServiceContainer()->get(RouterInterface::class);
        } catch (\Psr\SimpleCache\CacheException $e) {
            throw new CacheException('Cache error', 0, $e);
        } catch (BerliozException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RoutingException('Routing error', 0, $e);
        }
    }

    /**
     * Get current route.
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    /**
     * Handle application.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $serverRequest Server request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(?ServerRequestInterface $serverRequest = null): ResponseInterface
    {
        try {
            $router = $this->getRouter();

            // Handle router
            $routerActivity = (new Debug\Activity('Router (handle)', 'Berlioz'))->start();
            $this->route = $router->handle($serverRequest);
            $this->getDebug()->getTimeLine()->addActivity($routerActivity->end());

            if (!is_null($this->route)) {
                $routeContext = $this->route->getContext();

                if (!empty($routeContext['_class']) && !empty($routeContext['_method'])) {
                    // Define default locale from request and route (attribute _locale)
                    if (!is_null($locale = $serverRequest->getAttribute('_locale'))) {
                        $this->setLocale($locale);
                    }

                    // Create instance of controller and invoke method
                    try {
                        $response = new Response;
                        $controllerActivity = (new Debug\Activity('Controller'))->start();

                        // Create instance of controller
                        $controller = $this->getServiceContainer()
                                           ->getInstantiator()
                                           ->newInstanceOf($routeContext['_class'],
                                                           ['request'  => $serverRequest,
                                                            'response' => $response]);

                        // Call _b_pre() method?
                        if (method_exists($controller, '_b_pre')) {
                            // Call main method
                            $preResponse = $this->getServiceContainer()
                                                ->getInstantiator()
                                                ->invokeMethod($controller,
                                                               '_b_pre',
                                                               ['request'  => $serverRequest,
                                                                'response' => $response]);

                            if ($preResponse instanceof ResponseInterface) {
                                $response = $preResponse;
                            }
                        }

                        // Call main method only if response code is between 200 and 299
                        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                            $mainResponse = $this->getServiceContainer()
                                                 ->getInstantiator()
                                                 ->invokeMethod($controller,
                                                                $routeContext['_method'],
                                                                ['request'  => $serverRequest,
                                                                 'response' => $response]);

                            if (!$mainResponse instanceof ResponseInterface) {
                                $stream = new Stream;
                                $stream->write($mainResponse);
                                $response = $response->withBody($stream);
                            } else {
                                $response = $mainResponse;
                            }
                        }

                        // Call _b_post() method?
                        if (method_exists($controller, '_b_post')) {
                            // Call main method
                            $postResponse = $this->getServiceContainer()
                                                 ->getInstantiator()
                                                 ->invokeMethod($controller,
                                                                '_b_post',
                                                                ['request'  => $serverRequest,
                                                                 'response' => $response]);

                            if ($postResponse instanceof ResponseInterface) {
                                $response = $postResponse;
                            }
                        }
                    } finally {
                        $this->getDebug()->getTimeLine()->addActivity($controllerActivity->end());
                    }
                } else {
                    throw new InternalServerErrorHttpException;
                }
            } else {
                throw new NotFoundHttpException;
            }
        } catch (\Throwable $e) {
            if (!($e instanceof HttpException)) {
                $e = new InternalServerErrorHttpException(null, $e);
            }

            $response = $this->httpErrorHandler($e);
        }

        // Debug?
        if ($this->getDebug()->isEnabled()) {
            $response = $response->withAddedHeader('X-Berlioz-Debug', $this->getDebug()->getUniqid());
        }

        return $response;
    }

    /**
     * HTTP error handler.
     *
     * @param \Berlioz\HttpCore\Exception\HttpException $e
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function httpErrorHandler(HttpException $e): ResponseInterface
    {
        try {
            // Get error handler in configuration
            $errorHandler = $this->getConfig()->get(sprintf('berlioz.http.errors.%s', $e->getCode()),
                                                    $this->getConfig()->get('berlioz.http.errors.default'));

            // Check validity of error handler
            if (empty($errorHandler) || !class_exists($errorHandler) || !is_a($errorHandler, HttpErrorHandler::class, true)) {
                $errorHandler = DefaultHttpErrorHandler::class;
            }

            // Invoke method of error handler
            $handler = $this->getServiceContainer()
                            ->getInstantiator()
                            ->newInstanceOf($errorHandler);
            $response = $this->getServiceContainer()
                             ->getInstantiator()
                             ->invokeMethod($handler,
                                            'handle',
                                            ['request' => $this->getRouter()->getServerRequest(),
                                             'e'       => $e]);
        } catch (\Throwable $throwable) {
            try {
                $handler = new DefaultHttpErrorHandler($this);
                $response = $handler->handle($this->getRouter()->getServerRequest(), $e);
            } catch (\Throwable $throwable) {
                $str = "<html>" .
                       "<body>" .
                       "<h1>Internal Server Error</h1>";

                try {
                    $debug = false;
                    if ($debug = $this->getConfig()->get('berlioz.debug', false)) {
                        $str .= "<pre>{$e}</pre>";
                    }
                } catch (\Throwable $throwable) {
                }

                if (!$debug) {
                    $str .= "<p>Looks like we're having some server issues.</p>";
                }

                $str .= "</body>" .
                        "</html>";

                $response = new Response($str, 500);
            }
        }

        return $response;
    }

    /**
     * Print ResponseInterface object.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function printResponse(ResponseInterface $response)
    {
        $printActivity = (new Debug\Activity('Print response', 'Berlioz'))->start();

        // Headers
        if (!headers_sent()) {
            // Remove headers and add main header
            header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase(), true);

            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                $replace = true;
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), $replace);
                    $replace = false;
                }
            }
        }

        // Content
        print $response->getBody();

        // Debug
        $this->getDebug()->getTimeLine()->addActivity($printActivity->end());
    }
}