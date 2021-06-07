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
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Helper\ReloadHelperTrait;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use LogicException;
use PHPUnit\Framework\TestCase;

class ReloadHelperTraitTest extends TestCase
{
    private function getHelper()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $app->handle(new ServerRequest('GET', '/controller2/foo/method1?foo=bar&qux=quux'));

        $helper = new class {
            use ReloadHelperTrait {
                reload as public;
            }
        };
        $helper->setApp($app);

        return $helper;
    }

    public function testReload_withoutServerRequest()
    {
        $this->expectException(LogicException::class);

        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $helper = new class {
            use ReloadHelperTrait {
                reload as public;
            }
        };
        $helper->setApp($app);

        $helper->reload();
    }

    public function testReload_withoutQueryParams()
    {
        $helper = $this->getHelper();
        $response = $helper->reload([], false);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1', reset($locationHeader));
    }

    public function testReload_withQueryParams()
    {
        $helper = $this->getHelper();
        $response = $helper->reload(['bar' => 'foo'], false);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1?bar=foo', reset($locationHeader));
    }

    public function testReload_withoutQueryParamsAndMerge()
    {
        $helper = $this->getHelper();
        $response = $helper->reload([], true);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1?foo=bar&qux=quux', reset($locationHeader));
    }

    public function testReload_withQueryParamsAndMerge()
    {
        $helper = $this->getHelper();
        $response = $helper->reload(['bar' => 'foo'], true);

        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/controller2/foo/method1?foo=bar&qux=quux&bar=foo', reset($locationHeader));
    }
}
