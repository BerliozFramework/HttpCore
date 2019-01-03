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

namespace Berlioz\HttpCore\Controller;

use Berlioz\Core\Debug;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Http\Message\Response;
use Berlioz\HttpCore\App\HttpApp;
use Berlioz\HttpCore\Debug\Section;
use Berlioz\HttpCore\Exception\Http\NotFoundHttpException;
use Berlioz\Package\Twig\Controller\RenderingControllerInterface;
use Berlioz\Package\Twig\Controller\RenderingControllerTrait;
use Psr\Http\Message\ServerRequestInterface;

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
    /** @var \Berlioz\Core\Debug[] $debug */
    private $debug;

    /**
     * DebugController constructor.
     *
     * @param \Berlioz\HttpCore\App\HttpApp $app
     *
     * @throws \Berlioz\Core\Exception\BerliozException
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
     * @return \Berlioz\Core\Debug
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    private function getDebugReport(string $id): Debug
    {
        if (is_null($this->debug)) {
            try {
                if (!empty($debugDirectory = $this->getApp()->getCore()->getConfig()->get('berlioz.directories.debug')) &&
                    file_exists($debugFile = $debugDirectory . DIRECTORY_SEPARATOR . basename($id) . '.debug')) {

                    if (empty($this->debug[$id] = unserialize(gzinflate(file_get_contents($debugFile))))) {
                        throw new BerliozException(sprintf('Debug report "%s" corrupted', $id));
                    }
                } else {
                    throw new BerliozException(sprintf('Debug report "%s" does not exists', $id));
                }
            } catch (BerliozException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new BerliozException(sprintf('Error during get debug report "%s"', $id), 0, $e);
            }
        }

        return $this->debug[$id];
    }

    ///////////////
    /// Toolbar ///
    ///////////////

    /**
     * Toolbar dist.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\HttpCore\Exception\Http\NotFoundHttpException
     * @route("/_console/dist/toolbar.{type}", name="_berlioz/console/toolbar-dist", priority=1001,
     *                                         requirements={"type": "js|css|caller\.js"}, defaults={"type":
     *                                         "caller.js"})
     */
    public function toolbarDist(ServerRequestInterface $request)
    {
        switch ($request->getAttribute('type')) {
            case 'css':
                return new Response(file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->resourceDist, 'css', 'debug-toolbar.css'])),
                                    200,
                                    ['Content-Type' => 'text/css']);
            case 'css.map':
                return new Response(file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->resourceDist, 'css.map', 'debug-toolbar.css.map'])),
                                    200,
                                    ['Content-Type' => 'application/json']);
            case 'js':
                return new Response(file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->resourceDist, 'js', 'debug-toolbar.js'])),
                                    200,
                                    ['Content-Type' => 'text/javascript']);
            case 'caller.js':
                return new Response(file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->resourceDist, 'js', 'debug-caller.js'])),
                                    200,
                                    ['Content-Type' => 'text/javascript']);
            default:
                throw new NotFoundHttpException;
        }
    }

    /**
     * Toolbar.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/toolbar", name="_berlioz/console/toolbar", requirements={"id":"\w+"})
     */
    public function toolbar(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));

        return $this->render('@Berlioz-HttpCore/Twig/Debug/_toolbar.html.twig',
                             ['report' => $report]);
    }

    ///////////////
    /// Console ///
    ///////////////

    /**
     * Get resource CSS.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Server request
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Berlioz\HttpCore\Exception\Http\NotFoundHttpException
     * @route("/_console/dist/debug.{type}", name="_berlioz/console/console-dist", priority=1001, requirements={"type":
     *                                         "js|css|css.map"})
     */
    public function consoleDist(ServerRequestInterface $request)
    {
        switch ($request->getAttribute('type')) {
            case 'css':
                return new Response(file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->resourceDist, 'css', 'debug.css'])),
                                    200,
                                    ['Content-Type' => 'text/css']);
            case 'css.map':
                return new Response(file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->resourceDist, 'css', 'debug.css.map'])),
                                    200,
                                    ['Content-Type' => 'application/json']);
            case 'js':
                return new Response(file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->resourceDist, 'js', 'debug.js'])),
                                    200,
                                    ['Content-Type' => 'text/javascript']);
            default:
                throw new NotFoundHttpException;
        }
    }

    /**
     * PHP info.
     *
     * @return \Psr\Http\Message\ResponseInterface|string
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}", name="_berlioz/console/home", requirements={"id":"\w+"})
     */
    public function dashboard(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $system = $report->getSystemInfo();
        $php = $report->getPhpInfo();

        return $this->render('@Berlioz-HttpCore/Twig/Debug/dashboard.html.twig',
                             ['report' => $report,
                              'system' => $system,
                              'php'    => $php]);
    }

    /**
     * Performances.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/performances", name="_berlioz/console/performances", requirements={"id":"\w+"})
     */
    public function performances(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $timeLine = $report->getTimeLine();
        $performances = $report->getPerformancesInfo();

        return $this->render('@Berlioz-HttpCore/Twig/Debug/performances.html.twig',
                             ['report'       => $report,
                              'timeLine'     => $timeLine,
                              'performances' => $performances]);
    }

    /**
     * PHP info.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/phpinfo", name="_berlioz/console/phpinfo", requirements={"id":"\w+"})
     */
    public function phpInfo(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));

        return $this->render('@Berlioz-HttpCore/Twig/Debug/phpinfo.html.twig',
                             ['report' => $report]);
    }

    /**
     * Activities.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/activities", name="_berlioz/console/activities", requirements={"id":"\w+"})
     */
    public function activities(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $timeLine = $report->getTimeLine();

        return $this->render('@Berlioz-HttpCore/Twig/Debug/activities.html.twig',
                             ['report'   => $report,
                              'timeLine' => $timeLine]);
    }

    /**
     * Activity.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
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

            return $this->render('@Berlioz-HttpCore/Twig/Debug/activity.html.twig',
                                 ['report'         => $report,
                                  'timeLine'       => $timeLine,
                                  'activity'       => $activity,
                                  'activityDetail' => is_scalar($activityDetail) ? $activityDetail : var_export($activityDetail, true),
                                  'activityResult' => is_scalar($activityResult) ? $activityResult : var_export($activityResult, true)]);
        } else {
            throw new NotFoundHttpException('Detail of activity not found');
        }
    }

    /**
     * PHP errors.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/php-errors", name="_berlioz/console/php-errors", requirements={"id":"\w+"})
     */
    public function phpErrors(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $phpErrors = $report->getPhpError()->getPhpErrors();

        return $this->render('@Berlioz-HttpCore/Twig/Debug/php-errors.html.twig',
                             ['report'    => $report,
                              'phpErrors' => $phpErrors]);
    }

    /**
     * Php error.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/php-errors/{error}", name="_berlioz/console/php-error", requirements={"id":"\w+",
     *                                             "error": "\d+"})
     */
    public function phpError(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $phpErrors = $report->getPhpError()->getPhpErrors();

        if ($phpError = $phpErrors[$request->getAttribute('error')] ?? null) {
            return $this->render('@Berlioz-HttpCore/Twig/Debug/php-error.html.twig',
                                 ['report' => $report,
                                  'error'  => $phpError]);
        } else {
            throw new NotFoundHttpException('Detail of PHP error not found');
        }
    }

    /**
     * Used classes.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/used-classes", name="_berlioz/console/used-classes", requirements={"id":"\w+"})
     */
    public function usedClasses(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $projectInfo = $report->getProjectInfo();

        $nbClasses = ['berlioz' => 0, 'composer' => 0, 'user' => 0];
        $projectInfo['declared_classes'] = array_map(
            function ($className) use (&$nbClasses) {
                if (class_exists($className) && call_user_func([new \ReflectionClass($className), 'isInternal'])) {
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

                return ['name' => $className,
                        'type' => $type];
            },
            $projectInfo['declared_classes']);
        $projectInfo['declared_classes'] = array_filter($projectInfo['declared_classes']);

        return $this->render('@Berlioz-HttpCore/Twig/Debug/used-classes.html.twig',
                             ['report'    => $report,
                              'classes'   => $projectInfo['declared_classes'],
                              'nbClasses' => $nbClasses]);
    }

    /**
     * Configuration.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/config", name="_berlioz/console/config", requirements={"id":"\w+"})
     */
    public function configuration(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $configuration = json_encode($report->getConfig(), JSON_PRETTY_PRINT);

        return $this->render('@Berlioz-HttpCore/Twig/Debug/config.html.twig',
                             ['report'        => $report,
                              'configuration' => $configuration]);
    }

    /**
     * Other sections.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Twig_Error
     * @route("/_console/{id}/{section}", name="_berlioz/console/section", requirements={"id":"\w+", "section":
     *                                    "[\w\-_]+"})
     */
    public function section(ServerRequestInterface $request)
    {
        $report = $this->getDebugReport($request->getAttribute('id'));
        $reportSection = $report->getSection($request->getAttribute('section'));

        if ($reportSection instanceof Section || method_exists($reportSection, 'getTemplateName')) {
            return $this->render($reportSection->getTemplateName(),
                                 ['report'  => $report,
                                  'section' => $reportSection]);
        } else {
            return $this->render('@Berlioz-HttpCore/Twig/Debug/section.html.twig',
                                 ['report'  => $report,
                                  'section' => $reportSection]);
        }
    }
}