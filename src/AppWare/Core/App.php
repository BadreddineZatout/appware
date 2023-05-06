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

namespace AppWare\Core;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use \League\Event\Emitter;
use \Illuminate\Support\Facades\Facade;
use \AppWare\Core\Exceptions\ComponentNotFound as AppWareComponentNotFoundException;
use \AppWare\Core\Exceptions\ControllerNotFound as AppWareControllerNotFoundException;
use \AppWare\Core\Exceptions\MethodNotFound as AppWareMethodNotFoundException;
use \AppWare\Core\Exceptions\ModelNotFound as AppWareModelNotFoundException;
use \AppWare\Core\Exceptions\ViewNotFound as AppWareViewNotFoundException;
use \AppWare\Core\Exceptions\ModuleNotFound as AppWareModuleNotFoundException;
use \AppWare\Core\Exceptions\ClassNotFound as AppWareClassNotFoundException;
use \AppWare\Core\Exceptions\Unhandled as AppWareUnhandledException;
use \AppWare\Core\Config as AppWareConfig;
use \AppWare\Core\Views\Base as AppWareView;
use \AppWare\Core\Auth as AppWareAuth;
use \AppWare\Core\Mailer as AppWareMailer;
use \AppWare\Core\Form\Validation as AppWareValidation;
use \AppWare\Core\Logger as AppWareLogger;
use \AppWare\Core\ErrorHandlers\Base as AppWareErrorHandler;
use \AppWare\Core\Form\Form as AppWareForm;
use \AppWare\Core\Database\Manager as AppWareDatabaseManager;
use \AppWare\Core\Cache\Manager as AppWareCacheManager;
use \AppWare\Core\Controllers\Base as AppWareController;
use \Exception;
use \Klein\Klein;
use \Klein\Request;
use \Klein\Response;
use \Klein\ServiceProvider;
use \FireLogger;

/**
 * AppWare
 * @package AppWare
 * @author  Ramzi HABIB
 * @since   2.0.0
 */
class App
{

    /**
     * The app singleton
     *
     * @var App
     * @access protected
     * @static
     */
    protected static $_instance = null;

    static public $_rootPath;

    static public $_privatePath;

    static public $_tmpPath;

    static public $_webRoot;

    static public $_webPath;

    static public $_libPath;

    static public $_publicPath;

    static public $_mediasPath;

    static public $_mediasRootPath;

    //static public $_appPath;

    static public $_configPath;

    static public $_viewsPath;

    static protected $_cachePath;

    static protected $_query;

    static public $db;

    static public $auth;

    static public $mailer;

    static public $validation;

    static public $router;

    static public $request;

    static public $response;

    static public $service;

    static public $view;

    static public $controller;

    static public $config;

    static public $fireLogger;

    static public $logger;

    static public $errorHandler;

    static public $formHandler;

    static public $dbManager;

    static public $cacheManager;

    static public $defaultTimeZone = 'Europe/Paris';
    static public $defaultContentType = 'text/html';
    static public $defaultCharset = 'utf-8';

    static public $controllersNameSpace = '';
    static public $controllersClassSuffix = '';
    static public $controllerLowersCaseMethodName = true;

    static public $modulesNameSpace = '';
    static public $modulesClassSuffix = '';

    static public $defaultControllerName = 'Home';
    static public $defaultControllerClass = "Home";
    static public $defaultMethod = "index";

    static public $errorControllerName = 'Error';
    static public $errorControllerClass = "Error";
    static public $errorNotFoundMethod = "notFound";
    static public $errorErrorMethod = "error";

    static public $defaultControllerFound = false;
    static public $defaultMethodFound = false;

    static public $errorControllerFound = false;
    static public $errorNotFoundMethodFound = false;
    static public $errorErrorMethodFound = false;

    /**
     * The App constructor
     *
     * @param string
     * @return App
     */
    public function __construct($rootPath = null) {

        static::$_rootPath = $rootPath?:(static::$_rootPath?:(defined('APPWARE_ROOT_PATH')?APPWARE_ROOT_PATH:''));

        static::$_instance = $this;

        static::timeZone(static::config()->get('AppWare.TimeZone', static::$defaultTimeZone));
        static::contentType(static::config()->get('AppWare.ContentType', static::$defaultContentType));
        static::charset(static::config()->get('AppWare.Charset', static::$defaultCharset));

        static::errorHandler()->register();
    }

