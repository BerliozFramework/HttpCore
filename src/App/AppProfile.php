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
use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\FlashBag\FlashBag;
use Berlioz\Router\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AppProfile.
 *
 * @package Berlioz\HttpCore\App
 */
class AppProfile implements CoreAwareInterface, HttpAppAwareInterface
{
    use CoreAwareTrait;
    use HttpAppAwareTrait;

    /**
     * AppProfile constructor.
     *
     * @param \Berlioz\Core\Core                 $core
     * @param \Berlioz\HttpCore\App\HttpApp|null $app
     */
    public function __construct(Core $core, ?HttpApp $app)
    {
        $this->setCore($core);

        if (!is_null($app)) {
            $this->setApp($app);
        }
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
     */
    public function getConfig(): ConfigInterface
    {
        return $this->getCore()->getConfig();
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
        $flashBag = $this->getCore()->getServiceContainer()->get('flashbag');

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
        $router = $this->getCore()->getServiceContainer()->get('router');

        return $router->getServerRequest();
    }

    /**
     * Get route.
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        if (is_null($this->getApp())) {
            return null;
        }

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
        return $this->getCore()->getLocale();
    }

    /**
     * Is debug enabled?
     *
     * @return bool
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function isDebugEnabled(): bool
    {
        return $this->getCore()->getDebug()->isEnabled();
    }

    /**
     * Get debug unique ID.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getDebugUniqid(): string
    {
        return $this->getCore()->getDebug()->getUniqid();
    }
}