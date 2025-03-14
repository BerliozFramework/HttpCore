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

namespace Berlioz\Http\Core\Helper;

use Berlioz\Http\Core\App\HttpAppAwareTrait;
use LogicException;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait ReloadHelperTrait.
 */
trait ReloadHelperTrait
{
    use HttpAppAwareTrait;
    use ResponseHelperTrait;

    /**
     * Reload page.
     *
     * If response is given in parameter, it will be completed with good headers.
     *
     * @param array $queryParams Http GET parameters
     * @param bool $mergeQueryParams Merge parameters with current server request
     * @param ResponseInterface|null $response Response
     *
     * @return ResponseInterface
     * @deprecated Uses `ResponseHelperTrait::redirect()` to do redirection.
     */
    protected function reload(
        array $queryParams = [],
        bool $mergeQueryParams = false,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        if (null === ($serverRequest = $this->getApp()->getRequest())) {
            throw new LogicException('You can not reload without server request');
        }

        // Query
        if ($mergeQueryParams) {
            $queryParams = array_merge($serverRequest->getQueryParams(), $queryParams);
        }

        // URI
        $uri = $serverRequest->getUri();
        $uri = $uri->withQuery(http_build_query($queryParams));

        return $this->redirect($uri, 302, $response);
    }
}