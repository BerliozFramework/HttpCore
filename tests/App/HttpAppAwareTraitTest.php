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

use Berlioz\Core\Core;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\App\HttpAppAwareTrait;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class HttpAppAwareTraitTest extends TestCase
{
    public function test()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $trait = new class {
            use HttpAppAwareTrait;
        };
        $trait->setApp($app);

        $this->assertTrue($trait->hasApp());
        $this->assertSame($trait->getApp(), $app);
    }

    public function testEmpty()
    {
        $trait = new class {
            use HttpAppAwareTrait;
        };

        $this->assertFalse($trait->hasApp());
        $this->assertNull($trait->getApp());
    }
}
