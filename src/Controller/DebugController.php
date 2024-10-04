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

use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Asset\EntryPoints;
use Berlioz\Core\Asset\Manifest;
use Berlioz\Core\Debug\DebugHandler;
use Berlioz\Core\Debug\Snapshot;
use Berlioz\Core\Exception\AssetException;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Core\Attribute\Route;
use Berlioz\Http\Core\Attribute\RouteGroup;
use Berlioz\Http\Core\Debug\Section;
use Berlioz\Http\Core\Exception\Http\BadRequestHttpException;
use Berlioz\Http\Core\Exception\Http\InternalServerErrorHttpException;
use Berlioz\Http\Core\Exception\Http\NotFoundHttpException;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\Router\Exception\RoutingException;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionException;
use Throwable;
use Twig\Error\Error;

/**
 * Class DebugController.
 */
#[RouteGroup('/_console', requirements: ['id' => '\w+'], priority: 1000)]
class DebugController extends AbstractController
{
    private string $resourceDist;
    private array $snapshots = [];

    /**
     * DebugController constructor.
     *
     * @param DebugHandler $debug
     */
    public function __construct(DebugHandler $debug)
    {
        $debug->setEnabled(false);
        $this->resourceDist = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'resources', 'Public', 'dist']);
    }

    /**
     * Get debug snapshot.
     *
     * @param string $id
     *
     * @return Snapshot
     * @throws BerliozException
     */
    private function getDebugSnapshot(string $id): Snapshot
    {
        $id = basename($id, '.debug');

        if (array_key_exists($id, $this->snapshots)) {
            return $this->snapshots[$id];
        }

        try {
            $fs = $this->getApp()->getCore()->getFilesystem();
            $filename = sprintf('debug://%s.debug', $id);

            $nbRetries = 0;
            while (false === $fs->fileExists($filename)) {
                if ($nbRetries > 4) {
                    throw new BerliozException(sprintf('Debug snapshot "%s" does not exists', $id));
                }

                $nbRetries++;
                usleep(250000);
            }

            if (empty($this->snapshots[$id] = unserialize(gzinflate($fs->read($filename))))) {
                throw new BerliozException(sprintf('Debug snapshot "%s" corrupted', $id));
            }
        } catch (BerliozException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BerliozException(sprintf('Error during get debug snapshot "%s"', $id), 0, $e);
        }

        return $this->snapshots[$id];
    }

    /**
     * @inheritDoc
     */
    public function render(string $name, array $variables = []): string
    {
        $variables['entrypoints'] = new EntryPoints($this->resourceDist . DIRECTORY_SEPARATOR . 'entrypoints.json');

        return parent::render($name, $variables);
    }

    //////////////////
    /// DIST FILES ///
    //////////////////

    /**
     * Dist files.
     *
     * @param ServerRequest $request
     *
     * @return ResponseInterface
     * @throws NotFoundHttpException
     */
    #[Route('/dist/{type}/{file}',
        requirements: [
            "type" => "js|css|fonts",
            "file" => "[\\w\\-]+\\.\\w{8}\\.\\w+"
        ],
        name: '_berlioz/console/dist')]
    public function distFiles(
        ServerRequest $request
    ): ResponseInterface {
        $fileName =
            $this->resourceDist . '/' .
            $request->getAttribute('type') . '/' .
            basename($request->getAttribute('file'));

        if (!file_exists($fileName)) {
            throw new NotFoundHttpException('Asset not found');
        }

        $extension = substr($fileName, strrpos($fileName, '.') + 1);
        $stream = new Stream\FileStream($fileName, 'r');

        // Headers
        $headers = [
            'Content-Type' => match ($request->getAttribute('type')) {
                'js' => 'application/javascript',
                'css' => 'text/css',
                'fonts' => match ($extension) {
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                }
            },
            'Content-Length' => $stream->getSize(),
            'Cache-Control' => 'private, max-age=604800, immutable',
        ];

        return $this->response($stream, Response::HTTP_STATUS_OK, $headers);
    }

    ///////////////
    /// Toolbar ///
    ///////////////

    /**
     * Dist caller.
     *
     * @return ResponseInterface
     * @throws AssetException
     * @throws InternalServerErrorHttpException
     */
    #[Route('/dist/toolbar.js', name: '_berlioz/console/toolbar-dist')]
    public function distCaller(): ResponseInterface
    {
        $manifest = new Manifest($this->resourceDist . DIRECTORY_SEPARATOR . 'manifest.json');
        $fileName = $this->resourceDist . substr($manifest->get('debug-caller.js'), strlen('/_console/dist'));

        if (!file_exists($fileName)) {
            throw new InternalServerErrorHttpException('Toolbar caller not found');
        }

        $body = new Stream\FileStream($fileName, 'r');

        return $this->response(
            $body,
            Response::HTTP_STATUS_OK,
            [
                'Content-Type' => ['application/javascript'],
                'Content-Length' => [$body->getSize()],
            ]
        );
    }

    /**
     * Toolbar.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/toolbar', name: '_berlioz/console/toolbar')]
    public function toolbar(
        ServerRequestInterface $request
    ) {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/_toolbar.html.twig',
                [
                    'snapshot' => $snapshot,
                    'rtl' => ($_COOKIE['berlioz_toolbar_direction'] ?? 'ltr') === 'rtl',
                ]
            )
        );
    }

    ///////////////
    /// Console ///
    ///////////////

    /**
     * PHP info.
     *
     * @return ResponseInterface
     */
    #[Route('/_phpinfo', name: '_berlioz/phpinfo')]
    public function phpInfoRaw(): ResponseInterface
    {
        ob_start();
        phpinfo();
        $phpInfo = ob_get_contents();
        ob_end_clean();

        return $this->response($phpInfo);
    }

    /**
     * Console.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}', name: '_berlioz/console/home')]
    public function dashboard(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/dashboard.html.twig',
                ['snapshot' => $snapshot]
            )
        );
    }

    /**
     * System.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/environment', name: '_berlioz/console/environment')]
    public function environment(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/environment.html.twig',
                ['snapshot' => $snapshot]
            )
        );
    }

    /**
     * Performances.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/performances', name: '_berlioz/console/performances')]
    public function performances(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/performances.html.twig',
                ['snapshot' => $snapshot]
            )
        );
    }

    /**
     * PHP info.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/phpinfo', name: '_berlioz/console/phpinfo')]
    public function phpInfo(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/phpinfo.html.twig',
                ['snapshot' => $snapshot]
            )
        );
    }

    /**
     * Activities.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/activities', name: '_berlioz/console/activities')]
    public function activities(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/activities.html.twig',
                ['snapshot' => $snapshot]
            )
        );
    }

    /**
     * Activity.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/activities/{activity}', requirements: ['activity' => '\w+'], name: '_berlioz/console/activity')]
    public function activity(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        if (null === ($activity = $snapshot->getTimeline()->getActivity($request->getAttribute('activity')))) {
            throw new NotFoundHttpException('Detail of activity not found');
        }

        $activityDetail = $activity->getDetail();
        $activityResult = $activity->getResult();

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/activity.html.twig',
                [
                    'snapshot' => $snapshot,
                    'activity' => $activity,
                    'aDetail' => is_scalar($activityDetail) ? $activityDetail : var_export($activityDetail, true),
                    'aResult' => is_scalar($activityResult) ? $activityResult : var_export($activityResult, true),
                ]
            )
        );
    }

    /**
     * Events.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/events', name: '_berlioz/console/events')]
    public function events(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/events.html.twig',
                [
                    'snapshot' => $snapshot,
                ]
            )
        );
    }

    /**
     * Exception.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/exception', name: '_berlioz/console/exception')]
    public function exception(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/exception.html.twig',
                [
                    'snapshot' => $snapshot,
                ]
            )
        );
    }

    /**
     * PHP errors.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/php-errors', name: '_berlioz/console/php-errors')]
    public function phpErrors(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/php-errors.html.twig',
                ['snapshot' => $snapshot]
            )
        );
    }

    /**
     * Php error.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/php-errors/{error}', requirements: ['error' => '\d+'], name: '_berlioz/console/php-error')]
    public function phpError(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));
        $phpErrors = iterator_to_array($snapshot->getPhpErrors()->getErrors());

        if (!($phpError = $phpErrors[$request->getAttribute('error')] ?? null)) {
            throw new NotFoundHttpException('Detail of PHP error not found');
        }

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/php-error.html.twig',
                [
                    'snapshot' => $snapshot,
                    'error' => $phpError,
                ]
            )
        );
    }

    /**
     * Used classes.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     * @throws ReflectionException
     */
    #[Route('/{id}/used-classes', name: '_berlioz/console/used-classes')]
    public function usedClasses(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        $nbClasses = ['berlioz' => 0, 'composer' => 0, 'user' => 0];
        $classes = array_map(
            function ($className) use (&$nbClasses) {
                if (class_exists($className) && call_user_func([new ReflectionClass($className), 'isInternal'])) {
                    return false;
                }

                if (str_starts_with($className, 'Berlioz\\')) {
                    $type = 'berlioz';
                    $nbClasses['berlioz']++;
                } else {
                    if (str_starts_with($className, 'Composer\\')) {
                        $type = 'composer';
                        $nbClasses['composer']++;
                    } else {
                        $type = 'user';
                        $nbClasses['user']++;
                    }
                }

                return [
                    'name' => $className,
                    'type' => $type,
                ];
            },
            $snapshot->getProjectInfo()->getDeclaredClasses()
        );
        $classes = array_filter($classes);

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/used-classes.html.twig',
                [
                    'snapshot' => $snapshot,
                    'classes' => $classes,
                    'nbClasses' => $nbClasses,
                ]
            )
        );
    }

    /**
     * Cache.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     * @throws RoutingException
     */
    #[Route('/{id}/cache', name: '_berlioz/console/cache')]
    public function cache(
        ServerRequest $request
    ): ResponseInterface {
        $requestQueryParams = $request->getQueryParams();
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));

        // Clear
        if ($clear = $request->getQueryParam('clear')) {
            switch ($clear) {
                case 'internal':
                    $this->getApp()->getCore()->getCache()->clear();
                    break;
                case 'opcache':
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                    }
                    break;
                case 'directory':
                    if (null === ($directory = $request->getQueryParam('directory'))) {
                        throw new BadRequestHttpException();
                    }

                    $clear .= ':' . basename($directory);
                    $this->getApp()->getCore()->getFilesystem()->deleteDirectory('cache://' . basename($directory));
                    break;
            }

            return $this->redirect(
                $this->getRouter()->generate(
                    '_berlioz/console/cache',
                    [
                        'id' => $snapshot->getUniqid(),
                        'cleared' => $clear,
                    ]
                )
            );
        }

        // OPcache
        $opcache = false;
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status(true);
        }

        $directories =
            $this->getApp()->getCore()->getFilesystem()
                ->listContents('cache://')
                ->filter(fn(StorageAttributes $attr) => $attr->isDir())
                ->map(fn(DirectoryAttributes $attr) => basename($attr->path()))
                ->toArray();

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/cache.html.twig',
                [
                    'snapshot' => $snapshot,
                    'cacheManager' => $this->getApp()->getCore()->getCache(),
                    'opcache' => $opcache,
                    'cacheDirectories' => $directories,
                    'cleared' => ($requestQueryParams['cleared'] ?? null),
                ]
            )
        );
    }

    /**
     * Configuration.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws ConfigException
     * @throws Error
     */
    #[Route('/{id}/config', name: '_berlioz/console/config')]
    public function configuration(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));
        $configuration = json_encode($snapshot->getConfig()->getArrayCopy(), JSON_PRETTY_PRINT);

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/config.html.twig',
                [
                    'snapshot' => $snapshot,
                    'configuration' => $configuration,
                ]
            )
        );
    }

    /**
     * Other sections.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws BerliozException
     * @throws Error
     */
    #[Route('/{id}/{section}', requirements: ['section' => '[\w\-_]+'], name: '_berlioz/console/section')]
    public function section(
        ServerRequestInterface $request
    ): ResponseInterface {
        $snapshot = $this->getDebugSnapshot($request->getAttribute('id'));
        $snapshotSection = $snapshot->getSection($request->getAttribute('section'));

        if ($snapshotSection instanceof Section || method_exists($snapshotSection, 'getTemplateName')) {
            return $this->response(
                $this->render(
                    $snapshotSection->getTemplateName(),
                    [
                        'snapshot' => $snapshot,
                        'section' => $snapshotSection,
                    ]
                )
            );
        }

        return $this->response(
            $this->render(
                '@Berlioz-HttpCore/Twig/Debug/section.html.twig',
                [
                    'snapshot' => $snapshot,
                    'section' => $snapshotSection,
                ]
            )
        );
    }
}