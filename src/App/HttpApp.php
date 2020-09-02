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

namespace Berlioz\HttpCore\App;

use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\App\AbstractApp;
use Berlioz\Core\Core;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\Stream;
use Berlioz\HttpCore\Debug\Router as DebugRouter;
use Berlioz\HttpCore\Exception\Http\InternalServerErrorHttpException;
use Berlioz\HttpCore\Exception\Http\NotFoundHttpException;
use Berlioz\HttpCore\Exception\Http\ServiceUnavailableHttpException;
use Berlioz\HttpCore\Exception\HttpException;
use Berlioz\HttpCore\Http\DefaultHttpErrorHandler;
use Berlioz\HttpCore\Http\HttpErrorHandler;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\RouterInterface;
use Berlioz\ServiceContainer\Exception\ContainerException;
use Berlioz\ServiceContainer\Service;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class HttpApp.
 *
 * @package Berlioz\HttpCore\App
 */
class HttpApp extends AbstractApp implements RequestHandlerInterface
{
    /** @var RouteInterface|null Current route */
    private $route;

    /**
     * HttpApp constructor.
     *
     * @param Core|null $core
     *
     * @throws BerliozException
     * @throws ContainerException
     */
    public function __construct(?Core $core = null)
    {
        parent::__construct($core);

        $this->getCore()->onTerminate(
            function (Core $core) {
                if ($core->getDebug()->isEnabled()) {
                    $core->getDebug()->addSection(new DebugRouter($core));
                }
            }
        );
    }

    //////////////
    /// ROUTER ///
    //////////////

    /**
     * Initialize router.
     *
     * @return RouterInterface
     * @throws BerliozException
     */
    public function getRouter(): RouterInterface
    {
        return $this->getService(RouterInterface::class);
    }

    /**
     * Get current route.
     *
     * @return RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    ///////////////
    /// HANDLER ///
    ///////////////

    /**
     * Handle application.
     *
     * @param ServerRequestInterface $serverRequest Server request
     *
     * @return ResponseInterface
     * @throws BerliozException
     */
    public function handle(?ServerRequestInterface $serverRequest = null): ResponseInterface
    {
        try {
            // Check if application is in maintenance mode
            if ($this->getCore()->getConfig()->get('berlioz.maintenance', false) &&
                !$this->getCore()->getDebug()->isEnabled()) {
                throw new ServiceUnavailableHttpException();
            }

            $router = $this->getRouter();

            // Handle router
            $routerActivity = (new Debug\Activity('Router (handle)', 'Berlioz'))->start();
            $this->route = $router->handle($serverRequest);
            $this->getCore()->getDebug()->getTimeLine()->addActivity($routerActivity->end());

            // No route?
            if (null === $this->route) {
                if (null === ($response = $this->httpRedirection($serverRequest->getUri()))) {
                    throw new NotFoundHttpException;
                }

                return $response;
            }

            $routeContext = $this->route->getContext();

            // No controller?
            if (empty($routeContext['_class']) || empty($routeContext['_method'])) {
                throw new InternalServerErrorHttpException;
            }

            // Define default locale from request and route (attribute _locale)
            if (null !== $locale = $serverRequest->getAttribute('_locale')) {
                $this->getCore()->setLocale($locale);
            }

            // Add server request to the service container
            $this->getCore()->getServiceContainer()->add(new Service($serverRequest));

            // Create instance of controller and invoke method
            try {
                $response = new Response;
                $controllerActivity = (new Debug\Activity('Controller'))->start();

                // Create instance of controller
                $controller = $this->getCore()->getServiceContainer()
                    ->getInstantiator()
                    ->newInstanceOf(
                        $routeContext['_class'],
                        [
                            'request' => $serverRequest,
                            'response' => $response,
                        ]
                    );

                // Call _b_pre() method?
                if (method_exists($controller, '_b_pre')) {
                    // Call main method
                    $preResult = $this->getCore()->getServiceContainer()
                        ->getInstantiator()
                        ->invokeMethod(
                            $controller,
                            '_b_pre',
                            [
                                'request' => $serverRequest,
                                'response' => $response,
                            ]
                        );

                    if ($preResult instanceof ServerRequestInterface) {
                        $serverRequest = $preResult;
                    }
                    if ($preResult instanceof ResponseInterface) {
                        $response = $preResult;
                    }
                }

                // Call main method only if response code is between 200 and 299
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $mainResponse = $this->getCore()->getServiceContainer()
                        ->getInstantiator()
                        ->invokeMethod(
                            $controller,
                            $routeContext['_method'],
                            [
                                'request' => $serverRequest,
                                'response' => $response,
                            ]
                        );

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
                    $postResponse = $this->getCore()->getServiceContainer()
                        ->getInstantiator()
                        ->invokeMethod(
                            $controller,
                            '_b_post',
                            [
                                'request' => $serverRequest,
                                'response' => $response,
                            ]
                        );

                    if ($postResponse instanceof ResponseInterface) {
                        $response = $postResponse;
                    }
                }
            } finally {
                $this->getCore()->getDebug()->getTimeLine()->addActivity($controllerActivity->end());
            }
        } catch (Throwable $e) {
            $this->getCore()->getDebug()->setExceptionThrown($e);

            if (!($e instanceof HttpException)) {
                $e = new InternalServerErrorHttpException(null, $e);
            }

            $response = $this->httpErrorHandler($e);
        }

