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

use Berlioz\Config\Adapter\ArrayAdapter;
use Berlioz\Core\Core;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Exception\Http\ServiceUnavailableHttpException;
use Berlioz\Http\Core\Http\Handler\MaintenanceHandler;
use Berlioz\Http\Core\Tests\TestDirectories;
use PHPUnit\Framework\TestCase;

class MaintenanceHandlerTest extends TestCase
{
    public function testHandle()
    {
        $handler = new MaintenanceHandler();
        $handler->setApp($app = new HttpApp(new Core(new TestDirectories(), cache: false)));
        $app->getCore()->getConfig()->addConfig(
            new ArrayAdapter(
                [
                    'berlioz' => [
                        'directories' => ['templates' => __DIR__ . '/../../../tests_env/resources/templates']
                    ],
                    'twig' => [
                        'paths' => ['Berlioz-HttpCore' => __DIR__ . '/../../../resources']
                    ]
                ]
            )
        );
        $handler->setTwig($app->get('twig'));
        $response = $handler->handle(new ServerRequest('GET', '/'));

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('Maintenance in progress', (string)$response->getBody());
    }

    public function testHandle_error()
    {
        $this->expectException(ServiceUnavailableHttpException::class);
        $handler = new MaintenanceHandler();
        $handler->setApp(new HttpApp(new Core(new TestDirectories(), cache: false)));
        $response = $handler->handle(new ServerRequest('GET', '/'));
    }
}
