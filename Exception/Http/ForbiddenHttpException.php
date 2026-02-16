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

namespace Berlioz\Http\Core\Exception\Http;

use Berlioz\Http\Core\Exception\HttpException;
use Throwable;

/**
 * Class ForbiddenHttpException.
 */
class ForbiddenHttpException extends HttpException
{
    /**
     * ForbiddenHttpException constructor.
     *
     * @param null|string $message
     * @param null|Throwable $previous
     */
    public function __construct(?string $message = null, ?Throwable $previous = null)
    {
        parent::__construct(403, $message, $previous);
    }
}