<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2026 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Http\Core\Http\Middleware;

use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Exception\Http\BadRequestHttpException;
use Berlioz\Http\Core\Exception\Http\UnsupportedMediaTypeHttpException;
use Berlioz\Http\Core\Exception\HttpAppException;
use Berlioz\Http\Core\Exception\HttpException;
use Berlioz\Http\Core\Http\Handler\Error\ErrorHandler;
use Berlioz\Http\Core\Http\MediaType;
use Berlioz\Http\Message\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class AcceptQueryMiddleware.
 *
 * Handles the "acceptQuery" route option (RFC 10008): advertises the accepted query media types
 * with the "Accept-Query" response header and rejects QUERY requests with a missing, invalid or unsupported
 * content type.
 */
class AcceptQueryMiddleware implements MiddlewareInterface
{
    public const ROUTE_OPTION = 'acceptQuery';
    public const HEADER = 'Accept-Query';

    public function __construct(
        protected HttpApp $app,
    ) {
    }

    /**
     * @inheritDoc
     * @throws HttpAppException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $mediaRanges = $this->getMediaRanges();

        if (empty($mediaRanges)) {
            return $handler->handle($request);
        }

        $headerValue = $this->serializeHeader($mediaRanges);

        if (Request::HTTP_METHOD_QUERY === strtoupper($request->getMethod())) {
            $exception = $this->checkContentType($request->getHeaderLine('Content-Type'), $mediaRanges);

            if (null !== $exception) {
                return $this->createErrorResponse($request, $exception)->withHeader(static::HEADER, $headerValue);
            }
        }

        return $handler->handle($request)->withHeader(static::HEADER, $headerValue);
    }

    /**
     * Get accepted media ranges from the current route.
     *
     * @return MediaType[]
     * @throws HttpAppException
     */
    protected function getMediaRanges(): array
    {
        $option = $this->app->getRoute()?->getOption(static::ROUTE_OPTION);

        if (null === $option) {
            return [];
        }

        $mediaRanges = [];

        foreach ((array)$option as $mediaRange) {
            if (!is_string($mediaRange) || null === ($parsed = MediaType::parse($mediaRange, allowWildcard: true))) {
                throw new HttpAppException(
                    sprintf(
                        'Route option "%s" must contain valid media ranges, "%s" given',
                        static::ROUTE_OPTION,
                        is_string($mediaRange) ? $mediaRange : get_debug_type($mediaRange),
                    )
                );
            }

            $mediaRanges[] = $parsed;
        }

        return $mediaRanges;
    }

    /**
     * Check content type of a QUERY request.
     *
     * @param string $contentType
     * @param MediaType[] $mediaRanges
     *
     * @return HttpException|null NULL if accepted
     */
    protected function checkContentType(string $contentType, array $mediaRanges): ?HttpException
    {
        if ('' === trim($contentType)) {
            return new BadRequestHttpException('Missing content type for QUERY request');
        }

        if (null === ($mediaType = MediaType::parse($contentType))) {
            return new BadRequestHttpException('Invalid content type for QUERY request');
        }

        foreach ($mediaRanges as $mediaRange) {
            if ($mediaRange->matches($mediaType)) {
                return null;
            }
        }

        return new UnsupportedMediaTypeHttpException();
    }

    /**
     * Serialize media ranges as a structured field list (RFC 9651) of strings with parameters.
     *
     * @param MediaType[] $mediaRanges
     *
     * @return string
     * @throws HttpAppException
     */
    protected function serializeHeader(array $mediaRanges): string
    {
        $members = [];

        foreach ($mediaRanges as $mediaRange) {
            $member = $this->serializeString($mediaRange->getMediaType());

            foreach ($mediaRange->parameters as $name => $value) {
                // Structured field keys: lcalpha / "*", followed by lcalpha / DIGIT / "_" / "-" / "." / "*"
                if (1 !== preg_match('/^[a-z*][a-z0-9_\-.*]*$/', (string)$name)) {
                    throw new HttpAppException(sprintf('Invalid media type parameter name "%s"', $name));
                }

                $member .= ';' . $name . '=' . $this->serializeString($value);
            }

            $members[] = $member;
        }

        return implode(', ', $members);
    }

    /**
     * Serialize a structured field string (RFC 9651, section 4.1.6).
     *
     * @param string $value
     *
     * @return string
     * @throws HttpAppException
     */
    protected function serializeString(string $value): string
    {
        if (1 !== preg_match('/^[\x20-\x7E]*$/', $value)) {
            throw new HttpAppException(sprintf('Invalid characters in "%s" header value', static::HEADER));
        }

        return '"' . addcslashes($value, '"\\') . '"';
    }

    /**
     * Create error response through the application error handler.
     *
     * @param ServerRequestInterface $request
     * @param HttpException $exception
     *
     * @return ResponseInterface
     */
    protected function createErrorResponse(
        ServerRequestInterface $request,
        HttpException $exception,
    ): ResponseInterface {
        return (new ErrorHandler($this->app))->handle($request, $exception);
    }
}
