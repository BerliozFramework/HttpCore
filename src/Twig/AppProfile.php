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

namespace Berlioz\HttpCore\Twig;

use Berlioz\Config\ConfigInterface;
use Berlioz\FlashBag\FlashBag;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\App\HttpAppAwareTrait;
use Berlioz\Router\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        return $this->getApp()->getConfig();
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
        $flashBag = $this->getApp()->getServiceContainer()->get('flashbag');

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
        $router = $this->getApp()->getServiceContainer()->get('router');

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
        return $this->getApp()->getLocale();
    }

    /**
     * Is debug enabled?
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->getApp()->getDebug()->isEnabled();
    }

    /**
     * Get debug unique ID.
     *
     * @return string
     */
    public function getDebugUniqid(): string
    {
        return $this->getApp()->getDebug()->getUniqid();
    }
}