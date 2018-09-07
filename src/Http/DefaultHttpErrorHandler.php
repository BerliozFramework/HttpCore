<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HttpCore\Http;

use Berlioz\Http\Message\Response;
use Berlioz\HttpCore\Controller\AbstractController;
use Berlioz\HttpCore\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DefaultHttpErrorHandler extends AbstractController implements HttpErrorHandler
{
    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function handle(?ServerRequestInterface $request, HttpException $e): ResponseInterface
    {
        $str = $this->render('@Berlioz-HttpCore/Twig/Http/error.html.twig',
                             ['request'   => $request,
                              'exception' => $e]);

        return new Response($str, $e->getCode(), [], $e->getMessage());
    }
}