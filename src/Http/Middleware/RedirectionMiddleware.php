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

use Berlioz\Config\Config;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class RedirectionMiddleware.
 */
class RedirectionMiddleware implements MiddlewareInterface
{
    public function __construct(protected Config $config)
    {
    }

    /**
     * @inheritDoc
     * @throws BerliozException
     * @throws ConfigException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundHttpException $exception) {
            $redirections = $this->config->get('berlioz.http.redirections', []);

            foreach ($redirections as $origin => $redirection) {
                $matches = [];
                if (!(preg_match(sprintf('#%s#i', $origin), $request->getUri()->getPath(), $matches) >= 1)) {
                    continue;
                }

                list('type' => $type, 'url' => $url) = $this->getRedirectionFromConfig($redirection);

                // Replacement
                $url = preg_replace(sprintf('#%s#i', $origin), $url, $request->getUri()->getPath());

                return new Response(null, $type, ['Location' => $url]);
            }

            throw $exception;
        }
    }

    /**
     * Get redirection type and url from configuration.
     *
     * @param mixed $redirection
     *
     * @return array
     */
    private function getRedirectionFromConfig(mixed $redirection): array
    {
        if (is_array($redirection)) {
            return [
                'type' => intval($redirection['type'] ?? 301),
                'url' => (string)$redirection['url']
            ];
        }

        return [
            'type' => 301,
            'url' => (string)$redirection
        ];
    }
}