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

namespace Berlioz\Http\Core\Tests\Helper;

use Berlioz\Core\Core;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Helper\RouterHelperTrait;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use Berlioz\Http\Core\Tests\AbstractTestCase;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Router\Exception\RoutingException;
use Berlioz\Router\Route;
use Berlioz\Router\RouterInterface;

class RouterHelperTraitTest extends AbstractTestCase
{
    private function getHelper()
    {
        $helper = new class {
            use RouterHelperTrait {
                getRouter as public;
                getRoute as public;
                path as public;
                finalize_path as public;
            }
        };
        $helper->setApp(new HttpApp(new Core(new FakeDefaultDirectories(), false)));

        return $helper;
    }

    public function testGetRouter()
    {
        $helper = $this->getHelper();

        $this->assertInstanceOf(RouterInterface::class, $this->getHelper()->getRouter());
        $this->assertSame($helper->getApp()->getRouter(), $helper->getRouter());
    }

    public function testGetRoute()
    {
        $helper = $this->getHelper();
        $helper->getApp()->handle(new ServerRequest('GET', '/controller2/foo/method1'));

        $this->assertInstanceOf(Route::class, $helper->getRoute());
        $this->assertSame($helper->getRoute(), $helper->getApp()->getRoute());
    }

    public function testGetRoute_NULL()
    {
        $helper = $this->getHelper();

        $this->assertNull($helper->getRoute());
    }

    public function testPath()
    {
        $helper = $this->getHelper();

        $this->assertEquals('/controller1/method1', (string)$helper->path('c1m1'));
    }

    public function testPath_withPrefix()
    {
        $_SERVER['HTTP_X_FORWARDED_PREFIX'] = '/super-prefix';

        $helper = $this->getHelper();

        $this->assertEquals('/super-prefix/controller1/method1', (string)$helper->path('c1m1'));
    }

    public function testPath_withRoute()
    {
        $helper = $this->getHelper();

        $router = $helper->getApp()->getRouter();
        $this->assertEquals('/controller1/method1', (string)$helper->path($router->getRoute('c1m1')));
    }

    public function testPath_missingAttribute()
    {
        $this->expectException(RoutingException::class);
        $helper = $this->getHelper();
        $helper->path('c2m1');
    }

    public function testFinalizePath_withoutPrefix()
    {
        $helper = $this->getHelper();

        $this->assertEquals('foo', (string)$helper->finalize_path('foo'));
    }

    public function testFinalizePath_withPrefix()
    {
        $_SERVER['HTTP_X_FORWARDED_PREFIX'] = '/super-prefix';

        $helper = $this->getHelper();

        $this->assertEquals('/super-prefix/foo', (string)$helper->finalize_path('/foo'));
    }

    public function testFinalizePath_withPrefixAndEmptyPath()
    {
        $_SERVER['HTTP_X_FORWARDED_PREFIX'] = '/super-prefix';

        $helper = $this->getHelper();

        $this->assertEquals('/super-prefix/', (string)$helper->finalize_path(''));
    }
}
