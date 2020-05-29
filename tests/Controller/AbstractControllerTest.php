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

namespace Berlioz\HttpCore\Tests\Controller;

use Berlioz\Core\Core;
use Berlioz\FlashBag\FlashBag;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\Http\Message\Uri;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\TestProject\FakeDefaultDirectories;
use Berlioz\Router\Route;
use Berlioz\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

class AbstractControllerTest extends TestCase
{
    public function testSleep()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController($app);

        $this->expectException(\RuntimeException::class);

        serialize($controller);
    }

    public function testGetRouter()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController($app);

        $this->assertInstanceOf(RouterInterface::class, $controller->getRouter());
        $this->assertSame($app->getRouter(), $controller->getRouter());
    }

    public function testGetRoute()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController($app);

        $this->assertNull($controller->getRoute());

        $app->handle(
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

        $this->assertInstanceOf(Route::class, $controller->getRoute());
        $this->assertSame($controller->getRoute(), $app->getRoute());
    }

    public function testRedirect()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController($app);

        $response = $controller->redirect('/foo', 301);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/foo', reset($locationHeader));
        $this->assertEmpty((string)$response->getBody());

        $response = $controller->redirect('/bar', 302, new Response('Foo'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/bar', reset($locationHeader));
        $this->assertEquals('Foo', (string)$response->getBody());
    }

    public function testReload()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController($app);
        $app->handle(
            $serverRequest =
                new ServerRequest(
                    'GET',
                    URI::createFromString('/controller2/foo/method1?foo=bar&qux=quux'),
                    [],
                    [],
                    [],
                    new Stream()
                )
        );

        $response = $controller->reload([], false);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1', reset($locationHeader));

        $response = $controller->reload(['bar' => 'foo'], false);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1?bar=foo', reset($locationHeader));

        $response = $controller->reload([], true);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1?foo=bar&qux=quux', reset($locationHeader));

        $response = $controller->reload(['bar' => 'foo'], true);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1?foo=bar&qux=quux&bar=foo', reset($locationHeader));
    }

    public function testAddFlash()
    {
        $app = new HttpApp($core = new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController($app);

        /** @var FlashBag $flashBagService */
        $this->assertCount(0, $flashBagService= $app->getService(FlashBag::class));

        $controller->addFlash(FlashBag::TYPE_SUCCESS, 'Foo');
        $controller->addFlash(FlashBag::TYPE_WARNING, 'Bar');

        $this->assertCount(2, $flashBagService);
        $this->assertEquals('Foo', $flashBagService->get(FlashBag::TYPE_SUCCESS)[0]);
    }
}
