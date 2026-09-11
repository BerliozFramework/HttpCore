<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2026 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Http\Core\Http\Middleware;

use Berlioz\Config\Config;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Http\Core\Debug\DebugConsoleAccess;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class DebugConsoleMiddleware.
 *
 * Gates the whole debug console surface (`/_console`) behind debug mode and the
 * configured client IP allow-list. Requests that are not allowed never reach the
 * `DebugController` and get a 404 (the debug surface is not disclosed).
 */
class DebugConsoleMiddleware implements MiddlewareInterface
{
    public const PATH_PREFIX = '/_console';

    public function __construct(protected Config $config)
    {
    }

    /**
     * @inheritDoc
     * @throws ConfigException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Only guard the debug console surface; let every other request through untouched.
        if (false === $this->isConsolePath($path)) {
            return $handler->handle($request);
        }

        (new DebugConsoleAccess($this->config))->assertAllowed($request);

        return $handler->handle($request);
    }

    /**
     * Is the request path part of the debug console surface?
     *
     * @param string $path
     *
     * @return bool
     */
    private function isConsolePath(string $path): bool
    {
        // Route matching is case-insensitive; the guard must use the same semantics.
        $path = strtolower($path);

        return $path === self::PATH_PREFIX || str_starts_with($path, self::PATH_PREFIX . '/');
    }
}
