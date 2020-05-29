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

namespace Berlioz\HttpCore\Http;

use Berlioz\HttpCore\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface HttpErrorHandler.
 *
 * @package Berlioz\HttpCore\Http
 */
interface HttpErrorHandler
{
    /**
     * Handle HTTP error.
     *
     * @param ServerRequestInterface|null $request
     * @param HttpException $e
     *
     * @return ResponseInterface
     */
    public function handle(?ServerRequestInterface $request, HttpException $e): ResponseInterface;
}