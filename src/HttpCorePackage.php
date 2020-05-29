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

declare(strict_types=1);

namespace Berlioz\HttpCore;

use Berlioz\Config\Exception\ConfigException;
use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Core;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\Core\Package\PackageInterface;
use Berlioz\FlashBag\FlashBag;
use Berlioz\HttpCore\App\AppProfile;
use Berlioz\PhpDoc\PhpDocFactory;
use Berlioz\Router\Route;
use Berlioz\Router\RouteGenerator;
use Berlioz\Router\Router;
use Berlioz\Router\RouterInterface;
use Berlioz\ServiceContainer\Exception\ContainerException;
use Berlioz\ServiceContainer\Service;
use Exception;
use Psr\SimpleCache\CacheException;

/**
 * Class HttpCorePackage.
 *
 * @package Berlioz\HttpCore
 */
class HttpCorePackage extends AbstractPackage implements PackageInterface
{
    ///////////////
    /// PACKAGE ///
    ///////////////

    /**
     * @inheritdoc
     * @throws ConfigException
     */
    public static function config()
    {
        return new ExtendedJsonConfig(
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources', 'config.default.json']),
            true
        );
    }

    /**
     * @inheritdoc
     * @throws ContainerException
     */
    public static function register(Core $core): void
    {
        // Create AppProfile service
        $appProfileService = new Service(AppProfile::class, 'AppProfile');
        self::addService($core, $appProfileService);

        // Create phpDoc service
        $phpDocService = new Service(PhpDocFactory::class, 'phpDocFactory');
        self::addService($core, $phpDocService);

        // Create router service
        $routerService = new Service(RouterInterface::class, 'router');
        $routerService->setFactory(HttpCorePackage::class . '::routerFactory');
        self::addService($core, $routerService);

        // Create FlashBag service
        $flashBagService = new Service(FlashBag::class, 'flashbag');
        self::addService($core, $flashBagService);
    }

    /////////////////
    /// FACTORIES ///
    /////////////////

    /**
     * Router factory.
     *
     * @param Core $core
     *
     * @return Router
     * @throws BerliozException
     */
    public static function routerFactory(Core $core): Router
    {
        // Create router
        $routerActivity = (new Debug\Activity('Router (initialization)', 'Berlioz'))->start();

        try {
            $cacheManager = $core->getCacheManager();

            // Get from cache
            if ($router = $cacheManager->get('berlioz-router')) {
                return $router;
            }

            $router = new Router();
            $routeGenerator = new RouteGenerator();

            if (!empty($controllers = $core->getConfig()->get('controllers', []))) {
                $controllers = array_unique($controllers);

                foreach ($controllers as $controller) {
                    $routeSet = $routeGenerator->fromClass($controller);
                    $router->getRouteSet()->merge($routeSet);
                }
            }

            if (!empty($routes = $core->getConfig()->get('routes', []))) {
                foreach ($routes as $routeName => $routeCfg) {
                    $route = new Route(
                        $routeCfg['route'],
                        array_merge(['name' => $routeName], $routeCfg['options'] ?? []),
                        $routeCfg['context'] ?? []
                    );
                    $router->getRouteSet()->addRoute($route);
                }
            }

            // Save to cache
            if ($core->isCacheEnabled()) {
                $core->onTerminate(
                    function (Core $core) use ($router) {
                        $core->getCacheManager()->set('berlioz-router', $router);
                    }
                );
            }

            return $router;
        } catch (CacheException $e) {
            throw new BerliozException('Router initialization error', 0, $e);
        } catch (Exception $e) {
            throw new BerliozException('Router initialization error', 0, $e);
        } finally {
            $core->getDebug()->getTimeLine()->addActivity($routerActivity->end());
        }
    }
}