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

use Berlioz\FlashBag\FlashBag;
use Berlioz\Http\Core\App\AppProfile;
use Berlioz\ServiceContainer\Container;
use Berlioz\ServiceContainer\Provider\AbstractServiceProvider;
use Berlioz\ServiceContainer\Service\Service;

/**
 * Class ServiceProvider.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        FlashBag::class,
        AppProfile::class,
        'flashbag',
        'AppProfile',
    ];

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->addService(new Service(FlashBag::class, 'flashbag'));
        $container->addService(new Service(AppProfile::class, 'AppProfile'));
    }
}