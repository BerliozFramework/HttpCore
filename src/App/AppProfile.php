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
use Berlioz\Core\Asset\Assets;
use Berlioz\FlashBag\FlashBag;
use Berlioz\Router\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AppProfile.
 */
class AppProfile
{
    /**
     * AppProfile constructor.
     *
     * @param HttpApp $app
     */
    public function __construct(protected HttpApp $app)
    {
    }

    /**
     * __debugInfo() magic method.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [];
    }

    /**
     * Get environment.
     *
     * @return string
     * @throws ConfigException
     */
    public function getEnv(): string
    {
        return $this->app->getCore()->getEnv();
    }

    /**
     * Get configuration.
     *
     * @param string|null $key
     * @param mixed|null $default
     *
     * @return mixed
     * @throws ConfigException
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if (null !== $key) {
            return $this->app->getConfigKey($key, $default);
        }

        return $this->app->getConfig();
    }

    /**
     * Get assets.
     *
     * @return Assets
     */
    public function getAssets(): Assets
    {
        return $this->app->getAssets();
    }

    /**
     * Get flash bag.
     *
     * @return FlashBag
     */
    public function getFlashBag(): FlashBag
    {
        return $this->app->get(FlashBag::class);
    }

    /**
     * Get server request.
     *
     * @return ServerRequestInterface|null
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->app->getRequest();
    }

    /**
     * Get route.
     *
     * @return RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->app->getRoute();
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->app->getCore()->getLocale();
    }

    /**
     * Is debug enabled?
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->app->getCore()->getDebug()->isEnabled();
    }

    /**
     * Get debug unique ID.
     *
     * @return string
     */
    public function getDebugUniqid(): string
    {
        return $this->app->getCore()->getDebug()->getUniqid();
    }
}