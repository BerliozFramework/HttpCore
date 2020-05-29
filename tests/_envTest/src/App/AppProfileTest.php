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

namespace Berlioz\HttpCore\TestProject\App;

use Berlioz\Core\Asset\Assets;
use Berlioz\Core\Core;
use Berlioz\FlashBag\FlashBag;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\Http\Message\Uri;
use Berlioz\HttpCore\App\AppProfile;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\TestProject\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class AppProfileTest extends TestCase
{
    public function testGetters()
    {
        $app = new HttpApp($core = new Core(new FakeDefaultDirectories(), false));
        $appProfile = new AppProfile($core, $app);

        $this->assertSame($core->getServiceContainer()->get(Assets::class), $appProfile->getAssets());
        $this->assertSame($core->getLocale(), $appProfile->getLocale());
        $this->assertNull($appProfile->getRoute());
        $this->assertSame($app->getRoute(), $appProfile->getRoute());
        $this->assertEquals($core->getDebug()->getUniqid(), $appProfile->getDebugUniqid());
        $this->assertSame($core->getConfig(), $appProfile->getConfig());
        $this->assertSame($app->getRouter()->getServerRequest(), $appProfile->getRequest());

        $app->handle(
            $serverRequest =
                new ServerRequest(
                    'GET',
                    URI::createFromString('/controller1/method1?foo=bar&qux=quux'),
                    [],
                    [],
                    [],
                    new Stream()
                )
        );

        $this->assertSame($app->getRoute(), $appProfile->getRoute());
        $this->assertSame($serverRequest, $appProfile->getRequest());
        $this->assertSame($app->getService(FlashBag::class), $appProfile->getFlashBag());
        $this->assertSame($core->getDebug()->isEnabled(), $appProfile->isDebugEnabled());
        $this->assertEmpty($appProfile->__debugInfo());
    }
}
