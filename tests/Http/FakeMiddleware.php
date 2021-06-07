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

namespace Berlioz\Http\Core\Tests\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FakeMiddleware implements MiddlewareInterface
{
    private bool $processed = false;
    private $callback;

    public function __construct(callable $callback = null)
    {
        $this->callback = $callback;
    }


    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->processed = true;

        if (null !== $this->callback) {
            return ($this->callback)();
        }

        return $handler->handle($request);
    }
}