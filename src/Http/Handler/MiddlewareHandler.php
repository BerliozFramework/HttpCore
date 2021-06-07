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

namespace Berlioz\Http\Core\Http\Handler;

use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\Exception\HttpAppException;
use Berlioz\ServiceContainer\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class MiddlewareHandler.
 */
class MiddlewareHandler implements RequestHandlerInterface
{
    public function __construct(
        protected Container $container,
        protected MiddlewareInterface|string $middleware,
        protected RequestHandlerInterface $handler
    ) {
    }

    /**
     * Get middleware.
     *
     * @return MiddlewareInterface
     * @throws BerliozException
     */
    private function getMiddleware(): MiddlewareInterface
    {
        if ($this->middleware instanceof MiddlewareInterface) {
            return $this->middleware;
        }

        try {
            $obj = $this->container->call($this->middleware);

            if (!($obj instanceof MiddlewareInterface)) {
                throw HttpAppException::invalidMiddleware($this->middleware);
            }
        } catch (HttpAppException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw HttpAppException::bootMiddleware($this->middleware, $exception);
        }

        return $this->middleware = $obj;
    }

    /**
     * @inheritDoc
     * @throws BerliozException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getMiddleware()->process($request, $this->handler);
    }
}