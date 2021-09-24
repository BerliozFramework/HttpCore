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

namespace Berlioz\Http\Core\Http\Handler\Error;

use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\Controller\AbstractController;
use Berlioz\Http\Core\Exception\Http\InternalServerErrorHttpException;
use Berlioz\Http\Core\Exception\HttpException;
use Berlioz\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Twig\Error\Error;

/**
 * Class DefaultErrorHandler.
 */
class DefaultErrorHandler extends AbstractController implements ErrorHandlerInterface
{
    /**
     * Get template name.
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return '@Berlioz-HttpCore/Twig/Http/error.html.twig';
    }

    /**
     * @inheritDoc
     * @throws BerliozException
     * @throws Error
     */
    public function handle(ServerRequestInterface $request, ?Throwable $throwable = null): ResponseInterface
    {
        $httpException = $throwable instanceof HttpException ? $throwable : new InternalServerErrorHttpException();

        $str = $this->render(
            $this->getTemplateName(),
            [
                'request' => $request,
                'exception' => $throwable,
                'httpException' => $httpException,
            ]
        );

        return new Response(
            body:         $str,
            statusCode:   $httpException->getCode(),
            reasonPhrase: $httpException->getMessage()
        );
    }
}