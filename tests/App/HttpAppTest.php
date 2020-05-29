<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\HttpCore\Tests\App;

use Berlioz\Core\Core;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\Http\Message\Uri;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\TestProject\Controller\ControllerOne;
use Berlioz\HttpCore\TestProject\FakeDefaultDirectories;
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

    public function redirectionsProvider()
    {
        return [
            ['/old', '/new', 301],
            ['/old/foo', null, 301],
            ['/old-route/foo', '/new-route/foo', 301],
            ['/old-route/foo/bar', '/new-route/foo/bar', 301],
            ['/old-route/', '/new-route/', 301],
            ['/another-old-route/', '/new/', 301],
            ['/another-old-route/foo/bar', '/new/foo/bar', 301],
            ['/foo', '/bar', 302],
        ];
    }

    /**
     * @dataProvider redirectionsProvider
     */
    public function testHttpRedirection($src, $dst, $status)
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));

        $response = $app->httpRedirection(Uri::createFromString($src));

        if (null === $dst) {
            $this->assertNull($response);
            return;
        }

        $this->assertNotNull($response);

        $locationHeader = $response->getHeader('Location');

        $this->assertCount(1, $locationHeader);
        $this->assertEquals($dst, reset($locationHeader));
        $this->assertEquals($status, $response->getStatusCode());
    }

    public function testPrintResponse()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $response = new Response(
            $body = 'FOO BAR',
            200,
            $headers = ['MyFirstHeader' => 'Value1', 'MySecondHeader' => ['Value2', 'Value3']],
            'Hummmm OKKK!'
        );

        $this->expectOutputString($body);
        $app->printResponse($response);
    }

    public function testHandle()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $response = $app->handle(
            $serverRequest =
                new ServerRequest(
                    'GET',
                    URI::createFromString('/controller2/foo/method1'),
                    [],
                    [],
                    [],
                    new Stream()
                )
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('QUX', (string)$response->getBody());
        $this->assertEmpty($serverRequest->getAttributes());
        $this->assertEquals(['attribute1' => 'foo'], $app->getRouter()->getServerRequest()->getAttributes());
    }

    public function testGetRoute()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $app->handle(
            new ServerRequest(
                'GET',
                URI::createFromString('/controller1/method1'),
                [],
                [],
                [],
                new Stream()
            )
        );

        $this->assertInstanceOf(Route::class, $app->getRoute());
        $this->assertEquals(
            [
                '_class' => ControllerOne::class,
                '_method' => 'methodOne'
            ],
            $app->getRoute()->getContext()
        );

        $app->handle(
            new ServerRequest(
                'GET',
                URI::createFromString('/unknown'),
                [],
                [],
                [],
                new Stream()
            )
        );

        $this->assertNull($app->getRoute());
    }

    public function testGetRouter()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));

        $this->assertInstanceOf(RouterInterface::class, $app->getRouter());
        $this->assertNotEmpty($app->getRouter()->getRouteSet());
    }
}
