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
use Berlioz\Http\Core\Debug\RouterSection;
use Berlioz\Http\Core\Http\Handler\ControllerHandler;
use Berlioz\Http\Core\Http\Handler\Error\ErrorHandler;
use Berlioz\Http\Core\Http\HttpHandler;
use Berlioz\Http\Message\HttpFactory;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\Router;
use Berlioz\Router\RouterInterface;
use Berlioz\ServiceContainer\Inflector\Inflector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class HttpApp.
 */
class HttpApp extends AbstractApp implements RequestHandlerInterface
{
    private bool $printed = false;
    private HttpHandler $httpHandler;
    protected ?Maintenance $maintenance;
    protected ?ServerRequestInterface $request;
    protected ?RouteInterface $route;

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
        $activity = $this->core->getDebug()->newActivity('Application handle', 'Berlioz')->start();

        if (null === $request) {
            $httpFactory = new HttpFactory();
            $request = $httpFactory->createServerRequestFromGlobals();
        }
        $this->request = $request;
        $activity->end();

        // Find route
        $this->route = $this->findRoute($this->request);

        $activity = $this->core->getDebug()->newActivity('Middleware', 'Berlioz')->start();

        // Add middlewares to http handler
        $middlewares = $this->getConfig()->get('berlioz.http.middlewares', []);
        uksort($middlewares, fn($key1, $key2) => $key1 <=> $key2);
        array_walk_recursive($middlewares, fn($middleware) => $this->httpHandler->addMiddleware($middleware));

        $activity->end();

        return $this->httpHandler->handle($this->request);
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
     * @param ResponseInterface $response
     */
    public function print(ResponseInterface $response): void
    {
        if (true === $this->printed) {
            return;
        }

        // Set response printed to not print again the response
        $this->setPrinted();

        $printActivity = $this->getDebug()->newActivity('Print response', TimelineActivity::BERLIOZ_GROUP);
        $printActivity->start();

        // Debug? So attach a header for unique id of debug snapshot
        if ($this->getDebug()->isEnabled()) {
            $response = $response->withAddedHeader('X-Berlioz-Debug', $this->getDebug()->getUniqid());
        }

        // Headers
        if (!headers_sent()) {
            // Remove headers and add main header
            header(
                sprintf(
                    'HTTP/%s %d %s',
                    $response->getProtocolVersion(),
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                ),
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

        // Debug
        $printActivity->end();
    }
}