    /**
     * Get back the Singleton instance
     *
     * @param string
     * @return App
     */
    public static function getInstance($rootPath = null) {
        if(is_null(static::$_instance)) {
            static::$_instance = new static($rootPath);
        }
        return static::$_instance;
    }

    /**
     * App facade accessor
     *
     * @param string
     * @param array
     * @return mixed
     */
    public function __call($name, $arguments) {
        return call_user_func_array(array( static::getInstance(), $name ), $arguments);
    }

    /**
     * Dynamically get a static property.
     *
     * @param  string  $key
     * @return bool
     */
    public function __get($key)
    {
        return static::$$key;
    }

    /**
     * Dynamically check if a static property is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset(static::$$key);
    }

    public function start() {

        static::controllersNameSpace(static::config()->get('AppWare.ControllersNameSpace', static::$controllersNameSpace));
        static::controllersClassSuffix(static::config()->get('AppWare.ControllersClassSuffix', static::$controllersClassSuffix));
        static::controllersLowerCaseMethodName(static::config()->get('AppWare.ControllersLowerCaseMethodName', static::$controllerLowersCaseMethodName));

        static::$defaultControllerName = static::config()->get('AppWare.DefaultController', static::$defaultControllerName);
        static::$defaultControllerClass = sprintf('%s\\%s%s', static::controllersNameSpace(), static::$defaultControllerName, static::controllersClassSuffix());
        static::$defaultMethod = static::config()->get('AppWare.DefaultMethod', static::$defaultMethod);

        static::$errorControllerName = static::config()->get('AppWare.ErrorController', static::$errorControllerName);
        static::$errorControllerClass = sprintf('%s\\%s%s', static::controllersNameSpace(), static::$errorControllerName, static::controllersClassSuffix());
        static::$errorNotFoundMethod = static::config()->get('AppWare.NotFoundMethod', static::$errorNotFoundMethod);
        static::$errorErrorMethod = static::config()->get('AppWare.ErrorMethod', static::$errorErrorMethod);

        static::modulesNameSpace(static::config()->get('AppWare.ModulesNameSpace', static::$modulesNameSpace));
        static::modulesClassSuffix(static::config()->get('AppWare.ModulesClassSuffix', static::$modulesClassSuffix));

        static::databaseManager()->setAsGlobal();
        Facade::setFacadeApplication(static::databaseManager()->getApplication());
        AppWareCacheManager::register(static::databaseManager()->getApplication());

        static::router()->respond('*', function () {

            static::response()->header('Content-Type', implode('; ', array(static::contentType(), implode('=', array('charset', static::charset())))));

            $query = static::requestQuery();

            $deliveryType = static::request()->param('DeliveryType', false);
            $deliveryMethod = static::request()->param('DeliveryMethod', false);
            $deliveryMasterView = static::request()->param('DeliveryMasterView', false);

            $queryParts = explode('/', $query);

            $strictSanityCheck = static::config()->get('AppWare.StrictSanityCheck', false);

            if ($queryParts === false || count($queryParts) == 0 || (count($queryParts) == 1 && empty($queryParts[0]))) {

                $className = static::$defaultControllerClass;
                $queryMethod = false;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$defaultMethod) : static::$defaultMethod;
                $controllerName = strtolower(static::$defaultControllerName);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();

            } elseif(count($queryParts) == 1 || (count($queryParts) == 2 && empty($queryParts[1]))) {

                $className = sprintf('%s\\%s%s', static::$controllersNameSpace, ucwords($queryParts[0]), static::$controllersClassSuffix);
                $queryMethod = false;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$defaultMethod) : static::$defaultMethod;
                $controllerName = strtolower($queryParts[0]);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();

            } elseif(count($queryParts) >= 2) {

                $queryController = array_shift($queryParts);
                //$queryController = array_shift($queryParts);
                $className = sprintf('%s\\%s%s', static::$controllersNameSpace, ucwords($queryController), static::$controllersClassSuffix);
                $queryMethod = array_shift($queryParts);
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(ucwords($queryMethod)) : ucwords($queryMethod);
                $controllerName = strtolower($queryController);
                $controllerMethod = strtolower($methodName);
                $methodArgs = $queryParts;//successive calls to array_shift removed for both the controller and the method

            } else {//Unknown error ...

                $className = static::$errorControllerClass;
                $queryMethod = false;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$errorErrorMethod) : static::$errorErrorMethod;
                $controllerName = strtolower(static::$errorControllerName);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();//some 50x debug thingy

            }

            $queryParts = $queryParts?:array();

            try {
                if (!class_exists($className))
                    throw new AppWareControllerNotFoundException("Controller not found");

                if ((lcfirst($methodName) != lcfirst(static::$defaultMethod)) && (!method_exists($className, $methodName))) {
                    $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$defaultMethod) : static::$defaultMethod;
                    $controllerMethod = strtolower($methodName);
                    if ($queryMethod) {
                        array_unshift($queryParts, $queryMethod);
                        $methodArgs = $queryParts;
                    }
                }

                if (!method_exists($className, $methodName))
                    throw new AppWareMethodNotFoundException("Method not found");
            } catch (AppWareControllerNotFoundException $e) {
                $className = static::$errorControllerClass;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$errorNotFoundMethod) : static::$errorNotFoundMethod;
                $controllerName = strtolower(static::$errorControllerName);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();//some 404 debug thingy
            } catch (AppWareMethodNotFoundException $e) {
                $className = static::$errorControllerClass;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$errorNotFoundMethod) : static::$errorNotFoundMethod;
                $controllerName = strtolower(static::$errorControllerName);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();//some 404 debug thingy
            }

            if ($className == static::$errorControllerClass) {
                try {
                    if (!class_exists($className))
                        throw new AppWareControllerNotFoundException(sprintf("%s not found", $className));

                    if (lcfirst($methodName) == lcfirst(static::$errorNotFoundMethod)) {
                        if (!method_exists($className, $methodName))
                            throw new AppWareMethodNotFoundException(sprintf("%s %s Method not found", $className, $methodName));
                    }
                } catch (AppWareControllerNotFoundException $e) {
                    throw new AppWareComponentNotFoundException(sprintf("%s not found", $className));
                } catch (AppWareMethodNotFoundException $e) {
                    $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$errorErrorMethod) : static::$errorErrorMethod;
                    $controllerMethod = strtolower(static::$errorErrorMethod);
                }
            }

            if (lcfirst($methodName) == lcfirst(static::$errorErrorMethod)) {
                if (!method_exists($className, $methodName))
                    throw new AppWareComponentNotFoundException(sprintf("%s %s Method not found", $className, $methodName));
            }

            //Sanity check. Could be avoided if Controller and Method are OK but anyhow, good to have them ready ...

            try {
                if (class_exists(static::$errorControllerClass))
                    static::$errorControllerFound = true;

                if (static::$errorControllerFound) {
                    if (method_exists(static::$errorControllerClass, static::$errorNotFoundMethod))
                        static::$errorNotFoundMethodFound = true;
                    if (method_exists(static::$errorControllerClass, static::$errorErrorMethod))
                        static::$errorErrorMethodFound = true;
                }
            } catch (AppWareControllerNotFoundException $e) {
                if ($strictSanityCheck) throw new AppWareComponentNotFoundException(sprintf("%s Controller not found", static::$errorControllerClass));
            } catch (AppWareMethodNotFoundException $e) {
                //Enable if strict sanity check
                if (!static::$errorErrorMethodFound)
                    if ($strictSanityCheck) throw new AppWareComponentNotFoundException(sprintf("%s %s Method not found", static::$errorControllerClass, static::$errorErrorMethod));
                if (!static::$errorNotFoundMethodFound)
                    if ($strictSanityCheck) throw new AppWareComponentNotFoundException(sprintf("%s %s Method not found", static::$errorControllerClass, static::$errorNotFoundMethod));
            }

            try {

                try {

                    $controller = new $className();

                    if ($deliveryType) call_user_func_array(array($controller, 'deliveryType'), array($deliveryType));
                    else $deliveryType = call_user_func_array(array($controller, 'deliveryType'), array());

                    if ($deliveryMethod) call_user_func_array(array($controller, 'deliveryMethod'), array($deliveryMethod));
                    else $deliveryMethod = call_user_func_array(array($controller, 'deliveryMethod'), array());

                    if ($deliveryMasterView) call_user_func_array(array($controller, 'masterView'), array($deliveryMasterView));
                    else $deliveryMasterView = call_user_func_array(array($controller, 'masterView'), array());

                    $controller->methodName = $methodName;
                    $controller->controllerMethod = $controllerMethod;

                    static::controller($controller);

                    call_user_func_array(array(static::controller(), $methodName), $methodArgs);

                } catch (AppWareControllerNotFoundException $e) {
                    static::throwNotFoundPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                } catch (AppWareMethodNotFoundException $e) {
                    static::throwNotFoundPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                } catch (AppWareModelNotFoundException $e) {
                    static::throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                } catch (AppWareViewNotFoundException $e) {
                    static::throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                } catch (AppWareModuleNotFoundException $e) {
                    static::throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                } catch (AppWareClassNotFoundException $e) {
                    static::throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                } catch (AppWareUnhandledException $e) {
                    static::throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                } catch (Exception $e) {
                    static::throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
                }

            } catch (AppWareComponentNotFoundException $e) {
                echo $e->getMessage();
            } catch (AppWareUnhandledException $e) {
                echo $e->getMessage();
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            static::response()->send();

        });

        return;
    }

    static public function throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView) {
        $view = static::view();
        $outputBufferLevel = $view ? $view->outputBufferLevel() : ob_get_level() - 1;
        while (ob_get_level() > $outputBufferLevel && ob_end_clean()) {
            // do nothing
        }

        $strictSanityCheck = static::config()->get('AppWare.StrictSanityCheck', false);

        try {
            if (class_exists(static::$errorControllerClass))
                static::$errorControllerFound = true;

            if (static::$errorControllerFound) {
                if (method_exists(static::$errorControllerClass, static::$errorNotFoundMethod))
                    static::$errorNotFoundMethodFound = true;
                if (method_exists(static::$errorControllerClass, static::$errorErrorMethod))
                    static::$errorErrorMethodFound = true;
            }
        } catch (AppWareControllerNotFoundException $e) {
            if ($strictSanityCheck) throw new AppWareComponentNotFoundException(sprintf("%s Controller not found", static::$errorControllerClass));
        } catch (AppWareMethodNotFoundException $e) {
            //Enable if strict sanity check
            if (!static::$errorErrorMethodFound)
                if ($strictSanityCheck) throw new AppWareComponentNotFoundException(sprintf("%s %s Method not found", static::$errorControllerClass, static::$errorErrorMethod));
            if (!static::$errorNotFoundMethodFound)
                if ($strictSanityCheck) throw new AppWareComponentNotFoundException(sprintf("%s %s Method not found", static::$errorControllerClass, static::$errorNotFoundMethod));
        }

        if (static::$errorControllerFound) {
            if (static::$errorErrorMethodFound) {
                $className = static::$errorControllerClass;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$errorErrorMethod) : static::$errorErrorMethod;
                $controllerName = strtolower(static::$errorControllerName);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();//some 404 debug thingy
            } else {
                throw new AppWareComponentNotFoundException(sprintf("%s %s Method not found", static::$errorControllerClass, static::$errorErrorMethod));
            }

            $controller = new $className();

            if ($deliveryType) call_user_func_array(array($controller, 'deliveryType'), array($deliveryType));
            if ($deliveryMethod) call_user_func_array(array($controller, 'deliveryMethod'), array($deliveryMethod));
            if ($deliveryMasterView) call_user_func_array(array($controller, 'masterView'), array($deliveryMasterView));

            $controller->methodName = $methodName;
            $controller->controllerMethod = $controllerMethod;

            $error = array();
            $error['code'] = call_user_func_array(array($e, 'getCode'), array())?:500;
            $error['message'] = call_user_func_array(array($e, 'getMessage'), array())?:'Unhandled error';
            call_user_func_array(array($controller, 'setJson'), array('error', $error));
            unset($error);

            static::controller($controller);

            static::controller()->setData('exception', $e);

            call_user_func_array(array(static::controller(), $methodName), array());

        } else throw new AppWareComponentNotFoundException(sprintf("%s Controller not found", static::$errorControllerClass));
    }

    static public function throwNotFoundPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView) {
        $view = static::view();
        $outputBufferLevel = $view ? $view->outputBufferLevel() : ob_get_level() - 1;
        while (ob_get_level() > $outputBufferLevel && ob_end_clean()) {
            // do nothing
        }

        if (static::$errorControllerFound) {
            if (static::$errorNotFoundMethodFound) {
                $className = static::$errorControllerClass;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$errorNotFoundMethod) : static::$errorNotFoundMethod;
                $controllerName = strtolower(static::$errorControllerName);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();//some 404 debug thingy
            } elseif (static::$errorErrorMethodFound) {
                $className = static::$errorControllerClass;
                $methodName = static::$controllerLowersCaseMethodName ? lcfirst(static::$errorErrorMethod) : static::$errorErrorMethod;
                $controllerName = strtolower(static::$errorControllerName);
                $controllerMethod = strtolower($methodName);
                $methodArgs = array();//some 404 debug thingy
            } else {
                throw new AppWareComponentNotFoundException(sprintf("%s %s Method not found", static::$errorControllerClass, static::$errorErrorMethod));
            }

            $controller = new $className();

            if ($deliveryType) call_user_func_array(array($controller, 'deliveryType'), array($deliveryType));
            if ($deliveryMethod) call_user_func_array(array($controller, 'deliveryMethod'), array($deliveryMethod));
            if ($deliveryMasterView) call_user_func_array(array($controller, 'masterView'), array($deliveryMasterView));

            $controller->methodName = $methodName;
            $controller->controllerMethod = $controllerMethod;

            $error = array();
            $error['code'] = call_user_func_array(array($e, 'getCode'), array())?:404;
            $error['message'] = call_user_func_array(array($e, 'getMessage'), array())?:'Resource not found';
            call_user_func_array(array($controller, 'setJson'), array('error', $error));
            unset($error);

            static::controller($controller);

            static::controller()->setData('exception', $e);

            call_user_func_array(array(static::controller(), $methodName), array());

        } else throw new AppWareComponentNotFoundException(sprintf("%s Controller not found", static::$errorControllerClass));
    }

    /**
     * Config singleton
     *
     * @param void
     * @return AppWareConfig
     */
    static public function config() {
        if (is_null(static::$config))
            static::$config = AppWareConfig::getInstance();
        return static::$config;
    }

