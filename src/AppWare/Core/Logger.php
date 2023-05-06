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

namespace AppWare\Core;

use \AppWare\Core\App as AppWare;
use \Monolog\Logger as MonologLogger;
use \Monolog\Handler\FirePHPHandler;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\LineFormatter;
use \Exception;

class Logger extends MonologLogger
{
    /**
    * @var Logger
    * @access private
    * @static
    */
    private static $_instance = null;
    
    /**
    * Constructor
    *
    * @param string
    * @param array
    * @return Logger
    */
    public function __construct($name = 'AppWareLogger', $handlers = array()) {
        $handlers = $handlers?:array();

        // Now add some handlers
        // StreamHandler
        $enableStreamHandler = static::app()->config()->get('AppWare.Logger.StreamHandler', false);
        if ($enableStreamHandler) {
            $streamHandlerFile = static::app()->config()->get('AppWare.Logger.StreamHandlerFile', false);
            if (!defined('APPWARE_BASE_PATH')) $streamHandlerFile = false;
            else $streamHandlerFile = APPWARE_BASE_PATH . $streamHandlerFile;
            if ($streamHandlerFile) {
                if (!file_exists($streamHandlerFile)) {
                    if (!is_dir(dirname($streamHandlerFile))) {
                        mkdir(dirname($streamHandlerFile), 0750, true);
                    }
                    if (is_writable(dirname($streamHandlerFile))) {
                        touch($streamHandlerFile);
                    }
                }
                if (file_exists($streamHandlerFile)) {
                    $streamHandlerLevel = static::app()->config()->get('AppWare.Logger.StreamHandlerLevel', MonologLogger::DEBUG);

                    if (!in_array($streamHandlerLevel, array(MonologLogger::DEBUG, MonologLogger::INFO, MonologLogger::NOTICE, MonologLogger::WARNING, MonologLogger::ERROR, MonologLogger::CRITICAL, MonologLogger::ALERT, MonologLogger::EMERGENCY)))
                    {
                        $streamHandlerLevel = MonologLogger::DEBUG;
                    }
                    $streamHandlerBubble = static::app()->config()->get('AppWare.Logger.StreamHandlerBubble', true);
                    try {
                        $streamHandler = new StreamHandler($streamHandlerFile, $streamHandlerLevel, $streamHandlerBubble);
                        $lineFormatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", null, true);
                        $streamHandler->setFormatter($lineFormatter);

                        $handlers[] = $streamHandler;
                    } catch(Exception $e) {}
                }
            }
        }

        // FirePHPHandler
        $enableFirePHPHandler = static::app()->config()->get('AppWare.Logger.FirePHPHandler', false);
        if ($enableFirePHPHandler) {
            $handlers[] = new FirePHPHandler();
        }

        parent::__construct($name, $handlers);
    }

    /**
     * Get back the Singleton instance
     *
     * @param string
     * @param array
     * @return Logger
     */
    public static function getInstance($name = 'AppWareLogger', $handlers = array()) {
        if(is_null(static::$_instance)) {
            static::$_instance = new static($name, $handlers);
        }
        return static::$_instance;
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