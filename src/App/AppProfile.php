<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HttpCore\App;

use Berlioz\Config\ConfigInterface;
use Berlioz\FlashBag\FlashBag;
use Berlioz\Router\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AppProfile.
 *
 * @package Berlioz\HttpCore\App
 */
class AppProfile
{
    use HttpAppAwareTrait;

    /**
     * AppProfile constructor.
     *
     * @param \Berlioz\HttpCore\App\HttpApp $app
     */
    public function __construct(HttpApp $app)
    {
        $this->setApp($app);
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
     * Get configuration.
     *
     * @return \Berlioz\Config\ConfigInterface
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getConfig(): ConfigInterface
    {
        return $this->getApp()->getCore()->getConfig();
    }

    /**
     * Get flash bag.
     *
     * @return \Berlioz\FlashBag\FlashBag
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getFlashBag(): FlashBag
    {
        /** @var \Berlioz\FlashBag\FlashBag $flashBag */
        $flashBag = $this->getApp()->getCore()->getServiceContainer()->get('flashbag');

        return $flashBag;
    }

    /**
     * Get server request.
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getRequest(): ServerRequestInterface
    {
        /** @var \Berlioz\Router\RouterInterface $router */
        $router = $this->getApp()->getCore()->getServiceContainer()->get('router');

        return $router->getServerRequest();
    }

    /**
     * Get route.
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        /** @var \Berlioz\Router\RouteInterface $route */
        $route = $this->getApp()->getRoute();

        return $route;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->getApp()->getCore()->getLocale();
    }

    /**
     * Is debug enabled?
     *
     * @return bool
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function isDebugEnabled(): bool
    {
        return $this->getApp()->getCore()->getDebug()->isEnabled();
    }

    /**
     * Get debug unique ID.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getDebugUniqid(): string
    {
        return $this->getApp()->getCore()->getDebug()->getUniqid();
    }
}