    /**
     * The controller at hand
     *
     * @param void
     * @return AppWareController
     */
    static public function controller($controller = NULL) {
        //return static::$controller;
        if (!is_null($controller))
            static::$controller = $controller;
        return static::$controller;
    }

    /**
     * Router singleton
     *
     * @param void
     * @return Klein
     */
    static public function router() {
        if (is_null(static::$router))
            static::$router = new Klein();
        return static::$router;
    }

    /**
     * Get or force the request query
     *
     * @param string $query
     * @return string
     */
    static public function requestQuery($query = null) {
        if ($query)
            static::$_query = $query;
        if (is_null(static::$_query)) {
            if (static::request())
                static::$_query = static::request()->param('rq');
            else static::$_query = NULL;
        }
        return static::$_query;
    }

    /**
     * Router singleton request
     *
     * @param void
     * @return Request
     */
    static public function request() {
        return static::router()->request();
    }

    /**
     * Router singleton response
     *
     * @param void
     * @return Response
     */
    static public function response() {
        return static::router()->response();
    }

    /**
     * Router singleton service provider
     *
     * @param void
     * @return ServiceProvider
     */
    static public function service() {
        return static::router()->service();
    }

    /**
     * View handler singleton
     *
     * @param void
     * @return AppWareView
     */
    static public function view() {
        if (is_null(static::$view))
            static::$view = new AppWareView();
        return static::$view;
    }

