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

namespace Berlioz\Http\Core\TestProject\Controller;

use Berlioz\Http\Core\Attribute\Route;
use Berlioz\Http\Core\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;

class ControllerOne extends AbstractController
{
    #[Route('/controller1/method1', name: 'c1m1')]
    public function methodOne(): ResponseInterface
    {
        return $this->response('FOO BAR');
    }

    #[Route('/controller2/method2')]
    public function methodTwo(): ResponseInterface
    {
        return $this->response();
    }
}