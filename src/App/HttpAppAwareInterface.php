<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\HttpCore\App;

interface HttpAppAwareInterface
{
    /**
     * Get application.
     *
     * @return \Berlioz\HttpCore\App\HttpApp|null
     */
    public function getApp(): ?HttpApp;

    /**
     * Set application.
     *
     * @param \Berlioz\HttpCore\App\HttpApp $app
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