    /**
     * Old database singleton. Replaced with the Capsule instead
     *
     * @param void
     * @return AppWareDatabaseManager
     */
    static public function db() {
        return static::databaseManager();
    }

    /**
     * Auth manager singleton
     *
     * @param void
     * @return AppWareAuth
     */
    static public function auth() {
        if (is_null(static::$auth))
            static::$auth = AppWareAuth::getInstance();
        return static::$auth;
    }

    /**
     * Mailer singleton
     *
     * @param void
     * @return AppWareMailer
     */
    static public function mailer() {
        if (is_null(static::$mailer))
            static::$mailer = AppWareMailer::getInstance();
        return static::$mailer;
    }

    /**
     * Model validation singleton
     *
     * @param void
     * @return AppWareValidation
     */
    static public function validation() {
        if (is_null(static::$validation))
            static::$validation = AppWareValidation::getInstance();
        return static::$validation;
    }

    /**
     * FireLogger singleton
     *
     * @param void
     * @return FireLogger
     */
    static public function fireLogger() {
        if (is_null(static::$fireLogger))
            static::$fireLogger = new FireLogger();
        return static::$fireLogger;
    }

    /**
     * Logger singleton
     *
     * @param void
     * @return AppWareLogger
     */
    static public function logger() {
        if (is_null(static::$logger))
            static::$logger = AppWareLogger::getInstance();
        return static::$logger;
    }

