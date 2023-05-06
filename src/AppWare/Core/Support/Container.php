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

namespace AppWare\Core\Support;

use \Illuminate\Support\Fluent;
use \Illuminate\Support\Arr;

class Container extends Fluent
{

    /**
     * Constructor
     *
     * @param array
     * @return Container
     */
    public function __construct($attributes = array()) {

        //Legacy format
        $attributes = $attributes?:array();

        //New format
        $attr = array();
        foreach ($attributes as $key => $value)
        {
            $attr = Arr::set($attr, $key, $value);
        }

        unset($attributes);

        parent::__construct($attr);

        unset($attr);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        if (is_string($key) && strpos($key, '.') !== false)
            return Arr::get($this->attributes, $key, $default);
        else
            return parent::get($key, $default);
    }

    /**
     * Dynamically set the value of an attribute.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->__set($key, $value);
    }

    /**
     * Dynamically set the value of an attribute.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        if (is_string($key) && strpos($key, '.') !== false)
            Arr::set($this->attributes, $key, $value);
        else
            parent::__set($key, $value);
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        if (is_string($key) && strpos($key, '.') !== false)
            Arr::has($this->attributes, $key);
        else
            parent::__isset($key);
    }

    /**
     * Dynamically unset an attribute.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        if (is_string($key) && strpos($key, '.') !== false)
            Arr::forget($this->attributes, $key);
        else
            parent::__unset($key);
    }
}
