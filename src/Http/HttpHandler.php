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

namespace Berlioz\Http\Core\Http;

use Berlioz\Http\Core\Http\Handler\Error\ErrorHandlerInterface;
use Berlioz\Http\Core\Http\Handler\MiddlewareHandler;
use Berlioz\ServiceContainer\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class HttpHandler.
 */
class HttpHandler implements RequestHandlerInterface
{
    protected array $middlewares = [];

    public function __construct(
        protected Container $container,
        protected RequestHandlerInterface $requestHandler,
        protected ErrorHandlerInterface $errorHandler,
    ) {
    }

    /**
     * Add middleware.
     *
     * @param MiddlewareInterface|string ...$middleware
     */
    public function addMiddleware(MiddlewareInterface|string ...$middleware): void
    {
        array_push($this->middlewares, ...$middleware);
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $handler = $this->requestHandler;

            foreach (array_reverse($this->middlewares) as $middleware) {
                $handler = new MiddlewareHandler($this->container, $middleware, $handler);
            }

            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->errorHandler->handle($request, $exception);
        }
    }
}