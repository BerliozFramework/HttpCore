<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HttpCore\Controller;

use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Asset\EntryPoints;
use Berlioz\Core\Asset\Manifest;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\AssetException;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\ServerRequest;
use Berlioz\Http\Message\Stream;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\Debug\Section;
use Berlioz\HttpCore\Exception\Http\InternalServerErrorHttpException;
use Berlioz\HttpCore\Exception\Http\NotFoundHttpException;
use Berlioz\Package\Twig\Controller\RenderingControllerInterface;
use Berlioz\Package\Twig\Controller\RenderingControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Throwable;
use Twig\Error\Error;

/**
 * Class DebugController.
 *
 * @package Berlioz\HttpCore\Controller
 * @route(priority=1000)
 */
class DebugController extends AbstractController implements RenderingControllerInterface
{
    use RenderingControllerTrait;

    /** @var string Resource string */
    private $resourceDist;
    /** @var Debug[] $debug */
    private $debug = [];

    /**
     * DebugController constructor.
     *
     * @param HttpApp $app
     *
     * @throws BerliozException
     */
    public function __construct(HttpApp $app)
    {
        parent::__construct($app);

        $this->getApp()->getCore()->getDebug()->setEnabled(false);
        $this->resourceDist = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'resources', 'Public', 'dist']);
    }

    /**
     * Get debug report.
     *
     * @param string $id
     *
     * @return Debug
     * @throws BerliozException
     */
    private function getDebugReport(string $id): Debug
    {
        $id = basename($id, '.debug');

        if (array_key_exists($id, $this->debug)) {
            return $this->debug[$id];
        }

        try {
            $debugDirectory = $this->getApp()->getCore()->getConfig()->get('berlioz.directories.debug');
            if (empty($debugDirectory)) {
                throw new BerliozException('Debug directory does not defined');
            }

            // Prevent write timing of harddrives (retry 4 times)
            $nbRetries = 0;
            $debugFile = $debugDirectory . DIRECTORY_SEPARATOR . $id . '.debug';
            while (!file_exists($debugFile)) {
                if ($nbRetries > 4) {
                    throw new BerliozException(sprintf('Debug report "%s" does not exists', $id));
                }

                $nbRetries++;
                usleep(250000);
                clearstatcache(true, $debugFile);
            }

            if (empty($this->debug[$id] = unserialize(gzinflate(file_get_contents($debugFile))))) {
                throw new BerliozException(sprintf('Debug report "%s" corrupted', $id));
            }
        } catch (BerliozException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BerliozException(sprintf('Error during get debug report "%s"', $id), 0, $e);
        }

        return $this->debug[$id];
    }

    /**
     * @inheritdoc
     */
    public function render(string $name, array $variables = []): string
    {
        $variables = array_merge(
            [
                'entrypoints' => new EntryPoints($this->resourceDist . DIRECTORY_SEPARATOR . 'entrypoints.json'),
            ],
            $variables
        );

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
     * @route('/_console/dist/{type}/{file}',
     *        requirements={"type": "js|css", "file": "[\\w\\-]+\\.\\w{8}\\.\\w{2,3}(\\.map)?"})
     */
    public function distFiles(ServerRequest $request): ResponseInterface
    {
        $fileName = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->resourceDist,
                $request->getAttribute('type'),
                basename($request->getAttribute('file')),
            ]
        );

        if (!file_exists($fileName)) {
            throw new NotFoundHttpException('Asset not found');
        }

        $body = new Stream();
        $body->write(file_get_contents($fileName));
        $response = new Response($body);

        // Content-Type
        switch ($request->getAttribute('type')) {
            case 'js':
                $response = $response->withHeader('Content-Type', 'application/javascript');
                break;
            case 'css':
                $response = $response->withHeader('Content-Type', 'text/css');
                break;
        }

        // Map file?
        if (substr($request->getAttribute('file'), -4) == '.map') {
            $response = $response->withHeader('Content-Type', 'application/javascript');
        }

        // Content-Length
        $response = $response->withHeader('Content-Length', $body->getSize());

        return $response;
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
     * @route('/_console/dist/toolbar.js', name='_berlioz/console/toolbar-dist')
     */
    public function distCaller(): ResponseInterface
    {
        $manifest = new Manifest($this->resourceDist . DIRECTORY_SEPARATOR . 'manifest.json');
        $fileName = $this->resourceDist . substr($manifest->get('/debug-caller.js'), strlen('/_console/dist'));

        if (!file_exists($fileName)) {
            throw new InternalServerErrorHttpException('Toolbar caller not found');
        }

        $body = new Stream();
        $body->write(file_get_contents($fileName));

        return new Response(
            $body,
            200,
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
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/toolbar", name="_berlioz/console/toolbar", requirements={"id":"\w+"})
     */
    public function toolbar(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/_toolbar.html.twig',
            [
                'report' => $report,
                'rtl' => ($_COOKIE['berlioz_toolbar_direction'] ?? 'ltr') === 'rtl',
            ]
        );
    }

    ///////////////
    /// Console ///
    ///////////////

    /**
     * PHP info.
     *
     * @return ResponseInterface|string
     * @route("/_phpinfo", name="_berlioz/phpinfo")
     */
    public function phpInfoRaw()
    {
        ob_start();
        phpinfo();
        $phpInfo = ob_get_contents();
        ob_end_clean();

        return $phpInfo;
    }

    /**
     * Console.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}", name="_berlioz/console/home", requirements={"id":"\w+"})
     */
    public function dashboard(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/dashboard.html.twig',
            [
                'report' => $report,
            ]
        );
    }

    /**
     * System.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/environment", name="_berlioz/console/environment", requirements={"id":"\w+"})
     */
    public function environment(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $system = $report->getSystemInfo();
        $php = $report->getPhpInfo();

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/environment.html.twig',
            [
                'report' => $report,
                'system' => $system,
                'php' => $php,
            ]
        );
    }

    /**
     * Performances.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/performances", name="_berlioz/console/performances", requirements={"id":"\w+"})
     */
    public function performances(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $timeLine = $report->getTimeLine();
        $performances = $report->getPerformancesInfo();

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/performances.html.twig',
            [
                'report' => $report,
                'timeLine' => $timeLine,
                'performances' => $performances,
            ]
        );
    }

    /**
     * PHP info.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/phpinfo", name="_berlioz/console/phpinfo", requirements={"id":"\w+"})
     */
    public function phpInfo(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/phpinfo.html.twig',
            ['report' => $report]
        );
    }

    /**
     * Activities.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/activities", name="_berlioz/console/activities", requirements={"id":"\w+"})
     */
    public function activities(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $timeLine = $report->getTimeLine();

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/activities.html.twig',
            [
                'report' => $report,
                'timeLine' => $timeLine,
            ]
        );
    }

    /**
     * Activity.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/activities/{activity}", name="_berlioz/console/activity", requirements={"id":"\w+",
     *                                                "activity": "\d+"})
     */
    public function activity(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $timeLine = $report->getTimeLine();

        if ($activity = $timeLine->getActivities()[$request->getAttribute('activity')] ?? null) {
            $activityDetail = $activity->getDetail();
            $activityResult = $activity->getResult();

            return $this->render(
                '@Berlioz-HttpCore/Twig/Debug/activity.html.twig',
                [
                    'report' => $report,
                    'timeLine' => $timeLine,
                    'activity' => $activity,
                    'activityDetail' => is_scalar($activityDetail) ? $activityDetail : var_export(
                        $activityDetail,
                        true
                    ),
                    'activityResult' => is_scalar($activityResult) ? $activityResult : var_export(
                        $activityResult,
                        true
                    ),
                ]
            );
        } else {
            throw new NotFoundHttpException('Detail of activity not found');
        }
    }

    /**
     * Exception.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/exception", name="_berlioz/console/exception", requirements={"id":"\w+"})
     */
    public function exception(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $exception = $report->getExceptionThrown();

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/exception.html.twig',
            [
                'report' => $report,
                'exception' => $exception,
            ]
        );
    }

    /**
     * PHP errors.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/php-errors", name="_berlioz/console/php-errors", requirements={"id":"\w+"})
     */
    public function phpErrors(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $phpErrors = $report->getPhpError()->getPhpErrors();

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/php-errors.html.twig',
            [
                'report' => $report,
                'phpErrors' => $phpErrors,
            ]
        );
    }

    /**
     * Php error.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/php-errors/{error}", name="_berlioz/console/php-error", requirements={"id":"\w+",
     *                                             "error": "\d+"})
     */
    public function phpError(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $phpErrors = $report->getPhpError()->getPhpErrors();

        if ($phpError = $phpErrors[$request->getAttribute('error')] ?? null) {
            return $this->render(
                '@Berlioz-HttpCore/Twig/Debug/php-error.html.twig',
                [
                    'report' => $report,
                    'error' => $phpError,
                ]
            );
        } else {
            throw new NotFoundHttpException('Detail of PHP error not found');
        }
    }

    /**
     * Used classes.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/used-classes", name="_berlioz/console/used-classes", requirements={"id":"\w+"})
     */
    public function usedClasses(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $projectInfo = $report->getProjectInfo();

        $nbClasses = ['berlioz' => 0, 'composer' => 0, 'user' => 0];
        $projectInfo['declared_classes'] = array_map(
            function ($className) use (&$nbClasses) {
                if (class_exists($className) && call_user_func([new ReflectionClass($className), 'isInternal'])) {
                    return false;
                } else {
                    if (substr($className, 0, 8) == 'Berlioz\\') {
                        $type = 'berlioz';
                        $nbClasses['berlioz']++;
                    } else {
                        if (substr($className, 0, 8) == 'Composer') {
                            $type = 'composer';
                            $nbClasses['composer']++;
                        } else {
                            $type = 'user';
                            $nbClasses['user']++;
                        }
                    }
                }

                return [
                    'name' => $className,
                    'type' => $type,
                ];
            },
            $projectInfo['declared_classes']
        );
        $projectInfo['declared_classes'] = array_filter($projectInfo['declared_classes']);

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/used-classes.html.twig',
            [
                'report' => $report,
                'classes' => $projectInfo['declared_classes'],
                'nbClasses' => $nbClasses,
            ]
        );
    }

    /**
     * Cache.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/cache", name="_berlioz/console/cache", requirements={"id":"\w+"})
     */
    public function cache(ServerRequestInterface $request)
    {
        $requestQueryParams = $request->getQueryParams();
        $report = $this->getDebugReport($request->getAttribute('id'));

        // Clear
        if ($clear = ($requestQueryParams['clear'] ?? null)) {
            switch ($clear) {
                case 'internal':
                    $this->getCore()->getCacheManager()->clear();
                    break;
                case 'opcache':
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                    }
                    break;
            }

            return $this->redirect(
                $this->getRouter()->generate(
                    '_berlioz/console/cache',
                    [
                        'id' => $report->getUniqid(),
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

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/cache.html.twig',
            [
                'report' => $report,
                'cacheManager' => $this->getCore()->getCacheManager(),
                'opcache' => $opcache,
                'cleared' => ($requestQueryParams['cleared'] ?? null),
            ]
        );
    }

    /**
     * Configuration.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws ConfigException
     * @throws Error
     * @route("/_console/{id}/config", name="_berlioz/console/config", requirements={"id":"\w+"})
     */
    public function configuration(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $configuration = json_encode($report->getConfig()->get(), JSON_PRETTY_PRINT);

        return $this->render(
            '@Berlioz-HttpCore/Twig/Debug/config.html.twig',
            [
                'report' => $report,
                'configuration' => $configuration,
            ]
        );
    }

    /**
     * Other sections.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|string
     * @throws BerliozException
     * @throws Error
     * @route("/_console/{id}/{section}", name="_berlioz/console/section", requirements={"id":"\w+", "section":
     *                                    "[\w\-_]+"})
     */
    public function section(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $reportSection = $report->getSection($request->getAttribute('section'));

        if ($reportSection instanceof Section || method_exists($reportSection, 'getTemplateName')) {
            return $this->render(
                $reportSection->getTemplateName(),
                [
                    'report' => $report,
                    'section' => $reportSection,
                ]
            );
        } else {
            return $this->render(
                '@Berlioz-HttpCore/Twig/Debug/section.html.twig',
                [
                    'report' => $report,
                    'section' => $reportSection,
                ]
            );
        }
    }
}