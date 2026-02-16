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

namespace Berlioz\Http\Core\Http\Handler;

use Berlioz\Http\Core\Controller\AbstractController;
use Berlioz\Http\Core\Exception\Http\ServiceUnavailableHttpException;
use Berlioz\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class MaintenanceHandler extends AbstractController implements RequestHandlerInterface
{
    /**
     * @inheritDoc
     * @throws ServiceUnavailableHttpException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $str = $this->render(
                '@Berlioz-HttpCore/Twig/Http/maintenance.html.twig',
                [
                    'request' => $request,
                    'maintenance' => $this->getApp()->getMaintenance()
                ]
            );

            return new Response(body: $str, statusCode: 503);
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(previous: $exception);
        }
    }
}