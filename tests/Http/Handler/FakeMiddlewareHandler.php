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

namespace Berlioz\Http\Core\Tests\Http\Handler;

use Berlioz\Http\Core\Http\Handler\MiddlewareHandler;
use Psr\Http\Server\MiddlewareInterface;

class FakeMiddlewareHandler extends MiddlewareHandler
{
    public function getMiddleware(): MiddlewareInterface|string
    {
        return $this->middleware;
    }
}