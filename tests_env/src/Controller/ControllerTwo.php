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

use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\Stream\MemoryStream;
use Berlioz\Http\Core\Attribute\Route;
use Berlioz\Http\Core\Controller\AbstractController;
use Berlioz\Http\Core\Exception\Http\NotImplementedHttpException;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class ControllerTwo extends AbstractController
{
    #[Route('/controller2/{attribute1}/method1', name: 'c2m1')]
    public function methodOne(
        Response $response
    ): ResponseInterface {
        return $response->withBody(new MemoryStream('ControllerTwo::methodOne'));
    }

    #[Route('/controller2/{attribute1}/method2', name: 'c2m2')]
    public function methodTwo(): ResponseInterface
    {
        throw new NotImplementedHttpException();
    }

    #[Route('/controller2/{attribute1}/method3', name: 'c2m3')]
    public function methodThree(): string
    {
        return 'ControllerTwo::methodThree';
    }

    #[Route('/controller2/{attribute1}/method4', name: 'c2m4')]
    public function methodFour(): array
    {
        return ['ControllerTwo', 'methodFour'];
    }

    #[Route('/controller2/{attribute1}/method5', name: 'c2m5')]
    public function methodFive(): stdClass
    {
        $obj = new stdClass();
        $obj->contents = 'ControllerTwo::methodFive';

        return $obj;
    }

    #[Route('/controller2/{attribute1}/method6', name: 'c2m6')]
    public function methodSix()
    {
        return null;
    }
}