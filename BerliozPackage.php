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

namespace Berlioz\Http\Core;

use Berlioz\Config\Adapter\ArrayAdapter;
use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\Core\Package\PackageInterface;
use Berlioz\Http\Core\Container\RouteProvider;
use Berlioz\Http\Core\Container\ServiceProvider;
use Berlioz\Http\Core\Controller\DebugController;
use Berlioz\Http\Core\Http\Handler\Error\DefaultErrorHandler;
use Berlioz\Http\Core\Http\Middleware\DebugConsoleMiddleware;
use Berlioz\Http\Core\Http\Middleware\MaintenanceMiddleware;
use Berlioz\Http\Core\Http\Middleware\RedirectionMiddleware;
use Berlioz\ServiceContainer\Container;

/**
 * Class BerliozPackage.
 */
class BerliozPackage extends AbstractPackage implements PackageInterface
{
    /**
     * @inheritDoc
     * @throws ConfigException
     */
    public static function config(): ?ConfigInterface
    {
        return new ArrayAdapter(
            [
                'berlioz' => [
                    'directories' => [
                        'templates' => '{config:berlioz.directories.app}/resources/templates',
                    ],
                    'assets' => [
                        'manifest' => '{config:berlioz.directories.app}/public/assets/manifest.json',
                        'entrypoints' => '{config:berlioz.directories.app}/public/assets/entrypoints.json',
                    ],
                    'http' => [
                        'errors' => [
                            'default' => DefaultErrorHandler::class,
                        ],
                        'redirections' => [],
                        'middlewares' => [
                            0 => [
                                'maintenance' => MaintenanceMiddleware::class,
                            ],
                            1 => [
                                'debug_console' => DebugConsoleMiddleware::class,
                            ],
                            99 => [
                                'redirection' => RedirectionMiddleware::class,
                            ],
                        ],
                    ],
                    'router' => [],
                    'maintenance' => false,
                ],
                'controllers' => [
                    DebugController::class,
                ],
                'twig' => [
                    'paths' => [
                        'Berlioz-HttpCore' => realpath(__DIR__ . '/resources'),
                    ],
                    'globals' => [
                        'app' => '@AppProfile',
                    ],
                ],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public static function register(Container $container): void
    {
        $container->addProvider($container->call(RouteProvider::class));
        $container->addProvider(new ServiceProvider());
    }
}
