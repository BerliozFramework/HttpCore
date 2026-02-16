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

use Berlioz\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Trait ResponseHelperTrait.
 */
trait ResponseHelperTrait
{
    /**
     * Response.
     *
     * @param string|resource|null $body
     * @param int $statusCode
     * @param array $headers
     *
     * @return ResponseInterface
     */
    protected function response(
        mixed $body = null,
        int $statusCode = Response::HTTP_STATUS_OK,
        array $headers = [],
    ): ResponseInterface {
        $response = new Response($body, $statusCode, $headers);

        if ($response->getBody()->getSize() === 0) {
            if ($response->getStatusCode() === Response::HTTP_STATUS_OK) {
                $response = $response->withStatus(Response::HTTP_STATUS_NO_CONTENT);
            }
        }

        return $response;
    }

    /**
     * JSON response.
     *
     * @param mixed $body
     * @param int $flags
     * @param int $statusCode
     * @param array $headers
     *
     * @return ResponseInterface
     */
    protected function jsonResponse(
        mixed $body = null,
        int $flags = 0,
        int $statusCode = Response::HTTP_STATUS_OK,
        array $headers = [],
    ): ResponseInterface {
        $response = new Response(
            null !== $body ? json_encode($body, $flags) : '',
            $statusCode,
            array_replace(['Content-Type' => 'application/json'], $headers)
        );

        if ($response->getBody()->getSize() === 0) {
            if ($response->getStatusCode() === Response::HTTP_STATUS_OK) {
                $response = $response->withStatus(Response::HTTP_STATUS_NO_CONTENT);
            }
        }

        return $response;
    }

    /**
     * Redirection.
     *
     * If response is given in parameter, it will be completed with good headers.
     *
     * @param UriInterface|string $uri
     * @param int $httpResponseCode
     * @param ResponseInterface|null $response
     *
     * @return ResponseInterface
     */
    protected function redirect(
        UriInterface|string $uri,
        int $httpResponseCode = 302,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        if (null === $response) {
            $response = new Response;
        }

        return
            $response
                ->withStatus($httpResponseCode)
                ->withHeader('Location', (string)$uri);
    }
}