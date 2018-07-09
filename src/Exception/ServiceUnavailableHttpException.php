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

namespace Berlioz\HttpCore\Exception;

class ServiceUnavailableHttpException extends HttpException
{
    /**
     * ServiceUnavailableHttpException constructor.
     *
     * @param null|string     $message
     * @param null|\Throwable $previous
     */
    public function __construct(?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(503, $message, $previous);
    }
}