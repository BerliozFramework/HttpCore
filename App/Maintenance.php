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

namespace Berlioz\Http\Core\App;

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\Exception\HttpAppException;
use DateTimeImmutable;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class Maintenance.
 */
class Maintenance
{
    /**
     * Build from config.
     *
     * @param ConfigInterface $config
     *
     * @return Maintenance|null
     * @throws ConfigException
     * @throws BerliozException
     */
    public static function buildFromConfig(ConfigInterface $config): ?Maintenance
    {
        $maintenance = $config->get('berlioz.maintenance', false);

        if (is_bool($maintenance)) {
            if (true === $maintenance) {
                return new static();
            }

            return null;
        }

        if (is_string($maintenance)) {
            return new static(message: $maintenance);
        }

        if (!is_array($maintenance)) {
            return new static();
        }

        try {
            return new static(
                start: !empty($maintenance['start']) ? new DateTimeImmutable($maintenance['start']) : null,
                end: !empty($maintenance['end']) ? new DateTimeImmutable($maintenance['end']) : null,
                message: $maintenance['message'] ?? null,
                handler: $maintenance['handler'] ?? null,
            );
        } catch (Throwable $exception) {
            throw new HttpAppException('Bad maintenance configuration', previous: $exception);
        }
    }

    /**
     * Maintenance constructor.
     *
     * @param DateTimeImmutable|null $start
     * @param DateTimeImmutable|null $end
     * @param string|null $message
     * @param string|null $handler
     *
     * @throws BerliozException
     */
    public function __construct(
        protected ?DateTimeImmutable $start = null,
        protected ?DateTimeImmutable $end = null,
        protected ?string $message = null,
        protected ?string $handler = null,
    ) {
        if (null !== $this->handler) {
            if (!is_a($this->handler, RequestHandlerInterface::class, true)) {
                throw HttpAppException::invalidMaintenanceHandler();
            }
        }
    }

    /**
     * Get start.
     *
     * @return DateTimeImmutable|null
     */
    public function getStart(): ?DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * Get end.
     *
     * @return DateTimeImmutable|null
     */
    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get handler.
     *
     * @return string|null
     */
    public function getHandler(): ?string
    {
        return $this->handler;
    }
}