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

use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Router\ForwardedPrefixResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ForwardedPrefixMiddleware.
 *
 * Rewrites the current request URI with the reverse-proxy prefix
 * (`X-Forwarded-Prefix`) so that any URL derived from it — pagination links,
 * self-URLs, redirections — is correctly prefixed, without the application code
 * having to know about the reverse-proxy topology.
 *
 * All the resolution logic (header lookup, trusted-proxy guard) is
 * delegated to the injected ForwardedPrefixResolver, which is
 * the single source of truth. The resolved prefix is also exposed as the
 * `berlioz.forwarded_prefix` request attribute for consumers that need the raw value.
 *
 * This middleware is applied last in the pipeline (closest to the controller),
 * hence after route matching: rewriting the path never affects routing.
 */
class ForwardedPrefixMiddleware implements MiddlewareInterface
{
    public const REQUEST_ATTRIBUTE = 'berlioz.forwarded_prefix';
    private const PROCESSED_ATTRIBUTE = 'berlioz.forwarded_prefix.processed';

    public function __construct(
        protected HttpApp $app,
        private readonly ForwardedPrefixResolver $prefixResolver,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (true === $request->getAttribute(self::PROCESSED_ATTRIBUTE)) {
            return $handler->handle($request);
        }

        $prefix = $this->prefixResolver->resolve($request->getServerParams());

        if (null !== $prefix) {
            $uri = $request->getUri();
            $finalizedPath = $prefix . '/' . ltrim($uri->getPath(), '/');

            $request = $request
                ->withUri($uri->withPath($finalizedPath))
                ->withAttribute(self::REQUEST_ATTRIBUTE, $prefix)
                ->withAttribute(self::PROCESSED_ATTRIBUTE, true);

            // Keep the application-wide request in sync so helpers relying on
            // HttpApp::getRequest() (e.g. reload/self-redirect) see the prefixed URI.
            $this->app->setRequest($request);
        }

        return $handler->handle($request);
    }
}
