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

namespace Berlioz\Http\Core\Attribute;

use Attribute;
use Berlioz\Router\Exception\RoutingException;
use Berlioz\Router\Route as BerliozRoute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    protected array $options = [];

    public function __construct(
        protected string $path = '',
        protected array $defaults = [],
        protected array $requirements = [],
        protected ?string $name = null,
        protected string|array|null $method = null,
        protected string|array|null $host = null,
        protected int $priority = -1,
        mixed ...$options,
    ) {
        $this->options = $options;
    }

    /**
     * Get route.
     *
     * @param mixed|null $context
     *
     * @return BerliozRoute
     * @throws RoutingException
     */
    public function getRoute(mixed $context = null): BerliozRoute
    {
        return new BerliozRoute(
            path: $this->path,
            defaults: $this->defaults,
            requirements: $this->requirements,
            name: $this->name,
            method: $this->method,
            host: $this->host,
            priority: $this->priority,
            options: $this->options,
            context: $context,
        );
    }
}