    /**
     * Error handler singleton
     *
     * @param void
     * @return AppWareErrorHandler
     */
    static public function errorHandler() {
        if (is_null(static::$errorHandler))
            static::$errorHandler = AppWareErrorHandler::getInstance();
        return static::$errorHandler;
    }

    /**
     * Form handler singleton
     *
     * @param void
     * @return AppWareForm
     */
    static public function form() {
        if (is_null(static::$formHandler))
            static::$formHandler = AppWareForm::getInstance();
        return static::$formHandler;
    }

    /**
     * Capsule database manager singleton
     *
     * @param void
     * @return AppWareDatabaseManager
     */
    static public function databaseManager() {
        if (is_null(static::$dbManager))
            static::$dbManager = AppWareDatabaseManager::getInstance();
        return static::$dbManager;
    }

    /**
     * Cache manager singleton
     *
     * @param void
     * @return AppWareCacheManager
     */
    static public function cacheManager() {
        if (is_null(static::$cacheManager))
            static::$cacheManager = AppWareCacheManager::getInstance();
        return static::$cacheManager;
    }

    /**
     * Dispatch the request.
     * Web based or command line one.
     *
     * @param array
     * @return void
     */
    public function dispatch($args = null) {
        static::_rawRequestParse($_GET, isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:'');
        static::_rawRequestParse($_POST, file_get_contents('php://input'));
        static::_rawRequestParse($_COOKIE, isset($_SERVER['HTTP_COOKIE'])?preg_replace('#;(\s)*#', '&', $_SERVER['HTTP_COOKIE']):'');

        $request = new Request($_GET, $_POST, $_COOKIE, $_SERVER, $_FILES, null);
        $request->paramsNamed()->set('args', $args);

        static::router()->dispatch($request);
        return;
    }

