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

namespace Berlioz\Http\Core\Tests\Controller;

use Berlioz\Http\Core\Controller\AbstractController;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class FakeController extends AbstractController
{
    public function getRouter(): RouterInterface
    {
        return parent::getRouter();
    }

    public function getRoute(): ?RouteInterface
    {
        return parent::getRoute();
    }

    public function redirect(
        UriInterface|string $uri,
        int $httpResponseCode = 302,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        return parent::redirect($uri, $httpResponseCode, $response);
    }

    public function reload(
        array $queryParams = [],
        bool $mergeQueryParams = false,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        return parent::reload($queryParams, $mergeQueryParams, $response);
    }

    public function addFlash(string $type, string $message): static
    {
        return parent::addFlash($type, $message);
    }
}