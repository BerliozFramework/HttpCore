<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2026 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Http\Core\Debug;

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Helpers\NetworkHelper;
use Berlioz\Http\Core\Controller\DebugController;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class DebugConsoleAccess.
 */
class DebugConsoleAccess
{
    public function __construct(private readonly ConfigInterface $config)
    {
    }

    /**
     * Does a resolved controller target belong to the debug console?
     */
    public static function isDebugController(mixed $context): bool
    {
        if (is_array($context)) {
            $context = $context[0] ?? null;
        } elseif (is_string($context)) {
            $context = explode('::', $context, 2)[0];
        }

        return (is_string($context) || is_object($context)) && is_a($context, DebugController::class, true);
    }

    /**
     * Require configured debug access for the current request.
     *
     * @throws ConfigException
     * @throws NotFoundHttpException
     */
    public function assertAllowed(ServerRequestInterface $request): void
    {
        if (!$this->isAllowed($request)) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @throws ConfigException
     */
    private function isAllowed(ServerRequestInterface $request): bool
    {
        // DebugController disables collection; authorization must use configuration instead.
        if (true !== $this->config->get('berlioz.debug.enable', false)) {
            return false;
        }

        $allowedIps = $this->config->get('berlioz.debug.ip', []);
        if (!is_array($allowedIps)) {
            return false;
        }

        if ([] === $allowedIps) {
            return true;
        }

        $trustedProxies = $this->config->get('berlioz.proxies.trusted', []);
        if (!is_array($trustedProxies)) {
            $trustedProxies = [];
        }

        $clientIp = NetworkHelper::clientIp($trustedProxies, $request->getServerParams());
        if (null === $clientIp) {
            return false;
        }

        if (in_array($clientIp, $allowedIps, true)) {
            return true;
        }

        return in_array(gethostbyaddr($clientIp), $allowedIps, true);
    }
}