    /**
     * Run that thing, will ya!
     *
     * @param array
     * @return void
     */
    public function run($args = null) {
        $this->start();
        $this->dispatch($args);
    }

    /**
     * Get or force the time zone
     *
     * @param string $timeZone
     * @return string
     */
    static public function timeZone($timeZone = null) {
        if ($timeZone?:false) {
            static::$defaultTimeZone = $timeZone;
            date_default_timezone_set(static::$defaultTimeZone);
        }
        if (is_null(static::$defaultTimeZone)) {
            static::$defaultTimeZone = static::config()->get('AppWare.TimeZone', 'Europe/Paris');
            date_default_timezone_set(static::$defaultTimeZone);
        }
        return static::$defaultTimeZone;
    }

    /**
     * Get or force the default content type
     *
     * @param string $contentType
     * @return string
     */
    static public function contentType($contentType = null) {
        if ($contentType?:false) {
            static::$defaultContentType = $contentType;
        }
        if (is_null(static::$defaultContentType)) {
            static::$defaultContentType = static::config()->get('AppWare.ContentType', 'text/html');
        }
        return static::$defaultContentType;
    }

    /**
     * Get or force the default charset
     *
     * @param string $charset
     * @return string
     */
    static public function charset($charset = null) {
        if ($charset?:false) {
            static::$defaultCharset = $charset;
        }
        if (is_null(static::$defaultCharset)) {
            static::$defaultCharset = static::config()->get('AppWare.TimeZone', 'utf-8');
        }
        return static::$defaultCharset;
    }

    /**
     * Get or force the default controllers namespace
     *
     * @param string $controllersNameSpace
     * @return string
     */
    static public function controllersNameSpace($controllersNameSpace = null) {
        if ($controllersNameSpace?:false) {
            static::$controllersNameSpace = $controllersNameSpace;
        }
        if (is_null(static::$controllersNameSpace)) {
            static::$controllersNameSpace = static::config()->get('AppWare.ControllersNameSpace', '');
        }
        return static::$controllersNameSpace;
    }

    /**
     * Get or force the default controllers class suffix
     *
     * @param string $controllersClassSuffix
     * @return string
     */
    static public function controllersClassSuffix($controllersClassSuffix = null) {
        if ($controllersClassSuffix?:false) {
            static::$controllersClassSuffix = $controllersClassSuffix;
        }
        if (is_null(static::$controllersClassSuffix)) {
            static::$controllersClassSuffix = static::config()->get('AppWare.ControllersClassSuffix', '');
        }
        return static::$controllersClassSuffix;
    }

