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

namespace Berlioz\Http\Core\Tests\Controller;

use Berlioz\Core\Core;
use Berlioz\FlashBag\FlashBag;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AbstractControllerTest extends TestCase
{
    public function testSerialize()
    {
        $this->expectException(RuntimeException::class);

        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController();
        $controller->setApp($app);
        serialize($controller);
    }

    public function testAddFlash()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $controller = new FakeController();
        $controller->setApp($app);

        /** @var FlashBag $flashBagService */
        $this->assertCount(0, $flashBagService = $app->get(FlashBag::class));

        $controller->addFlash(FlashBag::TYPE_SUCCESS, 'Foo');
        $controller->addFlash(FlashBag::TYPE_WARNING, 'Bar');

        $this->assertCount(2, $flashBagService);
        $this->assertEquals('Foo', $flashBagService->get(FlashBag::TYPE_SUCCESS)[0]);
    }
}
