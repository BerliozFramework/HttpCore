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

declare(strict_types=1);

namespace Berlioz\Http\Core\Tests\Http;

use Berlioz\Http\Core\Http\Handler\Error\ErrorHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class FakeErrorHandler extends FakeRequestHandler implements ErrorHandlerInterface
{
    public function handle(ServerRequestInterface $request, ?Throwable $throwable = null): ResponseInterface
    {
        return parent::handle($request);
    }
}