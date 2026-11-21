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

namespace Berlioz\Http\Core\Event;

use Berlioz\Core\Event\TerminateEvent;
use Berlioz\Http\Core\App\HttpApp;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class HttpTerminateEvent.
 */
class HttpTerminateEvent extends TerminateEvent
{
    /**
     * @param HttpApp $app
     * @param ServerRequestInterface|null $request
     * @param array{statusCode: int, reasonPhrase: string, protocolVersion: string, headers: array<string, string[]>}|null $responseInfo
     */
    public function __construct(
        HttpApp $app,
        protected ?ServerRequestInterface $request = null,
        protected ?array $responseInfo = null,
    ) {
        parent::__construct($app);
    }

    /**
     * @inheritDoc
     */
    public function getApp(): HttpApp
    {
        /** @var HttpApp */
        return $this->app;
    }

    /**
     * Get server request.
     *
     * @return ServerRequestInterface|null
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Get response metadata (the response body is not retained).
     *
     * @return array{statusCode: int, reasonPhrase: string, protocolVersion: string, headers: array<string, string[]>}|null
     */
    public function getResponseInfo(): ?array
    {
        return $this->responseInfo;
    }
}
