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
use Berlioz\Config\Adapter\JsonAdapter;
use Berlioz\Core\Core;
use Berlioz\Http\Core\TestProject\FakeDefaultDirectories;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Container\ServiceProvider;
use Berlioz\Http\Core\Exception\Http\ForbiddenHttpException;
use Berlioz\Http\Core\Exception\Http\InternalServerErrorHttpException;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Core\Http\Handler\Error\ErrorHandler;
use Berlioz\Http\Core\Tests\TestDirectories;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    private function getApp(?Core $core = null): HttpApp
    {
        $core = $core ?? new Core(new TestDirectories(), cache: false);
        $core->getContainer()->addProvider(new ServiceProvider($core));
        $app = new HttpApp($core);
        $app->getCore()->getConfig()->addConfig(
            new JsonAdapter(__DIR__ . '/../../../../resources/config.default.json', true),
            new ArrayAdapter(
                [
                    'berlioz' => [
                        'directories' => ['templates' => __DIR__ . '/../../../../tests_env/resources/templates']
                    ],
                    'twig' => [
                        'paths' => ['Berlioz-HttpCore' => __DIR__ . '/../../../../resources']
                    ]
                ],
                1
            )
        );

        return $app;
    }

    public function testHandle()
    {
        $app = $this->getApp();
        $app->getCore()->getConfig()->addConfig(
            new ArrayAdapter(
                [
                    'berlioz' => [
                        'http' => [
                            'errors' => [
                                'default' => FakeErrorHandler::class
                            ]
                        ]
                    ]
                ]
            )
        );
        $handler = new ErrorHandler($app);

        $this->assertFalse(FakeErrorHandler::$handled);

        $handler->handle(new ServerRequest('GET', '/'));

        $this->assertTrue(FakeErrorHandler::$handled);
    }

    public function testHandle_onSpecificErrorCore()
    {
        $app = $this->getApp();
        $app->getCore()->getConfig()->addConfig(
            new ArrayAdapter(
                [
                    'berlioz' => [
                        'http' => [
                            'errors' => [
                                '404' => FakeErrorHandler::class,
                                '403' => FakeErrorHandler2::class,
                            ]
                        ]
                    ]
                ]
            )
        );
        $handler = new ErrorHandler($app);

        FakeErrorHandler::$handled = FakeErrorHandler2::$handled = false;
        $this->assertFalse(FakeErrorHandler::$handled);
        $this->assertFalse(FakeErrorHandler2::$handled);

        $handler->handle(new ServerRequest('GET', '/'), new InternalServerErrorHttpException());

        $this->assertFalse(FakeErrorHandler::$handled);
        $this->assertFalse(FakeErrorHandler2::$handled);
        FakeErrorHandler::$handled = FakeErrorHandler2::$handled = false;
        $this->assertFalse(FakeErrorHandler::$handled);
        $this->assertFalse(FakeErrorHandler2::$handled);

        $handler->handle(new ServerRequest('GET', '/'), new NotFoundHttpException());

        $this->assertTrue(FakeErrorHandler::$handled);
        $this->assertFalse(FakeErrorHandler2::$handled);
        FakeErrorHandler::$handled = FakeErrorHandler2::$handled = false;
        $this->assertFalse(FakeErrorHandler::$handled);
        $this->assertFalse(FakeErrorHandler2::$handled);

        $handler->handle(new ServerRequest('GET', '/'), new ForbiddenHttpException());

        $this->assertFalse(FakeErrorHandler::$handled);
        $this->assertTrue(FakeErrorHandler2::$handled);
    }

    public function testHandle_default()
    {
        $app = $this->getApp();
        $handler = new ErrorHandler($app);
        $response = $handler->handle(new ServerRequest('GET', '/'));

        $this->assertStringContainsString('500 error', (string)$response->getBody());
        $this->assertStringContainsString('Looks like we\'re having some server issues', (string)$response->getBody());
        $this->assertStringNotContainsString('<pre>', (string)$response->getBody());
    }

    public function testHandle_defaultDebugEnabled()
    {
        $core = new Core(new FakeDefaultDirectories(), cache: false);
        $core->getDebug()->setEnabled(true);
        $app = $this->getApp($core);
        $handler = new ErrorHandler($app);
        $response = $handler->handle(new ServerRequest('GET', '/'));

        $this->assertStringContainsString('500 error', (string)$response->getBody());
        $this->assertStringContainsString('Looks like we\'re having some server issues', (string)$response->getBody());
        $this->assertStringContainsString('<pre class="debug">', (string)$response->getBody());
    }

    public function testHandle_fallback()
    {
        $core = new Core(new TestDirectories(), cache: false);
        $handler = new ErrorHandler(new HttpApp($core));
        $response = $handler->handle(new ServerRequest('GET', '/'));

        $this->assertStringContainsString('Internal Server Error', (string)$response->getBody());
        $this->assertStringNotContainsString('<pre>', (string)$response->getBody());
    }

    public function testHandle_fallbackDebugEnabled()
    {
        $core = new Core(new TestDirectories(), cache: false);
        $core->getDebug()->setEnabled(true);
        $handler = new ErrorHandler(new HttpApp($core));
        $response = $handler->handle(new ServerRequest('GET', '/'));

        $this->assertStringContainsString('Internal Server Error', (string)$response->getBody());
        $this->assertStringContainsString('<pre>', (string)$response->getBody());
    }
}
