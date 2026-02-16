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

namespace Berlioz\Http\Core\Helper;

use Berlioz\Http\Core\App\HttpAppAwareTrait;
use Berlioz\Http\Message\Uri;
use Berlioz\Router\Exception\RoutingException;
use Berlioz\Router\RouteAttributes;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\Router;
use Berlioz\Router\RouterInterface;
use Psr\Http\Message\UriInterface;

/**
 * Trait RouterHelperTrait.
 */
trait RouterHelperTrait
{
    use HttpAppAwareTrait;

    /**
     * Get router.
     *
     * @return RouterInterface
     */
    protected function getRouter(): RouterInterface
    {
        return $this->getApp()->get(Router::class);
    }

    /**
     * Get current route.
     *
     * @return RouteInterface|null
     */
    protected function getRoute(): ?RouteInterface
    {
        return $this->getApp()->getRoute();
    }

    /**
     * Generate path.
     *
     * @param string|RouteInterface $name
     * @param array|RouteAttributes $parameters
     *
     * @return UriInterface
     * @throws RoutingException
     */
    protected function path(string|RouteInterface $name, array|RouteAttributes $parameters = []): UriInterface
    {
        return Uri::createFromString($this->getRouter()->generate($name, $parameters));
    }

    /**
     * Finalize path.
     *
     * @param string $path
     *
     * @return string
     */
    protected function finalize_path(string $path): string
    {
        return $this->getRouter()->finalizePath($path);
    }
}