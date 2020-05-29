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

namespace Berlioz\HttpCore\Tests\Controller;

use Berlioz\HttpCore\Controller\AbstractController;

class FakeController extends AbstractController
{
    public function addFlash($type, $message): AbstractController
    {
        return parent::addFlash($type, $message);
    }
}