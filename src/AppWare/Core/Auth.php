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

use \AppWare\Core\Auth as AppWareAuth;
use \Cartalyst;

class Auth
{
    /**
    * @var Auth
    * @access private
    * @static
    */
    private static $_instance = null;
    
    /**
    * Le nom du Président
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
    private function __construct() {
    }
    /**
    * Méthode qui crée l'unique instance de la classe
    * si elle n'existe pas encore puis la retourne.
    *
    * @param void
    * @return Auth
    */
    public static function getInstance() {
        if(is_null(static::$_instance)) {
            static::$_instance = new AppWareAuth();
        }
        return static::$_instance;
    }
    
    public function engine() {
        if (is_null(static::$_engine))
            static::init();
        return static::$_engine;
    }
    
    static public function init() {
            
            /*   
            * @param  Cartalyst\Sentinel\Hashing\HasherInterface  $hasher
            * @param  Cartalyst\Sentinel\Sessions\SessionInterface  $session
            * @param  Cartalyst\Sentinel\Cookies\CookieInterface  $cookie
            * @param  Cartalyst\Sentinel\Groups\GroupInterface  $groupProvider
            * @param  Cartalyst\Sentinel\Users\UserInterface  $userProvider
            * @param  Cartalyst\Sentinel\Throttling\ThrottleInterface  $throttleProvider
            */
            
            $hasher = new Cartalyst\Sentinel\Hashing\Sha256Hasher; // There are other hashers available, take your pick
            $userProvider = new Cartalyst\Sentinel\Users\Eloquent\Provider($hasher);

            $session = new Cartalyst\Sentinel\Sessions\NativeSession('awsu');

            // Note, all of the options below are, optional!
            $options = array(
                //'domain'    => '',// Default ""
                //'path'      => '/',// Default "/"
                //'secure'    => false,// Default "false"
                //'http_only' => false// Default "false"
            );

            $cookie = new Cartalyst\Sentinel\Cookies\NativeCookie($options, "awsu");//Default "cartalyst_sentinel"
            
            static::$_engine = Cartalyst\Sentinel\Facades\Native\Sentinel::createSentinel($userProvider, null, null, $session, $cookie);

    }
    
    public function getSessionKey() {
        $engine = static::engine();
        if ($engine !== null) {
            if ($engine->check()) {
                try
                {
                    // Get the current active/logged in user
                    $user = $engine->getUser();
                    return sha1($user->id . session_id() . $user->email);
                }
                catch (Cartalyst\Sentinel\Users\UserNotFoundException $e)
                {
                    // User wasn't found, should only happen if the user was deleted
                    // when they were already logged in or had a "remember me" cookie set
                    // and they were deleted.
                    return false;
                }
            } else return false;
        } else return false;
    }
    
    public function checkSessionKey($session_key = '') {
        $sessionKey = static::getSessionKey();
        if ($sessionKey == $session_key) {
            return true;
        } return false;
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
}
