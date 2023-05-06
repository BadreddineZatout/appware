<?php
/**
 * AppWare - a micro PHP framework
 *
 * @author      Ramzi HABIB <info@appwareframework.com>
 * @copyright   2016 Ramzi HABIB
 * @link        http://www.appwareframework.com
 * @license     http://www.appwareframework.com/license
 * @version     2.0.0
 * @package     AppWare
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * AppWare
 * @package AppWare
 * @author  Ramzi HABIB
 * @since   2.0.0
 */

namespace AppWare\Core\ErrorHandlers;

use \AppWare\Core\App as AppWare;
use \AppWare\Core\Exceptions\ControllerNotFound as AppWareControllerNotFoundException;
use \AppWare\Core\Exceptions\MethodNotFound as AppWareMethodNotFoundException;
use \Exception;
use \ErrorException;
use \Whoops;

/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

//namespace Whoops\Handler;
//use Whoops\Handler\Handler;
//use Whoops\Exception\Formatter;

/**
 * Catches an exception and converts it to a JSON
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class DefaultHandler extends Whoops\Handler\Handler
{
    /**
     * @var bool
     */
    private $returnFrames = false;

    /**
     * @param  bool|null $returnFrames
     * @return bool|$this
     */
    public function addTraceToOutput($returnFrames = null)
    {
        if(func_num_args() == 0) {
            return $this->returnFrames;
        }

        $this->returnFrames = (bool) $returnFrames;
        return $this;
    }

    /**
     * @return int
     */
    public function handle()
    {
        /*if($this->onlyForAjaxRequests() && !$this->isAjaxRequest()) {
            return Whoops\Handler\Handler::DONE;
        }

        $response = array(
            'error' => Whoops\Exception\Formatter::formatExceptionAsDataArray(
                $this->getInspector(),
                $this->addTraceToOutput()
            ),
        );

        if (\Whoops\Util\Misc::canSendHeaders()) {
            header('Content-Type: application/json');
        }

        echo json_encode($response);*/
        
        
        //throw new AppWareUnhandledException($this->getInspector()->getException());
        //throw $this->getInspector()->getException();
        
        $controller = static::app()->controller();
        $deliveryType = $controller ? $controller->deliveryType() : false;
        $deliveryMethod = $controller ? $controller->deliveryMethod() : false;
        $deliveryMasterView = $controller ? $controller->masterView() : false;

        $inspector = $this->getInspector();
        
        if ($inspector->hasPreviousException()) {
            $e = $inspector->getPreviousExceptionInspector()->getException();
        } else {
            $e = $inspector->getException();
        }

        $statusCode = $e && preg_match('#^5[0-9]{2}$#', $e->getCode()) ? $e->getCode() : 503;

        $this->getRun()->sendHttpCode($statusCode);

        if (static::app()->response()?:false) {
            static::app()->response()->code($statusCode);
            static::app()->throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
            static::app()->response()->send();
        } else {
            /*$view = static::app()->view();
            $outputBufferLevel = $view ? $view->outputBufferLevel() - 1 : ob_get_level() - 1;
            while (ob_get_level() > $outputBufferLevel && ob_end_clean()) {
                // do nothing
            }*/

            static::throwErrorPage($e, $statusCode);
        }
        
        return Whoops\Handler\Handler::QUIT;
        //return Whoops\Handler\Handler::DONE;
    }

    /**
     * Throw a last chance pretty error page.
     * Verbose errors are on by default unless configured otherwise.
     *
     * @param Exception
     * @param integer
     * @return AppWare
     */
    protected static function throwErrorPage($e = null, $statusCode = 503)
    {
        $e = $e?:new Exception('Unhandled exception', $statusCode?:503);

        $app = clone static::app();

        $app->router()->respond('*', function () use ($app, $statusCode, $e) {
            $app->router()->response()->code($statusCode);

            $verbose = true;
            $rootPath = '';
            $privatePath = '';
            $vendorPath = '';

            try {
                $verbose = $app::config()->get('AppWare.ErrorHandler.DefaultHandlerVerbose', $verbose);

                if ($verbose) {
                    $rootPath = $app::rootFolder() ?: (defined('APPWARE_ROOT_PATH')?APPWARE_ROOT_PATH:'');
                    $privatePath = $app::privateFolder() ?: (defined('APPWARE_PRIVATE_PATH')?APPWARE_PRIVATE_PATH:'');
                    $vendorPath = realpath(rtrim($privatePath, DIRECTORY_SEPARATOR) . '/../vendor');
                }
            } catch (Exception $err) {}

            ob_start();
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>Ouch! 50X error</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootswatch/3.3.6/darkly/bootstrap.min.css">
            </head>
            <body>
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="page-header">
                            <h1>Ouch! <span>50X error</span></h1>
                        </div>
                        <?php if ($verbose) { ?>
                            <div class="bs-component">
                                <div class="jumbotron">
                                    <pre class="text-left alert alert-danger"><?php echo get_class($e); ?> : <?php echo $e->getMessage(); ?></pre>
                                    <?php if($e instanceof ErrorException) { ?>
                                        <pre class="text-left alert alert-danger"><?php echo sprintf('#0 %s(%s): %s', str_replace(array($rootPath, $privatePath, $vendorPath), array('', '', '/vendor'), $e->getFile()), $e->getLine(), $e->getMessage()); ?></pre>
                                    <?php } else {?>
                                        <pre class="text-left alert alert-danger"><?php echo str_replace(array($rootPath, $privatePath, $vendorPath), array('', '', '/vendor'), $e->getTraceAsString()); ?></pre>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            </body>
            </html>
            <?php
            $app->router()->response()->body(ob_get_clean());
            $app->router()->response()->send();
        });

        $app->router()->dispatch();
    }

    /**
     * Get back the app instance
     *
     * @param string
     * @return AppWare
     */
    public static function app() {
        return AppWare::getInstance();
    }
}
