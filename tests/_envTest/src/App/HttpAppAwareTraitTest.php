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

use Berlioz\Core\Core;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\App\HttpAppAwareTrait;
use Berlioz\HttpCore\TestProject\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class HttpAppAwareTraitTest extends TestCase
{
    public function test()
    {
        $app =  new HttpApp(new Core(new FakeDefaultDirectories(), false));
        $obj = new class {
            use HttpAppAwareTrait;
        };

        $this->assertFalse($obj->hasApp());
        $this->assertNull($obj->getApp());

        $obj->setApp($app);

        $this->assertTrue($obj->hasApp());
        $this->assertSame($obj->getApp(), $app);
    }
}
