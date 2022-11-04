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

namespace Berlioz\Http\Core\Tests\App;

use Berlioz\Config\Adapter\ArrayAdapter;
use Berlioz\Core\Core;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\App\Maintenance;
use Berlioz\Http\Core\TestProject\Controller\ControllerOne;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use Berlioz\Http\Core\TestProject\Http\Middleware\AbstractMiddleware;
use Berlioz\Http\Core\TestProject\Http\Middleware\BarMiddleware;
use Berlioz\Http\Core\TestProject\Http\Middleware\BazMiddleware;
use Berlioz\Http\Core\TestProject\Http\Middleware\FooMiddleware;
use Berlioz\Http\Core\TestProject\Http\Middleware\QuxMiddleware;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Router\Route;
use Berlioz\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

class HttpAppTest extends TestCase
{
    public function test__construct()
    {
        $app = new HttpApp($core = new Core(new FakeDefaultDirectories(), false));

        $this->assertInstanceOf(HttpApp::class, $app);
        $this->assertSame($app->getCore(), $core);
    }

    public function testGetMaintenance_disabled()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));

        $this->assertNull($app->getMaintenance());
    }

    public function testGetMaintenance_enabled()
    {
        $core = new Core(new FakeDefaultDirectories(), false);
        $core->getConfig()->addConfig(new ArrayAdapter(['berlioz' => ['maintenance' => true]]));
        $app = new HttpApp($core);

        $this->assertInstanceOf(Maintenance::class, $app->getMaintenance());
    }

    public function testGetRouter()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));

        $this->assertInstanceOf(RouterInterface::class, $app->getRouter());
        $this->assertNotEmpty($app->getRouter()->getRoutes());
    }

    public function testGetRequest()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $app->handle($request = new ServerRequest('GET', 'https://getberlioz.com'));

        $this->assertSame($request, $app->getRequest());
    }

    public function testGetRoute()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $app->handle(new ServerRequest('GET', '/controller1/method1'));

        $this->assertInstanceOf(Route::class, $app->getRoute());
        $this->assertEquals(
            [
                ControllerOne::class,
                'methodOne'
            ],
            $app->getRoute()->getContext()
        );
    }

    public function testGetRoute_NULL()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $app->handle(new ServerRequest('GET', '/unknown'));

        $this->assertNull($app->getRoute());
    }

    public function testHandle()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $response = $app->handle($serverRequest = new ServerRequest('GET', '/controller2/foo/method1'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ControllerTwo::methodOne', (string)$response->getBody());
        $this->assertEmpty($serverRequest->getAttributes());
        $this->assertEquals(['attribute1' => 'foo'], $app->getRequest()->getAttributes());
    }

    public function testHandle_withMiddlewaresOrdered()
    {
        AbstractMiddleware::$calls = [];
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $app->handle(new ServerRequest('GET', 'https://getberlioz.com'));

        $this->assertSame(
            [
                FooMiddleware::class,
                BarMiddleware::class,
                BazMiddleware::class,
                QuxMiddleware::class,
            ],
            AbstractMiddleware::$calls,
        );
    }

    public function testPrint()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $response = new Response(
            $body = 'FOO BAR',
            200,
            ['MyFirstHeader' => 'Value1', 'MySecondHeader' => ['Value2', 'Value3']],
            'Hummmm OKKK!'
        );

        $this->expectOutputString($body);
        $app->print($response);
    }

    public function testPrint_alreadyPrinted()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $app->setPrinted(true);
        $response = new Response(
            'FOO BAR',
            200,
            ['MyFirstHeader' => 'Value1', 'MySecondHeader' => ['Value2', 'Value3']],
            'Hummmm OKKK!'
        );

        $this->expectOutputString('');
        $app->print($response);
    }

    public function testPrint_streamNotSeekable()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $response = new Response(
            popen('echo FOO BAR', 'r'),
            200,
            ['MyFirstHeader' => 'Value1', 'MySecondHeader' => ['Value2', 'Value3']],
            'Hummmm OKKK!'
        );

        $this->expectOutputString("FOO BAR\n");
        $app->print($response);
    }
}
