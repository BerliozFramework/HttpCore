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

use Berlioz\Config\Adapter\ArrayAdapter;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\App\Maintenance;
use Berlioz\Http\Core\Exception\HttpAppException;
use Berlioz\Http\Core\Tests\Http\FakeRequestHandler;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

class MaintenanceTest extends TestCase
{
    public function configProviders(): array
    {
        return [
            [
                'config' => [],
                'enabled' => false,
                'start' => null,
                'end' => null,
                'message' => 'Foo bar',
                'handler' => null,
            ],
            [
                'config' => ['berlioz' => ['maintenance' => false]],
                'enabled' => false,
                'start' => null,
                'end' => null,
                'message' => 'Foo bar',
                'handler' => null,
            ],
            [
                'config' => ['berlioz' => ['maintenance' => true]],
                'enabled' => true,
                'start' => null,
                'end' => null,
                'message' => null,
                'handler' => null,
            ],
            [
                'config' => ['berlioz' => ['maintenance' => 'Foo bar']],
                'enabled' => true,
                'start' => null,
                'end' => null,
                'message' => 'Foo bar',
                'handler' => null,
            ],
            [
                'config' => ['berlioz' => ['maintenance' => null]],
                'enabled' => true,
                'start' => null,
                'end' => null,
                'message' => null,
                'handler' => null,
            ],
            [
                'config' => ['berlioz' => ['maintenance' => ['start' => '2021-05-03 00:00:00']]],
                'enabled' => true,
                'start' => new DateTimeImmutable('2021-05-03 00:00:00'),
                'end' => null,
                'message' => null,
                'handler' => null,
            ],
            [
                'config' => [
                    'berlioz' => [
                        'maintenance' => [
                            'message' => 'Foo bar',
                            'handler' => FakeRequestHandler::class
                        ]
                    ]
                ],
                'enabled' => true,
                'start' => null,
                'end' => null,
                'message' => 'Foo bar',
                'handler' => FakeRequestHandler::class,
            ],
            [
                'config' => [
                    'berlioz' => [
                        'maintenance' => [
                            'start' => '2021-05-03 00:00:00',
                            'end' => '2021-05-03 23:59:59',
                            'message' => 'Foo bar',
                            'handler' => FakeRequestHandler::class
                        ]
                    ]
                ],
                'enabled' => true,
                'start' => new DateTimeImmutable('2021-05-03 00:00:00'),
                'end' => new DateTimeImmutable('2021-05-03 23:59:59'),
                'message' => 'Foo bar',
                'handler' => FakeRequestHandler::class,
            ]
        ];
    }

    /**
     * @param array $config
     * @param bool $enabled
     * @param DateTimeImmutable|null $start
     * @param DateTimeImmutable|null $end
     * @param string|null $message
     * @param string|null $handler
     *
     * @throws ConfigException
     * @throws BerliozException
     * @dataProvider configProviders
     */
    public function testBuildFromConfig(
        array $config,
        bool $enabled,
        ?DateTimeImmutable $start,
        ?DateTimeImmutable $end,
        ?string $message,
        ?string $handler
    ) {
        $maintenance = Maintenance::buildFromConfig(new ArrayAdapter($config));

        if (false === $enabled) {
            $this->assertNull($maintenance);
            return;
        }

        $this->assertInstanceOf(Maintenance::class, $maintenance);
        $this->assertEquals($start, $maintenance->getStart());
        $this->assertEquals($end, $maintenance->getEnd());
        $this->assertEquals($message, $maintenance->getMessage());
        $this->assertEquals($handler, $maintenance->getHandler());
    }

    public function testConstructBadHandler()
    {
        $this->expectException(HttpAppException::class);
        $this->expectExceptionMessage(HttpAppException::invalidMaintenanceHandler()->getMessage());

        new Maintenance(handler: stdClass::class);
    }

    public function testConstructBadConfig()
    {
        $this->expectException(HttpAppException::class);
        $this->expectExceptionMessage('Bad maintenance configuration');

        Maintenance::buildFromConfig(new ArrayAdapter(['berlioz' => ['maintenance' => ['start' => 'VERY BAD DATE']]]));
    }
}
