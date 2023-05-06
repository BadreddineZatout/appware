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

namespace AppWare\Core\Controllers;

use \Exception;

class eWebService extends Base
{

    public static $DEFAULT_SERVICE_VERSION = '0.9.0';

    public static $DEFAULT_SERVICE_USER_AGENT_NAME = 'Application';

    public static $SERVICE_VERSIONS = array();

    public $serviceVersion = false;

    public $userAgent = false;

    public $debugOutput = false;

    public $gzipOutput = false;

    public $jsonCache = false;

    public function __construct() {
        parent::__construct();
        $this->_deliveryMethod = APPWARE_DELIVERY_METHOD_JSON;
        $this->_deliveryType = APPWARE_DELIVERY_TYPE_ALL;
        spl_autoload_register(array($this, 'serviceLoader'));
    }

    public function service($requestType = false) {
        $defaultMethod = "exec";

        $this->_computeParameters(func_get_args());

        $this->initilizeService();

        $className = ucwords($requestType ? preg_replace('#([\w]+)#msi', '$1', $requestType) : 'default') . "Service";
        $methodName = ucwords($defaultMethod);
        $controllerName = strtolower($requestType);
        $controllerMethod = strtolower($methodName);
        $methodArgs = array();

        $error = false;
        $output = false;

        try {
            if (!class_exists($className))
                throw new Exception("Service $className not found");//Catched at the top

            if ($methodName != $defaultMethod && !method_exists($className, $methodName)) {
                $methodName = $defaultMethod;
                $controllerMethod = strtolower($methodName);
            }
            if (!method_exists($className, $methodName))
                throw new Exception("Method $methodName on service $className not found");//Catched at the top

            $controller = new $className();

            $output = call_user_func_array(array($controller, $methodName), $methodArgs);
        } catch (Exception $e) {
            $error = array();
            $error['code'] = $e->getCode();
            $error['message'] = $e->getMessage();
            $this->setJson('error', $error);
        }

        if (!$error && !$output) {
            $error = array();
            $error['code'] = 0;
            $error['message'] = "Unknown error occured on service $className with method $methodName";
            $this->setJson('error', $error);
        } else {
            $this->setJson('result', $output);
        }

        $this->render();
    }

    private function initilizeService() {

        $this->userAgent();

        $this->serviceVersion();

        $this->gzipOutput();

        $this->debugOutput();

        $this->jsonCache(true);

        //$this->headers();
    }

    private function _computeParameters($params = array()) {
        $requestType = array_shift($params);

        static::request()->paramsNamed()->set('reqtype', $requestType);

        if (is_array($params) && count($params) > 0 && !empty($params[0])) {
            foreach($params as $key => $param) {
                if (!static::_isOdd($key) & isset($params[$key+1])) static::request()->paramsNamed()->set($params[$key], $params[$key+1]);
                elseif (isset($params[$key-1])) static::request()->paramsNamed()->set($params[$key-1], $params[$key]);
            }
        }
    }

    public function parameter($key = false, $default = false) {
        if (!$key)
            if ($default) return $default;
            else return false;
        return static::request()->param($key, $default);
    }

    private function userAgent($serverua = false) {
        if (empty($serverua)) {
            $serverua = static::request()->userAgent();
        }

        $this->userAgent = $serverua;

        return $this->userAgent;
    }

