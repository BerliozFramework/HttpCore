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

namespace Berlioz\HttpCore\App;

use Berlioz\Config\ConfigInterface;
use Berlioz\Core\App\AbstractApp;
use Berlioz\Core\Config;
use Berlioz\Core\Debug;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\Stream;
use Berlioz\HttpCore\Controller\DebugController;
use Berlioz\HttpCore\Debug\Router as DebugRouter;
use Berlioz\HttpCore\Exception\HttpException;
use Berlioz\HttpCore\Exception\InternalServerErrorHttpException;
use Berlioz\HttpCore\Exception\NotFoundHttpException;
use Berlioz\HttpCore\Http\DefaultHttpErrorHandler;
use Berlioz\HttpCore\Http\HttpErrorHandler;
use Berlioz\Router\RouteGenerator;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\RouterInterface;
use Berlioz\Router\RouteSetInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class HttpApp extends AbstractApp
{
    const CACHE_ROUTER_KEY = '_BERLIOZ_ROUTER';
    /** @var bool Router initialized? */
    private $routerInitialized;
    /** @var \Berlioz\Router\RouteInterface|null Current route */
    private $route;

    public function __destruct()
    {
        if ($this->getDebug()->isEnabled()) {
            $this->getDebug()->addSection(new DebugRouter($this));
        }

        parent::__destruct();
    }

    /**
     * Get configuration.
     *
     * @return \Berlioz\Config\ConfigInterface
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getConfig(): ConfigInterface
    {
        if (!$this->hasConfig()) {
            $configActivity = (new Debug\Activity('Config (initialization)', 'Berlioz'))->start();

            // Create configuration
            $config = new Config(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'resources', 'config.default.json']), true);
            $config->setVariable('berlioz.directories.http-core', implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..']));

            // Search website config and add
            $domain = $_SERVER['HTTP_HOST'] ?? null;
            $configDirectory = implode(DIRECTORY_SEPARATOR, [$this->getAppDir(), 'etc']);

            if ((!is_null($domain) && file_exists($configFile = sprintf('%s%sconfig.%s.json', $configDirectory, DIRECTORY_SEPARATOR, $domain))) ||
                (file_exists($configFile = sprintf('%s%sconfig.json', $configDirectory, DIRECTORY_SEPARATOR)))) {
                $config->extendsJson($configFile, true, false);
            }

            $this->getDebug()->getTimeLine()->addActivity($configActivity->end());

            // Set config to parent
            $this->setConfig($config);
        }

        return parent::getConfig();
    }

    /**
     * Initialize router.
     *
     * @return \Berlioz\Router\RouterInterface
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\Router\Exception\RoutingException
     * @throws \Psr\SimpleCache\CacheException
     */
    public function getRouter(): RouterInterface
    {
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
                $controllers = @include (implode(DIRECTORY_SEPARATOR, [$this->getAppDir(), 'etc', 'controllers.php'])) ?? [];
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Psr\SimpleCache\CacheException
     */
    public function handle(): ResponseInterface
    {
        try {
            $router = $this->getRouter();

            /** @var \Psr\Http\Message\ServerRequestInterface|null $serverRequest */
            $serverRequest = null;

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
                        $controller = $this->getServiceContainer()->newInstanceOf($routeContext['_class'],
                                                                                  ['request'  => $serverRequest,
                                                                                   'response' => $response]);

                        // Call _b_pre() method?
                        if (method_exists($controller, '_b_pre')) {
                            // Call main method
                            $preResponse = $this->getServiceContainer()->invokeMethod($controller,
                                                                                      '_b_pre',
                                                                                      ['request'  => $serverRequest,
                                                                                       'response' => $response]);

                            if ($preResponse instanceof ResponseInterface) {
                                $response = $preResponse;
                            }
                        }

                        // Call main method only if response code is between 200 and 299
                        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                            $mainResponse = $this->getServiceContainer()->invokeMethod($controller,
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
                            $postResponse = $this->getServiceContainer()->invokeMethod($controller,
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
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Psr\SimpleCache\CacheException
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
            $handler = $this->getServiceContainer()->newInstanceOf($errorHandler);
            $response = $this->getServiceContainer()->invokeMethod($handler,
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
                if ($this->getConfig()->get('berlioz.debug', false)) {
                    $str .= "<pre>{$e}</pre>";
                } else {
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