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

namespace Berlioz\Http\Core\Tests\App;

use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\App\Maintenance;
use Berlioz\Router\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

class FakeHttpApp extends HttpApp
{
    public function findRoute(ServerRequestInterface &$request): RouteInterface
    {
        return parent::findRoute($request);
    }

    public function setRoute(?RouteInterface $route): void
    {
        $this->route = $route;
    }

    public function setMaintenance(?Maintenance $maintenance): void
    {
        $this->maintenance = $maintenance;
    }
}