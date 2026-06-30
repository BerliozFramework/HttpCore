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

namespace Berlioz\Http\Core\Controller;

use Berlioz\FlashBag\FlashBag;
use Berlioz\Http\Core\App\HttpAppAwareInterface;
use Berlioz\Http\Core\App\HttpAppAwareTrait;
use Berlioz\Http\Core\Helper\ReloadHelperTrait;
use Berlioz\Http\Core\Helper\ResponseHelperTrait;
use Berlioz\Http\Core\Helper\RouterHelperTrait;
use Berlioz\Package\Twig\TwigAwareInterface;
use Berlioz\Package\Twig\TwigAwareTrait;
use RuntimeException;

/**
 * Class AbstractController.
 */
abstract class AbstractController implements HttpAppAwareInterface, TwigAwareInterface
{
    use HttpAppAwareTrait;
    use ReloadHelperTrait;
    use ResponseHelperTrait;
    use RouterHelperTrait;
    use TwigAwareTrait;

    /**
     * PHP serialize method.
     *
     * @throws RuntimeException because unable to serialize a Controller object
     */
    final public function __serialize(): array
    {
        throw new RuntimeException('Unable to serialize a Controller object');
    }

    /**
     * Get service.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function get(string $id): mixed
    {
        return $this->getApp()->get($id);
    }

    /**
     * Add new message in flash bag.
     *
     * @param string $type Type of message
     * @param string $message Message
     *
     * @return static
     * @see FlashBag FlashBag class whose manage all flash messages
     */
    protected function addFlash(string $type, string $message): static
    {
        /** @var FlashBag $flashBag */
        $flashBag = $this->get(FlashBag::class);
        $flashBag->add($type, $message);

        return $this;
    }
}
