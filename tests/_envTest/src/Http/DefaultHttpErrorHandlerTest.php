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

namespace Berlioz\HttpCore\TestProject\Http;

use Berlioz\Core\Core;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\Exception\Http\NotFoundHttpException;
use Berlioz\HttpCore\Http\DefaultHttpErrorHandler;
use Berlioz\HttpCore\TestProject\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class DefaultHttpErrorHandlerTest extends TestCase
{
    public function testHandle()
    {
        $app = new HttpApp(new Core(new FakeDefaultDirectories(), false));

        $controller = new DefaultHttpErrorHandler($app);
        $response = $controller->handle(null, new NotFoundHttpException());

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }
}