    /**
     * Get or force the default lower case use of controllers method names
     *
     * @param string $controllersLowerCaseMethodName
     * @return string
     */
    static public function controllersLowerCaseMethodName($controllersLowerCaseMethodName = null) {
        if ($controllersLowerCaseMethodName?:false) {
            static::$controllerLowersCaseMethodName = $controllersLowerCaseMethodName;
        }
        if (is_null(static::$controllerLowersCaseMethodName)) {
            static::$controllerLowersCaseMethodName = static::config()->get('AppWare.ControllersLowerCaseMethodName', true);
        }
        return static::$controllerLowersCaseMethodName;
    }

    /**
     * Get or force the default modules namespace
     *
     * @param string $modulesNameSpace
     * @return string
     */
    static public function modulesNameSpace($modulesNameSpace = null) {
        if ($modulesNameSpace?:false) {
            static::$modulesNameSpace = $modulesNameSpace;
        }
        if (is_null(static::$modulesNameSpace)) {
            static::$modulesNameSpace = static::config()->get('AppWare.ModulesNameSpace', '');
        }
        return static::$modulesNameSpace;
    }

    /**
     * Get or force the default modules class suffix
     *
     * @param string $modulesClassSuffix
     * @return string
     */
    static public function modulesClassSuffix($modulesClassSuffix = null) {
        if ($modulesClassSuffix?:false) {
            static::$modulesClassSuffix = $modulesClassSuffix;
        }
        if (is_null(static::$modulesClassSuffix)) {
            static::$modulesClassSuffix = static::config()->get('AppWare.ModulesClassSuffix', '');
        }
        return static::$modulesClassSuffix;
    }

    static public function rootFolder() {
        if (is_null(static::$_rootPath))
            static::$_rootPath = APPWARE_ROOT_PATH;
        return static::$_rootPath;
    }

    static public function privateFolder() {
        if (is_null(static::$_privatePath))
            static::$_privatePath = APPWARE_PRIVATE_PATH;
        return static::$_privatePath;
    }

    static public function temporaryFolder() {
        if (is_null(static::$_tmpPath))
            static::$_tmpPath = APPWARE_TMP_PATH;
        return static::$_tmpPath;
    }

    static public function webFolder() {
        if (is_null(static::$_webPath))
            static::$_webPath = APPWARE_WEB_PATH;
        return static::$_webPath;
    }

    static public function webRoot($webroot = '') {
        if ($webroot != '') {
            // Apply $webroot to static::$_webRoot and return it;
            static::$_webRoot = $webroot;
        } else if (static::$_webRoot != '') {
            // Return static::$_webRoot; if set;
        } else {
            $serverSchema = static::config()->get('AppWare.ServerSchema', 'http');
            $serverHost = static::config()->get('AppWare.ServerHost', static::request()->server()->get('HTTP_HOST', ''));
            static::$_webRoot = $serverSchema . '://' . $serverHost;
        }
        return static::$_webRoot;
    }

    static public function webRootPath() {
        return str_replace(static::rootFolder(), '', static::webFolder());
    }

    static public function webRootUrl($url) {
        $paramsGet = array_intersect_key(static::request()->paramsGet()->all(), array("rq" => "rq", "Page" => "Page", "DeliveryType" => "DeliveryType", "DeliveryMethod" => "DeliveryMethod", "DeliveryMasterView" => "DeliveryMasterView", ));
        if (isset($paramsGet['rq'])) unset($paramsGet['rq']);
        $url .= ((count($paramsGet) > 0)?'?' . http_build_query($paramsGet):'');
        return static::webRootPath() . DIRECTORY_SEPARATOR . ltrim($url, '/');
    }

    static public function webUrl($url) {
        return static::webRoot() . static::webRootUrl($url);
    }

    static public function webPath() {
        return str_replace(static::rootFolder(), '', static::assetsFolder());
    }

    static public function webMediasPath() {
        $mediasRootFolder = static::mediasRootFolder();
        $rootFolder = static::rootFolder();

        $mediasFolder = static::mediasFolder();

        if ($mediasRootFolder != $rootFolder) {
            $schema = static::config()->get('AppWare.ServerSchema', 'http');
            $mediasHost = static::config()->get('AppWare.MediasServerHost', static::config()->get('AppWare.ServerHost', static::request()->server()->get('SERVER_HOST', '')));

            return sprintf('%s://%s%s', $schema, $mediasHost, str_replace($mediasRootFolder, '', $mediasFolder));
        } elseif($mediasRootFolder) {
            return str_replace($mediasRootFolder, '', $mediasFolder);
        } elseif($rootFolder) {
            return str_replace($rootFolder, '', $mediasFolder);
        } else {
            return $mediasFolder;
        }
    }

