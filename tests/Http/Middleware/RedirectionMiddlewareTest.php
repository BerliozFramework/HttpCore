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

use Berlioz\Config\Adapter\ArrayAdapter;
use Berlioz\Config\Adapter\JsonAdapter;
use Berlioz\Config\Config;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Core\Http\Middleware\RedirectionMiddleware;
use Berlioz\Http\Core\Tests\Http\FakeRequestHandler;
use Berlioz\Http\Message\ServerRequest;
use PHPUnit\Framework\TestCase;

class RedirectionMiddlewareTest extends TestCase
{
    public function testProcess_withoutRedirection()
    {
        $middleware = new RedirectionMiddleware(new Config([new ArrayAdapter([])]));
        $middleware->process(new ServerRequest('GET', '/'), $handler = new FakeRequestHandler());

        $this->assertTrue($handler->isHandled());
    }

    public function redirectionsProvider(): array
    {
        return [
            ['/old', '/new', 301],
            ['/old/foo', null, 301],
            ['/old-route/foo', '/new-route/foo', 301],
            ['/old-route/foo/bar', '/new-route/foo/bar', 301],
            ['/old-route/', '/new-route/', 301],
            ['/another-old-route/', '/new/', 301],
            ['/another-old-route/foo/bar', '/new/foo/bar', 301],
            ['/foo', '/bar', 302],
        ];
    }

    /**
     * @dataProvider redirectionsProvider
     */
    public function testProcess($src, $dst, $status)
    {
        $middleware = new RedirectionMiddleware(
            new Config([new JsonAdapter(__DIR__ . '/../../../tests_env/config/redirections.json', true)])
        );

        if (null === $dst) {
            $this->expectException(NotFoundHttpException::class);
        }

        $response = $middleware->process(
            new ServerRequest('GET', $src),
            $handler = new FakeRequestHandler(fn() => throw new NotFoundHttpException())
        );

        if (null === $dst) {
            return;
        }

        $locationHeader = $response->getHeader('Location');

        $this->assertCount(1, $locationHeader);
        $this->assertEquals($dst, reset($locationHeader));
        $this->assertEquals($status, $response->getStatusCode());
    }
}
