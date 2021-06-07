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

namespace Berlioz\Http\Core\Exception;

use Berlioz\Core\Exception\BerliozException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class HttpAppException.
 */
class HttpAppException extends BerliozException
{
    /**
     * Invalid middleware.
     *
     * @param string $middleware
     * @param Throwable|null $previous
     *
     * @return static
     */
    public static function invalidMiddleware(string $middleware, ?Throwable $previous = null): static
    {
        return new static(
            message: sprintf('Middleware "%s" must implement "%s" interface', $middleware, MiddlewareInterface::class),
            previous: $previous
        );
    }

    /**
     * Boot middleware.
     *
     * @param string $middleware
     * @param Throwable|null $previous
     *
     * @return static
     */
    public static function bootMiddleware(string $middleware, ?Throwable $previous = null): static
    {
        return new static(
            message: sprintf('Error during boot of "%s" middleware', $middleware),
            previous: $previous
        );
    }

    /**
     * Invalid maintenance handler.
     *
     * @return static
     */
    public static function invalidMaintenanceHandler(): static
    {
        return new static(
            message: sprintf('Maintenance handler must implement "%s"', RequestHandlerInterface::class)
        );
    }
}