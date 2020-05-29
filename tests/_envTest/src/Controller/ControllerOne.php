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

namespace Berlioz\HttpCore\TestProject\Controller;

use Berlioz\Http\Message\Response;
use Berlioz\HttpCore\Controller\AbstractController;

class ControllerOne extends AbstractController
{
    /**
     * @return Response
     * @route('/controller1/method1')
     */
    public function methodOne()
    {
        return new Response('FOO BAR');
    }

    /**
     * @return Response
     * @route('/controller2/method2')
     */
    public function methodTwo()
    {
        return new Response();
    }
}