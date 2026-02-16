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

namespace Berlioz\Http\Core\Exception;

use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Message\Response;
use Throwable;

/**
 * Class HttpException.
 */
class HttpException extends BerliozException
{
    /**
     * HttpException constructor.
     *
     * @param int $code
     * @param null|string $message
     * @param Throwable|null $previous
     */
    public function __construct(int $code = 500, ?string $message = null, ?Throwable $previous = null)
    {
        // Default message
        if (null === $message) {
            $message = Response::REASONS[$code] ?? 'Unknown error';
        }

        parent::__construct($message, $code, $previous);
    }
}