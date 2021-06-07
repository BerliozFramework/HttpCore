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

namespace Berlioz\Http\Core\Tests;

use Berlioz\Config\Adapter\JsonAdapter;
use Berlioz\Http\Core\BerliozPackage;
use PHPUnit\Framework\TestCase;

class BerliozPackageTest extends TestCase
{
    public function testConfig()
    {
        $configFromPackage = (new BerliozPackage)->config();
        $config = new JsonAdapter(__DIR__ . '/../resources/config.default.json', true);

        $this->assertEquals($config->getArrayCopy(), $configFromPackage->getArrayCopy());
    }
}
