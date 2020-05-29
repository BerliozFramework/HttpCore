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
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\HttpCore\Controller\AbstractController;

class ControllerTwo extends AbstractController
{
    public function _b_pre(ServerRequest $request, Response $response)
    {
        $stream = new Stream();
        $stream->write('QUX');

        return $response->withBody($stream);
    }

    public function _b_post(Response $response)
    {
        return $response->withStatus(201);
    }

    /**
     * @route('/controller2/{attribute1}/method1')
     */
    public function methodOne(ServerRequest $request, Response $response)
    {
        return $response;
    }
}