    private function serviceVersion($paramv = false) {
        if (empty($paramv)) {
            $paramv = $this->parameter('v');
        }

        if (empty($paramv)) {
            $paramv = static::request()->paramsGet()->get('v', false);
        }

        if (empty($paramv)) {
            $service_version = static::$DEFAULT_SERVICE_VERSION;
        }

        if (isset($paramv)) {

            if (in_array($paramv, static::$SERVICE_VERSIONS)) {
                $service_version = $paramv;
            } else {
                preg_match('#' . preg_quote(static::$DEFAULT_SERVICE_USER_AGENT_NAME) . ' ([0-9\.]+) \(([^;]+); ([^;]+); ([^;]+)\)#msi', $this->userAgent, $service_user_agent_check);
                if (is_array($service_user_agent_check) && isset($service_user_agent_check[1]) && in_array($service_user_agent_check[1], static::$SERVICE_VERSIONS)) {
                    $service_version = $service_user_agent_check[1];
                } else {
                    $service_version = static::$DEFAULT_SERVICE_VERSION;
                }
            }
        } else {
            preg_match('#' . preg_quote(static::$DEFAULT_SERVICE_USER_AGENT_NAME) . ' ([0-9\.]+) \(([^;]+); ([^;]+); ([^;]+)\)#msi', $this->userAgent, $service_user_agent_check);
            if (is_array($service_user_agent_check) && isset($service_user_agent_check[1]) && in_array($service_user_agent_check[1], static::$SERVICE_VERSIONS)) {
                $service_version = $service_user_agent_check[1];
            } else {
                $service_version = static::$DEFAULT_SERVICE_VERSION;
            }
        }

        $this->serviceVersion = $service_version;

        return $this->serviceVersion;
    }

    private function jsonCache($jsonCache = false) {

        if ($jsonCache) {
            $this->jsonCache = true;
        } else {
            $this->jsonCache = false;
        }

        return $this->jsonCache;
    }

    private function gzipOutput($paramcp = false) {
        if (empty($paramcp)) {
            $paramcp = $this->parameter('compress');
        }

        if (empty($paramcp)) {
            $paramcp = static::request()->paramsGet()->get('compress', false);
        }

        if (empty($paramcp)) {
            $paramcp = "0";
        }

        switch ($paramcp) {
            case "1":
                $gzipoutput = true;
                break;
            case "0":
            default:
                $gzipoutput = false;
                break;
        }

        $this->gzipOutput = $gzipoutput;

        return $this->gzipOutput;
    }

    private function debugOutput($paramdb = false) {
        if (empty($paramdb)) {
            $paramdb = $this->parameter('debug');
        }

        if (empty($paramdb)) {
            $paramdb = static::request()->paramsGet()->get('debug', false);
        }

        if (empty($paramdb)) {
            $paramdb = "0";
        }

        switch ($paramdb) {
            case "1":
                $debugoutput = true;
                break;
            case "0":
            default:
                $debugoutput = false;
                break;
        }

        $this->debugOutput = $debugoutput;

        return $this->debugOutput;
    }

    /*public function headers() {
        header("Content-Type: ".($this->gzipOutput?"application/x-gzip":"text/javascript")."; charset=utf-8");
        
        //header("HTTP/1.1 304 Not Modified");
        switch ($this->serviceVersion) {
           case static::$DEFAULT_SERVICE_VERSION:
                //Continue
                break;
           default:
              break;
        }
    }*/
    public static function _isOdd($number) {
        if ($number % 2) return true;
        else return false;
    }

    private function serviceLoader($className) {
        $versionPosition = array_search($this->serviceVersion, static::$SERVICE_VERSIONS);

        if ($versionPosition != false) {
            $serviceVersions = array_slice(static::$SERVICE_VERSIONS, $versionPosition);
        } else {
            $serviceVersions = static::$SERVICE_VERSIONS;
        }

        $serviceClassesPath = APPWARE_APPLICATION_PATH . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $this->controllerName;

        $noVersion = true;
        foreach($serviceVersions as $serviceVersion) {
            if (file_exists($serviceClassesPath . DIRECTORY_SEPARATOR . $serviceVersion . DIRECTORY_SEPARATOR . $className . '.php')) {
                require_once($serviceClassesPath . DIRECTORY_SEPARATOR . $serviceVersion . DIRECTORY_SEPARATOR . $className . '.php');
                $noVersion = false;
                break;
            }
        }

        if ($noVersion)
            if (file_exists($serviceClassesPath . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . $className . '.php'))
                require_once($serviceClassesPath . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . $className . '.php');
    }

}