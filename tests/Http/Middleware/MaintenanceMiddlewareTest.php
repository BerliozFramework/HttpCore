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

namespace Berlioz\Http\Core\Tests\Http\Middleware;

use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\App\Maintenance;
use Berlioz\Http\Core\Http\Middleware\MaintenanceMiddleware;
use Berlioz\Http\Core\Tests\AbstractTestCase;
use Berlioz\Http\Core\Tests\Http\FakeRequestHandler;

class MaintenanceMiddlewareTest extends AbstractTestCase
{
    public function testProcess_noMaintenance()
    {
        $middleware = new MaintenanceMiddleware($app = $this->getApp());
        $middleware->process(new ServerRequest('GET', '/'), $handler = new FakeRequestHandler());

        $this->assertNull($app->getMaintenance());
        $this->assertTrue($handler->isHandled());
    }

    public function testProcess_maintenance()
    {
        $middleware = new MaintenanceMiddleware($app = $this->getApp());
        $app->setMaintenance(new Maintenance(message: 'Foo bar', handler: FakeMaintenanceHandler::class));

        $this->assertInstanceOf(Maintenance::class, $app->getMaintenance());
        $this->assertFalse(FakeMaintenanceHandler::$handled);

        $middleware->process(new ServerRequest('GET', '/'), new FakeRequestHandler());

        $this->assertTrue(FakeMaintenanceHandler::$handled);
    }
}
