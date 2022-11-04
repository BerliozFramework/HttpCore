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

namespace Berlioz\Http\Core\Tests\Http;

use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Message\Request;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\ServiceContainer\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class HttpHandlerTest extends TestCase
{
    public function testAddMiddleware()
    {
        $handler = new FakeHttpHandler(new Container(), new FakeRequestHandler(), new FakeErrorHandler());

        $this->assertCount(0, $handler->getMiddlewares());

        $handler->addMiddleware('FakeMiddleware', 'FakeMiddleware2');

        $this->assertCount(2, $handler->getMiddlewares());
        $this->assertContains('FakeMiddleware', $handler->getMiddlewares());
        $this->assertContains('FakeMiddleware2', $handler->getMiddlewares());
    }

    public function testHandle()
    {
        $handler = new FakeHttpHandler(
            new Container(),
            $requestHandler = new FakeRequestHandler(),
            $errorHandler = new FakeErrorHandler()
        );
        $handler->addMiddleware($middleware = new FakeMiddleware());
        $handler->addMiddleware($middleware2 = new FakeMiddleware());

        $this->assertFalse($requestHandler->isHandled());
        $this->assertFalse($errorHandler->isHandled());
        $this->assertFalse($middleware->isProcessed());
        $this->assertFalse($middleware2->isProcessed());

        $handler->handle(
            new ServerRequest(Request::HTTP_METHOD_GET, 'https://getberlioz.com')
        );

        $this->assertTrue($requestHandler->isHandled());
        $this->assertFalse($errorHandler->isHandled());
        $this->assertTrue($middleware->isProcessed());
        $this->assertTrue($middleware2->isProcessed());
    }

    public function testHandle_errorRequestHandler()
    {
        $handler = new FakeHttpHandler(
            new Container(),
            $requestHandler = new FakeRequestHandler(fn() => throw new NotFoundHttpException()),
            $errorHandler = new FakeErrorHandler()
        );
        $handler->addMiddleware($middleware = new FakeMiddleware());
        $handler->addMiddleware($middleware2 = new FakeMiddleware());

        $this->assertFalse($requestHandler->isHandled());
        $this->assertFalse($errorHandler->isHandled());
        $this->assertFalse($middleware->isProcessed());
        $this->assertFalse($middleware2->isProcessed());

        $handler->handle(
            new ServerRequest(Request::HTTP_METHOD_GET, 'https://getberlioz.com')
        );

        $this->assertTrue($requestHandler->isHandled());
        $this->assertTrue($errorHandler->isHandled());
        $this->assertTrue($middleware->isProcessed());
        $this->assertTrue($middleware2->isProcessed());
    }

    public function testHandle_errorMiddleware()
    {
        $handler = new FakeHttpHandler(
            new Container(),
            $requestHandler = new FakeRequestHandler(),
            $errorHandler = new FakeErrorHandler()
        );
        $handler->addMiddleware($middleware = new FakeMiddleware(fn() => throw new NotFoundHttpException()));
        $handler->addMiddleware($middleware2 = new FakeMiddleware());

        $this->assertFalse($requestHandler->isHandled());
        $this->assertFalse($errorHandler->isHandled());
        $this->assertFalse($middleware->isProcessed());
        $this->assertFalse($middleware2->isProcessed());

        $handler->handle(
            new ServerRequest(Request::HTTP_METHOD_GET, 'https://getberlioz.com')
        );

        $this->assertFalse($requestHandler->isHandled());
        $this->assertTrue($errorHandler->isHandled());
        $this->assertTrue($middleware->isProcessed());
        $this->assertFalse($middleware2->isProcessed());
    }

    public function testHandle_withStringMiddleware()
    {
        $handler = new FakeHttpHandler(
            new Container(),
            $requestHandler = new FakeRequestHandler(),
            $errorHandler = new FakeErrorHandler()
        );
        $handler->addMiddleware(FakeMiddleware::class);
        $handler->addMiddleware(FakeMiddleware::class);

        $handler->handle(
            new ServerRequest(Request::HTTP_METHOD_GET, 'https://getberlioz.com')
        );

        $this->assertTrue($requestHandler->isHandled());
        $this->assertFalse($errorHandler->isHandled());
    }

    public function testHandle_withInvalidStringMiddleware()
    {
        $handler = new FakeHttpHandler(
            new Container(),
            $requestHandler = new FakeRequestHandler(),
            $errorHandler = new FakeErrorHandler()
        );
        $handler->addMiddleware(FakeMiddleware::class);
        $handler->addMiddleware(stdClass::class);

        $handler->handle(
            new ServerRequest(Request::HTTP_METHOD_GET, 'https://getberlioz.com')
        );

        $this->assertFalse($requestHandler->isHandled());
        $this->assertTrue($errorHandler->isHandled());
    }
}
