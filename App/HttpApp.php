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

namespace Berlioz\Http\Core\App;

use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\App\AbstractApp;
use Berlioz\Core\Core;
use Berlioz\Core\Debug\Snapshot\TimelineActivity;
use Berlioz\Core\Debug\SnapshotLoader;
use Berlioz\Http\Core\Debug\RouterSection;
use Berlioz\Http\Core\Event\HttpTerminateEvent;
use Berlioz\Http\Core\Http\Handler\ControllerHandler;
use Berlioz\Http\Core\Http\Handler\Error\ErrorHandler;
use Berlioz\Http\Core\Http\HttpHandler;
use Berlioz\Http\Core\Http\Middleware\ForwardedPrefixMiddleware;
use Berlioz\Http\Message\Exception\InvalidResponseException;
use Berlioz\Http\Message\HttpFactory;
use Berlioz\Http\Message\Validator\Http1ResponseValidator;
use Berlioz\Router\ForwardedPrefixResolver;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\Router;
use Berlioz\Router\RouterInterface;
use Berlioz\ServiceContainer\Inflector\Inflector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class HttpApp.
 */
class HttpApp extends AbstractApp implements RequestHandlerInterface
{
    private bool $printed = false;
    private HttpHandler $httpHandler;
    protected ?Maintenance $maintenance = null;
    protected ?ServerRequestInterface $request = null;
    protected ?RouteInterface $route = null;
    /** @var array{statusCode: int, reasonPhrase: string, protocolVersion: string, headers: array<string, string[]>}|null */
    protected ?array $responseInfo = null;

    /**
     * HttpApp constructor.
     *
     * @param Core|null $core
     */
    public function __construct(?Core $core = null)
    {
        parent::__construct($core);

        $this->getCore()->getContainer()->addInflector(
            new Inflector(
                HttpAppAwareInterface::class,
                'setApp',
                ['app' => $this]
            )
        );
    }

    /**
     * @inheritDoc
     */
    protected function boot(): void
    {
        $this->core->getDebug()->addSection(new RouterSection($this));
        $bootActivity = $this->core->getDebug()->newActivity('Application Boot', 'Berlioz')->start();

        $this->maintenance = Maintenance::buildFromConfig($this->getConfig());
        $this->httpHandler = new HttpHandler(
            container: $this->getCore()->getContainer(),
            requestHandler: new ControllerHandler($this),
            errorHandler: new ErrorHandler($this),
        );

        $bootActivity->end();
    }

    ///////////////
    /// GETTERS ///
    ///////////////

    /**
     * Get maintenance.
     *
     * @return Maintenance|null
     */
    public function getMaintenance(): ?Maintenance
    {
        return $this->maintenance;
    }

    /**
     * Get router.
     *
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->get(Router::class);
    }

    /**
     * Get request.
     *
     * @return ServerRequestInterface|null
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request ?? null;
    }

    /**
     * Set request.
     *
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Get response metadata without retaining the response body.
     *
     * @return array{statusCode: int, reasonPhrase: string, protocolVersion: string, headers: array<string, string[]>}|null
     */
    public function getResponseInfo(): ?array
    {
        return $this->responseInfo;
    }

    /**
     * Capture response metadata without accessing its stream.
     *
     * @param ResponseInterface $response
     */
    protected function captureResponseInfo(ResponseInterface $response): void
    {
        $this->responseInfo = [
            'statusCode' => $response->getStatusCode(),
            'reasonPhrase' => $response->getReasonPhrase(),
            'protocolVersion' => $response->getProtocolVersion(),
            'headers' => $response->getHeaders(),
        ];
    }

    /**
     * Get current route.
     *
     * @return RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route ?? null;
    }

    ///////////////
    /// HANDLER ///
    ///////////////

    /**
     * Find route.
     *
     * @param ServerRequestInterface $request
     *
     * @return RouteInterface|null
     */
    protected function findRoute(ServerRequestInterface &$request): ?RouteInterface
    {
        $activity = $this->core->getDebug()->newActivity('Router', 'Berlioz')->start();
        $router = $this->getRouter();
        $activity->end();

        $activity = $this->core->getDebug()->newActivity('Router handle', 'Berlioz')->start();
        $route = $router->handle($request);
        $activity->end();

        return $route;
    }

