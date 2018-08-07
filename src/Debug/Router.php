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

namespace Berlioz\HttpCore\Debug;

use Berlioz\Core\Debug\AbstractSection;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\App\HttpAppAwareInterface;
use Berlioz\HttpCore\App\HttpAppAwareTrait;

class Router extends AbstractSection implements HttpAppAwareInterface, Section
{
    use HttpAppAwareTrait;
    /** @var \Psr\Http\Message\ServerRequestInterface Server request */
    protected $serverRequest;
    /** @var \Berlioz\Router\RouteInterface Route */
    protected $route;

    public function __construct(HttpApp $app)
    {
        $this->setApp($app);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['serverRequest' => $this->getServerRequest(),
                          'route'         => $this->getRoute()]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->serverRequest = $unserialized['serverRequest'] ?? null;
        $this->route = $unserialized['route'] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return var_export($this, true);
    }

    /**
     * Get section name.
     *
     * @return string
     */
    public function getSectionName(): string
    {
        return 'Router';
    }

    /**
     * @inheritdoc
     */
    public function getTemplateName(): string
    {
        return '@Berlioz-HttpCore/Twig/Debug/router.html.twig';
    }

    /**
     * Get server request.
     *
     * @return \Psr\Http\Message\ServerRequestInterface|null
     */
    public function getServerRequest(): ?\Psr\Http\Message\ServerRequestInterface
    {
        if (is_null($this->serverRequest) && $this->hasApp()) {
            try {
                $this->serverRequest = $this->getApp()->getRouter()->getServerRequest();
            } catch (\Throwable $e) {
            }
        }

        return $this->serverRequest;
    }

    /**
     * Get route.
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function getRoute(): ?\Berlioz\Router\RouteInterface
    {
        if (is_null($this->route) && $this->hasApp()) {
            $this->route = $this->getApp()->getRoute();
        }

        return $this->route;
    }
}