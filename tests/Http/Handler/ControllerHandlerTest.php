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

use Berlioz\Core\Core;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Core\Exception\Http\NotImplementedHttpException;
use Berlioz\Http\Core\Http\Handler\ControllerHandler;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use Berlioz\Http\Core\Tests\App\FakeHttpApp;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ControllerHandlerTest extends TestCase
{
    public function testHandle()
    {
        $request = new ServerRequest('GET', '/controller2/foo/method1');
        $handler = new ControllerHandler($app = new FakeHttpApp(new Core(new FakeDefaultDirectories(), cache: false)));
        $app->setRoute($app->findRoute($request));
        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(Response::HTTP_STATUS_OK, $response->getStatusCode());
        $this->assertEquals('ControllerTwo::methodOne', (string)$response->getBody());
    }

    public function testHandle_withStringResponse()
    {
        $request = new ServerRequest('GET', '/controller2/foo/method3');
        $handler = new ControllerHandler($app = new FakeHttpApp(new Core(new FakeDefaultDirectories(), cache: false)));
        $app->setRoute($app->findRoute($request));
        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(Response::HTTP_STATUS_OK, $response->getStatusCode());
        $this->assertEquals('ControllerTwo::methodThree', (string)$response->getBody());
    }

    public function testHandle_withArrayResponse()
    {
        $request = new ServerRequest('GET', '/controller2/foo/method4');
        $handler = new ControllerHandler($app = new FakeHttpApp(new Core(new FakeDefaultDirectories(), cache: false)));
        $app->setRoute($app->findRoute($request));
        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(Response::HTTP_STATUS_OK, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals('["ControllerTwo","methodFour"]', (string)$response->getBody());
    }

    public function testHandle_withObjectResponse()
    {
        $request = new ServerRequest('GET', '/controller2/foo/method5');
        $handler = new ControllerHandler($app = new FakeHttpApp(new Core(new FakeDefaultDirectories(), cache: false)));
        $app->setRoute($app->findRoute($request));
        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(Response::HTTP_STATUS_OK, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals('{"contents":"ControllerTwo::methodFive"}', (string)$response->getBody());
    }

    public function testHandle_withNullResponse()
    {
        $request = new ServerRequest('GET', '/controller2/foo/method6');
        $handler = new ControllerHandler($app = new FakeHttpApp(new Core(new FakeDefaultDirectories(), cache: false)));
        $app->setRoute($app->findRoute($request));
        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(Response::HTTP_STATUS_NO_CONTENT, $response->getStatusCode());
    }

    public function testHandle_controllerThrowHttpException()
    {
        $this->expectException(NotImplementedHttpException::class);

        $request = new ServerRequest('GET', '/controller2/foo/method2');
        $handler = new ControllerHandler($app = new FakeHttpApp(new Core(new FakeDefaultDirectories(), cache: false)));
        $app->setRoute($app->findRoute($request));
        $handler->handle($request);
    }

    public function testHandle_routeNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $handler = new ControllerHandler(new HttpApp(new Core(new FakeDefaultDirectories(), cache: false)));
        $handler->handle(new ServerRequest('GET', '/not-found'));
    }
}