    /**
     * Handle application.
     *
     * @param ServerRequestInterface|null $request Server request
     *
     * @return ResponseInterface
     * @throws ConfigException
     */
    public function handle(?ServerRequestInterface $request = null): ResponseInterface
    {
        $this->responseInfo = null;
        $activity = $this->core->getDebug()->newActivity('Application handle', 'Berlioz')->start();

        if (null === $request) {
            $httpFactory = new HttpFactory();
            $request = $httpFactory->createServerRequestFromGlobals();
        }
        $this->request = $request;
        $activity->end();

        // Keep generated routes and assets aligned with the current PSR-7 request,
        // independently of whether request URI rewriting is enabled.
        $this->getRouter()->setServerParams($request->getServerParams());

        // Find route
        $this->route = $this->findRoute($this->request);

        $activity = $this->core->getDebug()->newActivity('Middleware', 'Berlioz')->start();

        // Add middlewares to http handler
        $this->httpHandler->resetMiddlewares();
        $middlewares = $this->getConfig()->get('berlioz.http.middlewares', []);
        uksort($middlewares, fn($key1, $key2) => (int)$key1 <=> (int)$key2);
        array_walk_recursive($middlewares, fn($middleware) => $this->httpHandler->addMiddleware($middleware));

        // Applied last (closest to the controller): rewrites the request URI with the
        // reverse-proxy prefix so any URL derived from it (pagination, self-URLs, ...)
        // is correctly prefixed. Runs after routing, so route matching is never affected.
        if (false !== $this->getConfig()->get('berlioz.router.X-Forwarded-Prefix', false)) {
            if (true === $this->getConfig()->get('berlioz.router.rewriteRequestUri', false)) {
                $this->httpHandler->addMiddleware(ForwardedPrefixMiddleware::class);
            } elseif (null !== $this->get(ForwardedPrefixResolver::class)->resolve($this->request->getServerParams())) {
                trigger_error(
                    'Handling a trusted forwarded prefix without request URI rewriting is deprecated. '
                    . 'Set berlioz.router.rewriteRequestUri to true; rewriting will be mandatory in v4.',
                    E_USER_DEPRECATED,
                );
            }
        }

        $activity->end();

        $response = $this->httpHandler->handle($this->request);
        $this->captureResponseInfo($response);

        return $response;
    }

    /**
     * Set printed.
     *
     * @param bool $print
     */
    public function setPrinted(bool $print = true): void
    {
        $this->printed = $print;
    }

    /**
     * Print ResponseInterface object.
     *
     * Once printed, the connection with the client is closed (see finishRequest()),
     * so that work done in terminate() is not perceived by the client.
     *
     * The complete response is validated before any output: an invalid response emits
     * nothing and the application remains printable.
     *
     * @param ResponseInterface $response
     *
     * @throws ConfigException
     * @throws InvalidResponseException If the response cannot be emitted as a valid HTTP/1 message.
     */
    public function print(ResponseInterface $response): void
    {
        if (true === $this->printed) {
            return;
        }

        // Debug? So attach a header for unique id of debug snapshot
        if ($this->getDebug()->isEnabled()) {
            $response = $response->withAddedHeader('X-Berlioz-Debug', $this->getDebug()->getUniqid());
        }

        // Validate the complete response before any output, so that an invalid response is never partially emitted
        $head = (new Http1ResponseValidator())->validate($response);

        // Set response printed to not print again the response
        $this->setPrinted();

        $printActivity = $this->getDebug()->newActivity('Print response', TimelineActivity::BERLIOZ_GROUP);
        $printActivity->start();

        $this->captureResponseInfo($response);

        // Headers
        if (!headers_sent()) {
            // Remove headers and add main header
            header($head->statusLine, true);

            // Headers
            $replaced = [];
            foreach ($head->headers as $name => $values) {
                $lowerName = strtolower($name);

                foreach ($values as $value) {
                    $replace = !isset($replaced[$lowerName]);
                    $replaced[$lowerName] = true;

                    // Pass status code to prevent PHP from rewriting it (e.g. 302 with a "Location" header)
                    header(sprintf('%s: %s', $name, $value), $replace, $head->statusCode);
                }
            }
        }

        // Print body packets 8K by 8K
        $stream = $response->getBody();
        if ($stream->isReadable()) {
            if ($stream->isSeekable()) {
                $stream->seek(0);
            }

            while (!$stream->eof()) {
                print $stream->read(8192);
            }

            $stream->close();
        }

        $this->markDebugResponseSent();
        $this->finishRequest();

        // Debug
        $printActivity->end();
    }

    /**
     * Mark debug response as sent.
     *
     * The debug snapshot is saved at the end of the process, after terminate work. A pending marker
     * is written to let the debug console know that the snapshot is in progress.
     */
    protected function markDebugResponseSent(): void
    {
        $debug = $this->getDebug();

        if (false === $debug->isEnabled()) {
            return;
        }

        $debug->markResponseSent();

        try {
            (new SnapshotLoader($this->getCore()->getFilesystem(), $this->getConfig()))
                ->markPending($debug->getUniqid(), ['responseTime' => $debug->getResponseTime()]);
        } catch (Throwable) {
            trigger_error('Unable to mark debug snapshot as pending', E_USER_WARNING);
        }
    }

    /**
     * Finish request: flush the response and close the connection with the client.
     *
     * Only available with PHP-FPM (and compatible SAPIs) or LiteSpeed; no-op otherwise.
     * Can be disabled with the "berlioz.http.finish_request" configuration.
     *
     * @return bool True if the connection has been closed
     * @throws ConfigException
     */
    protected function finishRequest(): bool
    {
        if (false === $this->getConfig()->get('berlioz.http.finish_request', true)) {
            return false;
        }

        if (function_exists('fastcgi_finish_request')) {
            return fastcgi_finish_request();
        }

        if (function_exists('litespeed_finish_request')) {
            return litespeed_finish_request();
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * Releases the session lock before dispatching the terminate event,
     * so that next requests of the same client are not blocked.
     */
    public function terminate(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        parent::terminate();
    }

    /**
     * @inheritDoc
     */
    protected function createTerminateEvent(): HttpTerminateEvent
    {
        return new HttpTerminateEvent($this, $this->request, $this->responseInfo);
    }
}
