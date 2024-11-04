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

use Berlioz\Core\Core;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use Berlioz\Http\Core\Tests\App\FakeHttpApp;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PREFIX'] = null;
    }

    /**
     * Get app.
     *
     * @return FakeHttpApp
     * @throws BerliozException
     */
    protected function getApp(): FakeHttpApp
    {
        return new FakeHttpApp(new Core(new FakeDefaultDirectories(), false));
    }
}