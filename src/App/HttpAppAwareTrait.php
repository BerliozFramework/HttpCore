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

namespace Berlioz\HttpCore\App;

/**
 * Describes a app-aware instance.
 */
trait HttpAppAwareTrait
{
    /** @var HttpApp Application */
    private $app;

    /**
     * Get application.
     *
     * @return HttpApp|null
     */
    public function getApp(): ?HttpApp
    {
        return $this->app;
    }

    /**
     * Set application.
     *
     * @param HttpApp $app
     *
     * @return static
     */
    public function setApp(HttpApp $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Has application?
     *
     * @return bool
     */
    public function hasApp(): bool
    {
        return null !== $this->app;
    }
}