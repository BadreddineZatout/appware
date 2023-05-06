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

namespace AppWare\Core\Form;

use \Former\Facades\Former;
use \Former\FormerServiceProvider;

class Form extends Former
{
    /**
     * @var Form
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @param void
     * @return Form
     */
    public function __construct() {
        (new FormerServiceProvider(Former::getFacadeApplication()))->register();
    }

    /**
     * Get back the Singleton instance
     *
     * @return Form
     */
    public static function getInstance() {
        if(is_null(static::$_instance)) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }
    
    public function __call($name, $arguments) {
        $instance = static::getInstance();
        if ($instance !== null) {
            return call_user_func_array(array( Former::class, $name ), $arguments);
        } else return false;
    }

    /**  As of PHP 5.3.0  */
    public static function __callStatic($name, $arguments) {
        $instance = static::getInstance();
        if ($instance !== null) {
            return call_user_func_array(array( Former::class, $name ), $arguments);
        } else return false;
    }
}