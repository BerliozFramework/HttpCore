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
use Berlioz\Helpers\NetworkHelper;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
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

        if (false === $this->isAllowed($request)) {
            // Do not disclose the existence of the debug console.
            throw new NotFoundHttpException();
        }

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
        return $path === self::PATH_PREFIX || str_starts_with($path, self::PATH_PREFIX . '/');
    }

    /**
     * Is the request allowed to reach the debug console?
     *
     * The console is reachable only when debug mode is enabled and, when an IP
     * allow-list is configured, the (trusted) client IP matches it.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     * @throws ConfigException
     */
    private function isAllowed(ServerRequestInterface $request): bool
    {
        $enabled = $this->config->get('berlioz.debug.enable', false);

        if (!is_bool($enabled) || false === $enabled) {
            return false;
        }

        $allowedIps = $this->config->get('berlioz.debug.ip', []);

        if (!is_array($allowedIps)) {
            return false;
        }

        // No IP restriction: debug mode alone is enough.
        if ([] === $allowedIps) {
            return true;
        }

        $trustedProxies = $this->config->get('berlioz.proxies.trusted', []);

        if (!is_array($trustedProxies)) {
            $trustedProxies = [];
        }

        // Resolve the authoritative client IP from the PSR-7 request server params.
        // `X-Forwarded-For` is only honoured when the direct peer is a trusted proxy.
        $clientIp = NetworkHelper::clientIp($trustedProxies, $request->getServerParams());

        if (null === $clientIp) {
            return false;
        }

        // Match the client IP exactly...
        if (in_array($clientIp, $allowedIps, true)) {
            return true;
        }

        // ...or by reverse-DNS, performed only on the validated client IP.
        return in_array(gethostbyaddr($clientIp), $allowedIps, true);
    }
}
