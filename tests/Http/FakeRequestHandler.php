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

use Berlioz\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FakeRequestHandler implements RequestHandlerInterface
{
    private bool $handled = false;
    private $callback;

    public function __construct(callable $callback = null)
    {
        $this->callback = $callback;
    }

    /**
     * Is handled?
     *
     * @return bool
     */
    public function isHandled(): bool
    {
        return $this->handled;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->handled = true;

        if (null !== $this->callback) {
            return ($this->callback)();
        }

        return new Response();
    }
}