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

use Berlioz\Http\Core\App\HttpApp;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class ControllerHandler.
 */
class ControllerHandler implements RequestHandlerInterface
{
    public function __construct(protected HttpApp $app)
    {
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // No route, so no controller to handle!
        if (null === ($route = $this->app->getRoute())) {
            throw new NotFoundHttpException();
        }

        $activity = $this->app->getDebug()->newActivity('Controller');
        $activity
            ->start()
            ->setDescription('It\'s the execution of application controller')
            ->setDetail($route->getContext());

        try {
            $result = $this->app->call($route->getContext(), ['request' => $request]);

            if ($result instanceof ResponseInterface) {
                return $result;
            }

            if (null === $result || (is_string($result) && empty($result))) {
                return new Response(null, Response::HTTP_STATUS_NO_CONTENT);
            }

            if (is_scalar($result)) {
                return new Response($result);
            }

            return new Response(json_encode($result), headers: ['Content-Type' => 'application/json']);
        } finally {
            $activity->end();
        }
    }
}