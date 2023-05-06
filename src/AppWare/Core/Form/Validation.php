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

use \AppWare\Core\App as AppWare;
use \Valitron\Validator;

class Validation extends Validator
{
    /**
    * @var Validation
    * @access private
    * @static
    */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @param array
     * @param string
     * @param string
     * @return Validation
     */
    public function __construct($fields = array(), $lang = null, $langDir = null) {
        parent::__construct($fields, array(), $lang?:'en', $langDir?:(static::app()->privateFolder() . '/../vendor/vlucas/valitron/lang'));
    }

    /**
     * Use a given set of data & fields
     *
     * @param array
     * @param array
     * @return void
     */
    public function fields($data, $fields = array()) {
        // Allows filtering of used input fields against optional second array of field names allowed
        // This is useful for limiting raw $_POST or $_GET data to only known fields
        $this->_fields = !empty($fields) ? array_intersect_key($data, array_flip($fields)) : $data;
    }

    /**
     * Get back the Singleton instance
     *
     * @param array
     * @return Validation
     */
    public static function getInstance($fields = array()) {
        if(is_null(static::$_instance)) {
            static::$_instance = new Validation($fields);
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