    static public function webMediasUrl($url) {
        $paramsGet = array_intersect_key(static::request()->paramsGet()->all(), array("rq" => "rq", "Page" => "Page", "DeliveryType" => "DeliveryType", "DeliveryMethod" => "DeliveryMethod", "DeliveryMasterView" => "DeliveryMasterView", ));
        if (isset($paramsGet['rq'])) unset($paramsGet['rq']);
        $url .= ((count($paramsGet) > 0)?'?' . http_build_query($paramsGet):'');
        return static::webMediasPath() . DIRECTORY_SEPARATOR . ltrim($url, '/');
    }

    static public function libFolder() {
        if (is_null(static::$_libPath))
            static::$_libPath = APPWARE_LIBRARY_PATH;
        return static::$_libPath;
    }

    static public function configFolder() {
        if (is_null(static::$_configPath))
            static::$_configPath = APPWARE_CONFIG_PATH;
        return static::$_configPath;
    }

    static public function cacheFolder() {
        if (is_null(static::$_cachePath))
            static::$_cachePath = APPWARE_CACHE_PATH;
        return static::$_cachePath;
    }

    static public function publicFolder() {
        if (is_null(static::$_publicPath))
            static::$_publicPath = APPWARE_PUBLIC_PATH;
        return static::$_publicPath;
    }

    static public function assetsFolder() {
        return static::publicFolder() . '/templates' . '/default';
    }

    static public function mediasFolder() {
        if (is_null(static::$_mediasPath)) {
            $mediasPath = defined('APPWARE_MEDIAS_PATH')?APPWARE_MEDIAS_PATH:false;
            if ($mediasPath)
                static::$_mediasPath = $mediasPath;
            else
                static::$_mediasPath = static::publicFolder() . '/medias';
        }
        return static::$_mediasPath;
    }

    static public function mediasRootFolder() {
        if (is_null(static::$_mediasRootPath)) {
            $mediasRootPath = defined('APPWARE_MEDIAS_ROOT_PATH')?APPWARE_MEDIAS_ROOT_PATH:false;
            if ($mediasRootPath)
                static::$_mediasRootPath = $mediasRootPath;
            else
                static::$_mediasRootPath = static::rootFolder();
        }
        return static::$_mediasRootPath;
    }

    static public function viewsFolder() {
        if (is_null(static::$_viewsPath))
            static::$_viewsPath = APPWARE_TEMPLATES_PATH . '/default' . '/views';
        return static::$_viewsPath;
    }

    static public function redirect($destination = false, $statusCode = NULL) {
        if (!$destination)
            $destination = static::webRootUrl('');

        // Close any db connections before exit
        //static::db()->close();
        // Clear out any previously sent content
        ob_end_clean();

        // assign status code
        $sendCode = (is_null($statusCode)) ? 302 : $statusCode;
        // re-assign the location header
        header("Location: ".static::webRootUrl($destination), true, $sendCode);
        // Exit
        exit();
    }

    static public function noAuthRedirect() {
        $auth = static::auth();
        if (!$auth->check()) static::redirect("/entry/");
    }

    static protected function _rawRequestParse(&$target, $source, $keep = false) {
        if (!$source) {
            return;
        }
        $keys = array();

        $source = preg_replace_callback(
            '/
            # Match at start of string or &
            (?:^|(?<=&))
            # Exclude cases where the period is in brackets, e.g. foo[bar.blarg]
            [^=&\[]*
            # Affected cases: periods and spaces
            (?:\.|%20)
            # Keep matching until assignment, next variable, end of string or
            # start of an array
            [^=&\[]*
            /x',
            function ($key) use (&$keys) {
                $keys[] = $key = base64_encode(urldecode($key[0]));
                return urlencode($key);
            },
            $source
        );

        if (!$keep) {
            $target = array();
        }

        parse_str($source, $data);
        foreach ($data as $key => $val) {
            // Only unprocess encoded keys
            if (!in_array($key, $keys)) {
                $target[$key] = $val;
                continue;
            }

            $key = base64_decode($key);
            $target[$key] = $val;

            if ($keep) {
                // Keep a copy in the underscore key version
                $key = preg_replace('/(\.| )/', '_', $key);
                $target[$key] = $val;
            }
        }
    }

}