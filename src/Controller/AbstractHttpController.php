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

namespace Berlioz\HttpCore\Controller;

use Berlioz\Http\Message\Response;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\RouterInterface;
use Berlioz\HttpCore\App\HttpApp;
use PHPUnit\Framework\MockObject\RuntimeException;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractHttpController extends \Berlioz\Core\Controller\AbstractController
{
    /**
     * Get router.
     *
     * @return \Berlioz\Router\RouterInterface|null
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getRouter(): ?RouterInterface
    {
        /** @var \Berlioz\Router\RouterInterface $router */
        return $this->getApp()->getServiceContainer()->get(RouterInterface::class);
    }

    /**
     * Get current route.
     *
     * @return \Berlioz\Router\RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        if (($app = $this->getApp()) instanceof HttpApp) {
            /** @var \Berlioz\HttpCore\App\HttpApp $app */
            return $app->getRoute();
        } else {
            throw new RuntimeException(sprintf('App of controller is not a "%s" class', HttpApp::class));
        }
    }

    /**
     * Reload page.
     *
     * If response is given in parameter, it will be completed with good headers.
     *
     * @param array                               $queryParams      Http GET parameters
     * @param bool                                $mergeQueryParams Merge parameters with current server request
     * @param \Psr\Http\Message\ResponseInterface $response         Response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function reload(?array $queryParams = [], bool $mergeQueryParams = false, ?ResponseInterface $response = null): ResponseInterface
    {
        if (is_null($serverRequest = $this->getRouter()->getServerRequest())) {
            throw new \LogicException('You can not reload without server request');
        }

        // Query
        if ($mergeQueryParams) {
            $queryParams = array_merge($serverRequest->getQueryParams(), $queryParams);
        }

        // URI
        $uri = $serverRequest->getUri();
        $uri = $uri->withQuery(http_build_query($queryParams));

        return $this->redirect($uri, 302, $response);
    }

    /**
     * Redirection.
     *
     * If response is given in parameter, it will be completed with good headers.
     *
     * @param string|\Psr\Http\Message\UriInterface    $uri
     * @param int                                      $httpResponseCode
     * @param null|\Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function redirect($uri, int $httpResponseCode = 302, ?ResponseInterface $response = null): ResponseInterface
    {
        if (is_null($response)) {
            $response = new Response;
        }

        return $response->withStatus($httpResponseCode)
                        ->withHeader('Location', (string) $uri);
    }

    /**
     * Add new message in flash bag.
     *
     * @param string $type    Type of message
     * @param string $message Message
     *
     * @return static
     * @throws \Berlioz\Core\Exception\BerliozException
     * @see \Berlioz\FlashBag\FlashBag FlashBag class whose manage all flash messages
     */
    protected function addFlash($type, $message): AbstractHttpController
    {
        /** @var \Berlioz\FlashBag\FlashBag $flashBag */
        $flashBag = $this->getApp()->getServiceContainer()->get('flashbag');
        $flashBag->add($type, $message);

        return $this;
    }
}