        return $response;
    }

    /**
     * HTTP redirection.
     *
     * @param UriInterface $uri
     *
     * @return ResponseInterface|null
     * @throws ConfigException
     */
    public function httpRedirection(UriInterface $uri): ?ResponseInterface
    {
        $redirections = $this->getCore()->getConfig()->get('berlioz.http.redirections', []);

        foreach ($redirections as $origin => $redirection) {
            $matches = [];
            if (!(preg_match(sprintf('#%s#i', $origin), $uri->getPath(), $matches) >= 1)) {
                continue;
            }

            if (is_array($redirection)) {
                $redirectionType = intval($redirection['type'] ?? 301);
                $redirectionUrl = (string)$redirection['url'];
            } else {
                $redirectionType = 301;
                $redirectionUrl = (string)$redirection;
            }

            // Replacement
            $redirectionUrl = preg_replace(sprintf('#%s#i', $origin), $redirectionUrl, $uri->getPath());

            return new Response(null, $redirectionType, ['Location' => $redirectionUrl]);
        }

        return null;
    }

    /**
     * HTTP error handler.
     *
     * @param HttpException $e
     *
     * @return ResponseInterface
     */
    private function httpErrorHandler(HttpException $e): ResponseInterface
    {
        try {
            // Get error handler in configuration
            $errorHandler = $this->getCore()
                ->getConfig()
                ->get(
                    sprintf('berlioz.http.errors.%s', $e->getCode()),
                    $this->getCore()
                        ->getConfig()
                        ->get('berlioz.http.errors.default')
                );

            // Check validity of error handler
            if (empty($errorHandler) || !class_exists($errorHandler) || !is_a(
                    $errorHandler,
                    HttpErrorHandler::class,
                    true
                )) {
                $errorHandler = DefaultHttpErrorHandler::class;
            }

            // Invoke method of error handler
            $handler = $this->getCore()->getServiceContainer()
                ->getInstantiator()
                ->newInstanceOf($errorHandler);
            $response = $this->getCore()->getServiceContainer()
                ->getInstantiator()
                ->invokeMethod(
                    $handler,
                    'handle',
                    [
                        'request' => $this->getRouter()->getServerRequest(),
                        'e' => $e,
                    ]
                );
        } catch (Throwable $throwable) {
            try {
                $handler = new DefaultHttpErrorHandler($this);
                $response = $handler->handle($this->getRouter()->getServerRequest(), $e);
            } catch (Throwable $throwable) {
                $str =
                    '<html lang="en">' .
                    '<body>' .
                    '<h1>Internal Server Error</h1>';

                try {
                    $debug = false;
                    if ($debug = $this->getCore()->getDebug()->isEnabled()) {
                        $str .= '<pre>' . $e . '</pre>';
                    }
                } catch (Throwable $throwable) {
                }

                if (!$debug) {
                    $str .= '<p>Looks like we\'re having some server issues.</p>';
                }

                $str .=
                    '</body>' .
                    '</html>';

                $response = new Response($str, 500);
            }
        }

        return $response;
    }

    /**
     * Print ResponseInterface object.
     *
     * @param ResponseInterface $response
     *
     * @throws BerliozException
     */
    public function printResponse(ResponseInterface $response)
    {
        $printActivity = (new Debug\Activity('Print response', 'Berlioz'))->start();

        // Debug?
        if ($this->getCore()->getDebug()->isEnabled()) {
            $response = $response->withAddedHeader('X-Berlioz-Debug', $this->getCore()->getDebug()->getUniqid());
        }

        // Headers
        if (!headers_sent()) {
            // Remove headers and add main header
            header(
                'HTTP/' . $response->getProtocolVersion() . ' ' .
                $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
                true
            );

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
        $stream = $response->getBody();
        if ($stream->isReadable()) {
            $stream->seek(0);

            while (!$stream->eof()) {
                print $stream->read(8192);
            }

            $stream->close();
        }

        // Debug
        $this->getCore()->getDebug()->getTimeLine()->addActivity($printActivity->end());
    }
}
