<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HttpCore\Controller;

use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\FlashBag\FlashBag;
use Berlioz\Http\Message\Response;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\App\HttpAppAwareInterface;
use Berlioz\HttpCore\App\HttpAppAwareTrait;
use Berlioz\Package\Twig\Controller\RenderingControllerInterface;
use Berlioz\Package\Twig\Controller\RenderingControllerTrait;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\RouterInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Class AbstractController.
 *
 * @package Berlioz\HttpCore\Controller
 */
abstract class AbstractController implements CoreAwareInterface, HttpAppAwareInterface, RenderingControllerInterface
{
    use CoreAwareTrait;
    use HttpAppAwareTrait;
    use RenderingControllerTrait;

    /**
     * AbstractController constructor.
     *
     * @param HttpApp $app
     */
    public function __construct(HttpApp $app)
    {
        $this->setApp($app);
        $this->setCore($app->getCore());
    }

    /**
     * __sleep() magic method.
     *
     * @throws RuntimeException because unable to serialize a Controller object
     */
    public function __sleep(): array
    {
        throw new RuntimeException('Unable to serialize a Controller object');
    }

    /**
     * Get service.
     *
     * @param string $id
     *
     * @return mixed
     * @throws BerliozException
     */
    public function getService(string $id)
    {
        return $this->getApp()->getService($id);
    }

    /**
     * Get router.
     *
     * @return RouterInterface|null
     * @throws BerliozException
     */
    public function getRouter(): ?RouterInterface
    {
        /** @var RouterInterface $router */
        return $this->getService(RouterInterface::class);
    }

    /**
     * Get current route.
     *
     * @return RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->getApp()->getRoute();
    }

    /**
     * Reload page.
     *
     * If response is given in parameter, it will be completed with good headers.
     *
     * @param array $queryParams Http GET parameters
     * @param bool $mergeQueryParams Merge parameters with current server request
     * @param ResponseInterface $response Response
     *
     * @return ResponseInterface
     * @throws BerliozException
     */
    public function reload(
        ?array $queryParams = [],
        bool $mergeQueryParams = false,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        if (null === ($serverRequest = $this->getRouter()->getServerRequest())) {
            throw new LogicException('You can not reload without server request');
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
     * @param string|UriInterface $uri
     * @param int $httpResponseCode
     * @param null|ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function redirect($uri, int $httpResponseCode = 302, ?ResponseInterface $response = null): ResponseInterface
    {
        if (null === $response) {
            $response = new Response;
        }

        return
            $response
                ->withStatus($httpResponseCode)
                ->withHeader('Location', (string)$uri);
    }

    /**
     * Add new message in flash bag.
     *
     * @param string $type Type of message
     * @param string $message Message
     *
     * @return static
     * @throws BerliozException
     * @see \Berlioz\FlashBag\FlashBag FlashBag class whose manage all flash messages
     */
    protected function addFlash($type, $message): AbstractController
    {
        /** @var FlashBag $flashBag */
        $flashBag = $this->getService(FlashBag::class);
        $flashBag->add($type, $message);

        return $this;
    }
}