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

use Berlioz\Config\Adapter\JsonAdapter;
use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\Core\Package\PackageInterface;
use Berlioz\Http\Core\Container\RouteProvider;
use Berlioz\Http\Core\Container\ServiceProvider;
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
        return new JsonAdapter(__DIR__ . '/../resources/config.default.json', true);
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