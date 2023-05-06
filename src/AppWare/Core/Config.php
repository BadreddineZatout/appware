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
use \AppWare\Core\Support\Container;

class Config extends Container
{
    /**
     * @var Config
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @param array
     * @return Config
     */
    public function __construct($configuration = array()) {

        //Legacy format
        $configuration = $configuration?:array();
        $configFile = static::app()->configFolder() . '/config.php';
        if (file_exists($configFile)) {
            require_once($configFile);
        }

        //Legacy file
        if (isset($Configuration)) {
            $configuration = array_merge_recursive($configuration, $Configuration);
        }

        //New format
        parent::__construct($configuration);

        unset($configuration);
    }

    /**
     * Get back the Singleton instance
     *
     * @param array
     * @return Logger
     */
    public static function getInstance($configuration = array()) {
        if(is_null(static::$_instance)) {
            static::$_instance = new static($configuration);
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
