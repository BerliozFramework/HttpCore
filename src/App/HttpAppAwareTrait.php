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

namespace Berlioz\Http\Core\App;

/**
 * Describes a app-aware instance.
 */
trait HttpAppAwareTrait
{
    private HttpApp $app;

    /**
     * Get application.
     *
     * @return HttpApp|null
     */
    public function getApp(): ?HttpApp
    {
        return $this->app ?? null;
    }

    /**
     * Set application.
     *
     * @param HttpApp $app
     */
    public function setApp(HttpApp $app): void
    {
        $this->app = $app;
    }

    /**
     * Has application?
     *
     * @return bool
     */
    public function hasApp(): bool
    {
        return null !== ($this->app ?? null);
    }
}