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

namespace Berlioz\Http\Core\Tests\Http\Handler\Error;

use Berlioz\Config\Adapter\ArrayAdapter;
use Berlioz\Core\Core;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Core\Http\Handler\Error\DefaultErrorHandler;
use Berlioz\Http\Core\Tests\TestDirectories;
use Exception;
use PHPUnit\Framework\TestCase;

class DefaultErrorHandlerTest extends TestCase
{
    private function getHandler(): DefaultErrorHandler
    {
        $handler = new DefaultErrorHandler($app = new HttpApp(new Core(new TestDirectories(), cache: false)));
        $app->getCore()->getConfig()->addConfig(
            new ArrayAdapter(
                [
                    'berlioz' => [
                        'directories' => ['templates' => __DIR__ . '/../../../../tests_env/resources/templates']
                    ],
                    'twig' => [
                        'paths' => ['Berlioz-HttpCore' => __DIR__ . '/../../../../resources']
                    ]
                ]
            )
        );
        $handler->setTwig($app->get('twig'));

        return $handler;
    }

    public function testHandle_noException()
    {
        $handler = $this->getHandler();
        $response = $handler->handle(new ServerRequest('GET', '/'));

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('500 error', (string)$response->getBody());
        $this->assertStringContainsString('Looks like we\'re having some server issues', (string)$response->getBody());
    }

    public function testHandle_withException()
    {
        $handler = $this->getHandler();
        $response = $handler->handle(new ServerRequest('GET', '/'), new Exception());

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('500 error', (string)$response->getBody());
        $this->assertStringContainsString('Looks like we\'re having some server issues', (string)$response->getBody());
    }

    public function testHandle_withHttpException()
    {
        $handler = $this->getHandler();
        $response = $handler->handle(new ServerRequest('GET', '/'), new NotFoundHttpException());

        $this->assertEquals(Response::HTTP_STATUS_NOT_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('404 error', (string)$response->getBody());
        $this->assertStringContainsString('Oops, the page you\'re looking for doesn\'t exist', (string)$response->getBody());
    }
}
