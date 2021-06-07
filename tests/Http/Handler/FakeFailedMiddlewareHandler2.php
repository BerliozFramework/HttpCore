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

namespace Berlioz\Http\Core\Tests\Http\Handler;

use Berlioz\Http\Message\Response;
use Berlioz\Http\Core\Exception\HttpAppException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FakeFailedMiddlewareHandler2 implements RequestHandlerInterface
{
    public function __construct()
    {
        throw new HttpAppException();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response();
    }
}