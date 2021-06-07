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

namespace Berlioz\Http\Core\Tests\Helper;

use Berlioz\Http\Message\Response;
use Berlioz\Http\Core\Helper\ResponseHelperTrait;
use PHPUnit\Framework\TestCase;

class ResponseHelperTraitTest extends TestCase
{
    private function getHelper()
    {
        $helper = new class {
            use ResponseHelperTrait {
                response as public;
                jsonResponse as public;
                redirect as public;
            }
        };

        return $helper;
    }

    public function testResponse()
    {
        $helper = $this->getHelper();
        $response = $helper->response($body = 'CONTENT');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body, (string)$response->getBody());
    }

    public function testResponse_statusCode()
    {
        $helper = $this->getHelper();
        $response = $helper->response($body = 'CONTENT', 201);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($body, (string)$response->getBody());
    }

    public function testResponse_headers()
    {
        $helper = $this->getHelper();
        $response = $helper->response($body = 'CONTENT', 201, ['X-Berlioz' => 'Value']);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($body, (string)$response->getBody());
        $this->assertEquals(['X-Berlioz' => ['Value']], $response->getHeaders());
    }

    public function testResponse_empty()
    {
        $helper = $this->getHelper();
        $response = $helper->response();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function testJsonResponse()
    {
        $helper = $this->getHelper();
        $response = $helper->jsonResponse($body = ['CONTENT']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals(json_encode($body), (string)$response->getBody());
    }

    public function testJsonResponse_flag()
    {
        $helper = $this->getHelper();
        $response = $helper->jsonResponse($body = ['CONTENT'], JSON_FORCE_OBJECT);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals(json_encode($body, JSON_FORCE_OBJECT), (string)$response->getBody());
    }

    public function testJsonResponse_statusCode()
    {
        $helper = $this->getHelper();
        $response = $helper->jsonResponse($body = ['CONTENT'], statusCode: 201);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals(json_encode($body), (string)$response->getBody());
    }

    public function testJsonResponse_headers()
    {
        $helper = $this->getHelper();
        $response = $helper->jsonResponse($body = ['CONTENT'], statusCode: 201, headers: ['X-Berlioz' => 'Value']);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(json_encode($body), (string)$response->getBody());
        $this->assertEquals(['Content-Type' => ['application/json'], 'X-Berlioz' => ['Value']], $response->getHeaders());
    }

    public function testJsonResponse_empty()
    {
        $helper = $this->getHelper();
        $response = $helper->jsonResponse();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEmpty((string)$response->getBody());
    }

    public function testRedirect_withStatusCode()
    {
        $helper = $this->getHelper();
        $response = $helper->redirect('/foo', 301);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/foo', reset($locationHeader));
        $this->assertEmpty((string)$response->getBody());
    }

    public function testRedirect_withResponse()
    {
        $helper = $this->getHelper();
        $response = $helper->redirect('/bar', 302, new Response('Foo'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($locationHeader = $response->getHeader('Location'));
        $this->assertEquals('/bar', reset($locationHeader));
        $this->assertEquals('Foo', (string)$response->getBody());
    }
}
