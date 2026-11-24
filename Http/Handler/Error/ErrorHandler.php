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

namespace Berlioz\Http\Core\Http\Handler\Error;

use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Exception\Http\BadRequestHttpException;
use Berlioz\Http\Core\Exception\Http\InternalServerErrorHttpException;
use Berlioz\Http\Core\Exception\HttpException;
use Berlioz\Http\Message\Exception\MalformedBodyException;
use Berlioz\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class ErrorHandler.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    public function __construct(protected HttpApp $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request, ?Throwable $throwable = null): ResponseInterface
    {
        // An unusable request body is a client error; other bodies (e.g. upstream responses) stay server errors
        if (
            $throwable instanceof MalformedBodyException
            && $throwable->getHttpMessage() instanceof ServerRequestInterface
        ) {
            $throwable = new BadRequestHttpException(previous: $throwable);
        }

        // Add exception to debug
        if (null !== $throwable) {
            if (!$throwable instanceof HttpException || $throwable->getCode() >= 500) {
                $this->logException($throwable);
            }
            $this->app->getCore()->getDebug()->addException($throwable);
        }

        try {
            $statusCode = Response::HTTP_STATUS_INTERNAL_SERVER_ERROR;
            if ($throwable instanceof HttpException) {
                $statusCode = $throwable->getCode();
            }
            if (null === $throwable) {
                $throwable = new InternalServerErrorHttpException();
            }

            $handlerClasses = $this->app->getConfigKey('berlioz.http.errors');
            $handlerClass = $handlerClasses[$statusCode] ?? $handlerClasses['default'] ?? null;

            if (null !== $handlerClass) {
                $handler = $this->app->call($handlerClass);

                if ($handler instanceof ErrorHandlerInterface) {
                    return $handler->handle($request, $throwable);
                }

                if ($handler instanceof RequestHandlerInterface) {
                    return $handler->handle($request);
                }
            }
        } catch (Throwable $exception) {
            $this->logException($exception);
            $this->app->getCore()->getDebug()->addException($exception);
        }

        return $this->default($request, $throwable);
    }

    /**
     * Default handler.
     *
     * @param ServerRequestInterface $request
     * @param Throwable $throwable
     *
     * @return ResponseInterface
     */
    protected function default(ServerRequestInterface $request, Throwable $throwable): ResponseInterface
    {
        try {
            $handler = $this->app->call(DefaultErrorHandler::class);

            return $handler->handle($request, $throwable);
        } catch (Throwable $exception) {
            $this->logException($exception);
            // Add exception to debug
            $this->app->getDebug()->addException($exception);

            return $this->fallback($throwable);
        }
    }

    /**
     * Log an exception to the configured PHP error log independently of debug mode.
     *
     * @param Throwable $exception
     */
    protected function logException(Throwable $exception): void
    {
        error_log((string)$exception);
    }

    /**
     * Fallback handler.
     *
     * @param Throwable $throwable
     *
     * @return ResponseInterface
     */
    protected function fallback(Throwable $throwable): ResponseInterface
    {
        $str =
            '<html lang="en">' .
            '<body>' .
            '<h1>Internal Server Error</h1>';

        try {
            $debug = $this->app->getDebug()->isEnabled();

            if (true === $debug) {
                $str .= '<pre>' . $throwable . '</pre>';
            }
        } catch (Throwable) {
        }

        if (!isset($debug) || false === $debug) {
            $str .= '<p>Looks like we\'re having some server issues.</p>';
        }

        $str .=
            '</body>' .
            '</html>';

        if ($throwable instanceof HttpException) {
            return new Response(
                body: $str,
                statusCode: $throwable->getCode() ?: 500,
                reasonPhrase: $this->getReasonPhrase($throwable)
            );
        }

        return new Response(body: $str, statusCode: 500);
    }

    /**
     * Get the reason phrase of an HTTP exception.
     *
     * A message with a control character other than HTAB cannot be emitted in a status line: no reason phrase is
     * given, so the default one of the status code applies.
     *
     * @param HttpException $httpException
     *
     * @return string|null
     */
    private function getReasonPhrase(HttpException $httpException): ?string
    {
        $message = $httpException->getMessage();

        return 1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $message) ? null : $message;
    }
}
