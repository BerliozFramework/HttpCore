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

use Berlioz\Core\Directories\DefaultDirectories;

class TestDirectories extends DefaultDirectories
{
    public function getConfigDir(): string
    {
        return __DIR__ . '/../tests_env/config';
    }

    public function getVarDir(): string
    {
        return __DIR__ . '/../tests_env/var';
    }
}