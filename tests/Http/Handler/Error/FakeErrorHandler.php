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

namespace Berlioz\Http\Core\Tests\Http\Handler\Error;

use Berlioz\Http\Message\Response;
use Berlioz\Http\Core\Http\Handler\Error\ErrorHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class FakeErrorHandler implements ErrorHandlerInterface
{
    public static bool $handled = false;

    public function handle(ServerRequestInterface $request, ?Throwable $throwable = null): ResponseInterface
    {
        static::$handled = true;

        return new Response();
    }
}