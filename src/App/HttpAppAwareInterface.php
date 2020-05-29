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
 * Interface HttpAppAwareInterface.
 *
 * @package Berlioz\HttpCore\App
 */
interface HttpAppAwareInterface
{
    /**
     * Get application.
     *
     * @return HttpApp|null
     */
    public function getApp(): ?HttpApp;

    /**
     * Set application.
     *
     * @param HttpApp $app
     *
     * @return static
     */
    public function setApp(HttpApp $app);

    /**
     * Has application ?
     *
     * @return bool
     */
    public function hasApp(): bool;
}