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
use \AppWare\Core\ErrorHandlers\Base as AppWareErrorHandler;
use \AppWare\Core\ErrorHandlers\DefaultHandler as AppWareDefaultErrorHandler;
use \Whoops\Run;
use \Whoops\Handler\HandlerInterface;
use \Whoops\Handler\PlainTextHandler;
use \Whoops\Handler\PrettyPageHandler;
use \Whoops\Handler\CallbackHandler;
use \Whoops\Handler\JsonResponseHandler;
use \Whoops\Handler\XmlResponseHandler;

class Base
{
    /**
    * @var Base
    * @access private
    * @static
    */
    private static $_instance = null;
    
    /**
    * Logger Library
    *
    * @var string
    * @access private
    */
    private static $_engine = null;
    
    /**
    * Constructeur de la classe
    *
    * @param void
    * @return void
    */
    public function __construct() {
        static::$_engine = static::instantiateEngine();
    }
    /**
    * Méthode qui crée l'unique instance de la classe
    * si elle n'existe pas encore puis la retourne.
    *
    * @param void
    * @return Base
    */
    public static function getInstance() {
        if(is_null(static::$_instance)) {
            static::$_instance = new Base();
        }
        return static::$_instance;
    }

    /**
     * Cache Singleton
     *
     * @param void
     * @return HandlerInterface
     */
    private static function instantiateEngine() {
        $enableErrorHandler = static::app()->config()->get('AppWare.ErrorHandler.Enable', true);
        if ($enableErrorHandler) {
            $engine = new Run;
            
            if ($engine) {
                $enablePlainTextHandler = static::app()->config()->get('AppWare.ErrorHandler.PlainTextHandler', false);
                if ($enablePlainTextHandler) {
                    $engine->pushHandler(new PlainTextHandler);
                }
                
                $enablePrettyPageHandler = static::app()->config()->get('AppWare.ErrorHandler.PrettyPageHandler', false);
                if ($enablePrettyPageHandler) {
                    $engine->pushHandler(new PrettyPageHandler);
                }
                
                $enableCallbackHandler = static::app()->config()->get('AppWare.ErrorHandler.CallbackHandler', false);
                if ($enableCallbackHandler) {
                    $engine->pushHandler(new CallbackHandler(function(){}));
                }
                
                $enableJsonResponseHandler = static::app()->config()->get('AppWare.ErrorHandler.JsonResponseHandler', false);
                if ($enableJsonResponseHandler) {
                    $engine->pushHandler(new JsonResponseHandler);
                }
                
                $enableXmlResponseHandler = static::app()->config()->get('AppWare.ErrorHandler.XmlResponseHandler', false);
                if ($enableXmlResponseHandler) {
                    $engine->pushHandler(new XmlResponseHandler);
                }
                
                if (!$enablePlainTextHandler && !$enablePrettyPageHandler && !$enableCallbackHandler && !$enableJsonResponseHandler && !$enableXmlResponseHandler) {
                    $engine->pushHandler(new AppWareDefaultErrorHandler);
                }
                
                //$engine->register();//Has to be called manually while in the framework init process
            } else $engine = null;
        } else $engine = null;
        
        return $engine;
    }
    
    public function register() {
        $engine = static::engine();
        if ($engine !== null) {
            $engine->register();
            return true;
        } else return false;
    }
    
    public function unregister() {
        $engine = static::engine();
        if ($engine !== null) {
            $engine->unregister();
            return true;
        } else return false;
    }

    /**
     * Error handler Singleton
     *
     * @param void
     * @return HandlerInterface
     */
    public static function engine() {
        if (is_null(static::$_engine))
            static::$_engine = static::instantiateEngine();
        return static::$_engine;
    }
    
    public function __call($name, $arguments) {
        $engine = static::engine();
        if ($engine !== null) {
            return call_user_func_array(array( $engine, $name ), $arguments);
        } else return false;
    }

    /**  As of PHP 5.3.0  */
    public static function __callStatic($name, $arguments) {
        $engine = static::engine();
        if ($engine !== null) {
            return call_user_func_array(array( $engine, $name ), $arguments);
        } else return false;
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