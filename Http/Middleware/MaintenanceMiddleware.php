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

namespace Berlioz\Http\Core\Http\Middleware;

use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Http\Handler\MaintenanceHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class MaintenanceMiddleware.
 */
class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected HttpApp $app
    ) {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (null === ($this->app->getMaintenance())) {
            return $handler->handle($request);
        }

        $maintenanceHandler = $this->app->getMaintenance()?->getHandler() ?? MaintenanceHandler::class;
        $maintenanceHandler = $this->app->call($maintenanceHandler, ['app' => $this->app]);

        return $maintenanceHandler->handle($request);
    }
}