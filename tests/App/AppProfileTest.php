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

use Berlioz\FlashBag\FlashBag;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\App\AppProfile;
use Berlioz\Http\Core\Tests\AbstractTestCase;
use Locale;

class AppProfileTest extends AbstractTestCase
{
    public function getAppAndProfile(): array
    {
        $profile = new AppProfile($app = $this->getApp());

        return [$app, $profile];
    }

    public function testDebugInfo()
    {
        list(1 => $profile) = $this->getAppAndProfile();

        $this->assertEmpty($profile->__debugInfo());
    }

    public function testGetEnv()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertSame($app->getCore()->getEnv(), $profile->getEnv());
    }

    public function testGetConfig()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertSame($app->getConfig(), $profile->getConfig());
    }

    public function testGetAssets()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertSame($app->getAssets(), $profile->getAssets());
    }

    public function testGetFlashBag()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertSame($app->get(FlashBag::class), $profile->getFlashBag());
    }

    public function testGetRequest_NULL()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertNull($app->getRequest());
        $this->assertSame($app->getRequest(), $profile->getRequest());
    }

    public function testGetRequest()
    {
        list($app, $profile) = $this->getAppAndProfile();
        $app->handle($request = new ServerRequest('GET', '/controller1/method1?foo=bar&qux=quux'));

        $this->assertSame($request, $app->getRequest());
        $this->assertSame($app->getRequest(), $profile->getRequest());
    }

    public function testGetRoute_NULL()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertNull($app->getRoute());
        $this->assertSame($app->getRoute(), $profile->getRoute());
    }

    public function testGetRoute()
    {
        list($app, $profile) = $this->getAppAndProfile();
        $app->handle(new ServerRequest('GET', '/controller1/method1?foo=bar&qux=quux'));

        $this->assertEquals('c1m1', $app->getRoute()->getName());
        $this->assertSame($app->getRoute(), $profile->getRoute());
    }

    public function testGetLocale()
    {
        list($app, $profile) = $this->getAppAndProfile();
        $app->handle(new ServerRequest('GET', '/controller1/method1?foo=bar&qux=quux'));

        $this->assertEquals(Locale::getDefault(), $app->getCore()->getLocale());
        $this->assertSame($app->getCore()->getLocale(), $profile->getLocale());
    }

    public function testIsDebugEnabled_enabled()
    {
        list($app, $profile) = $this->getAppAndProfile();
        $app->getCore()->getDebug()->setEnabled(true);

        $this->assertTrue($app->getCore()->getDebug()->isEnabled());
        $this->assertSame($app->getCore()->getDebug()->isEnabled(), $profile->isDebugEnabled());
    }

    public function testIsDebugEnabled_disabled()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertFalse($app->getCore()->getDebug()->isEnabled());
        $this->assertSame($app->getCore()->getDebug()->isEnabled(), $profile->isDebugEnabled());
    }

    public function testGetDebugUniqid()
    {
        list($app, $profile) = $this->getAppAndProfile();

        $this->assertNotEmpty($app->getCore()->getDebug()->getUniqid());
        $this->assertSame($app->getCore()->getDebug()->getUniqid(), $profile->getDebugUniqid());
    }
}
