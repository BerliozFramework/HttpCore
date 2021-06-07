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

use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\Exception\HttpAppException;
use Berlioz\Http\Core\Http\Handler\MiddlewareHandler;
use Berlioz\Http\Core\Tests\Http\FakeMiddleware;
use Berlioz\Http\Core\Tests\Http\FakeRequestHandler;
use Berlioz\ServiceContainer\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class MiddlewareHandlerTest extends TestCase
{
    public function testHandle()
    {
        $handler = new MiddlewareHandler(
            new Container(),
            $middleware = new FakeMiddleware(),
            $handler2 = new FakeRequestHandler()
        );
        $handler->handle(new ServerRequest('GET', '/'));

        $this->assertTrue($middleware->isProcessed());
        $this->assertTrue($handler2->isHandled());
    }

    public function testHandle_stringMiddleware()
    {
        $handler = new FakeMiddlewareHandler(
            new Container(),
            FakeMiddleware::class,
            $handler2 = new FakeRequestHandler()
        );
        $handler->handle(new ServerRequest('GET', '/'));

        $this->assertTrue($handler->getMiddleware()->isProcessed());
        $this->assertTrue($handler2->isHandled());
    }

    public function testHandle_stringMiddlewareNotValid()
    {
        $this->expectException(HttpAppException::class);

        $handler = new FakeMiddlewareHandler(
            new Container(),
            stdClass::class,
            new FakeRequestHandler()
        );
        $handler->handle(new ServerRequest('GET', '/'));
    }

    public function testHandle_stringMiddlewareFailed()
    {
        $this->expectException(HttpAppException::class);

        $handler = new FakeMiddlewareHandler(
            new Container(),
            FakeFailedMiddlewareHandler::class,
            new FakeRequestHandler()
        );
        $handler->handle(new ServerRequest('GET', '/'));
    }

    public function testHandle_stringMiddlewareFailed2()
    {
        $this->expectException(HttpAppException::class);

        $handler = new FakeMiddlewareHandler(
            new Container(),
            FakeFailedMiddlewareHandler2::class,
            new FakeRequestHandler()
        );
        $handler->handle(new ServerRequest('GET', '/'));
    }
}
