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

namespace Berlioz\Http\Core\Container;

use Berlioz\Config\Config;
use Berlioz\Core\Core;
use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Router\RouterBuilder;
use Berlioz\Router\Route;
use Berlioz\Router\RouteInterface;
use Berlioz\Router\Router;
use Berlioz\Router\RouterInterface;
use Berlioz\ServiceContainer\Container;
use Berlioz\ServiceContainer\Provider\AbstractServiceProvider;
use Berlioz\ServiceContainer\Service\CacheStrategy;
use Berlioz\ServiceContainer\Service\Service;

class RouteProvider extends AbstractServiceProvider
{
    protected array $provides = [
        Router::class,
        RouterInterface::class,
        Route::class,
        RouteInterface::class,
        'router',
        'route',
    ];

    public function __construct(private Core $core)
    {
    }

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->addService(
            $service = new Service(
                class: Router::class,
                alias: 'router',
                factory: function (Config $config): Router {
                    $builder = new RouterBuilder($config);
                    $builder->addRoutesFromControllers();
                    $builder->addRoutesFromConfig();

                    return $builder->getRouter();
                },
                cacheStrategy: new CacheStrategy($this->core->getCache())
            )
        );
        $service->addProvide(RouterInterface::class);

        $container->addService(
            $service = new Service(
                class: Route::class,
                alias: 'route',
                factory: fn(HttpApp $app) => $app->getRoute()
            )
        );
        $service->setNullable(true);
        $service->addProvide(RouteInterface::class);
    }
}