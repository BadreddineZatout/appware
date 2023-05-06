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

namespace AppWare\Core\Cache;

use \AppWare\Core\App as AppWare;
use \Illuminate\Cache\CacheManager;
use \Illuminate\Container\Container;
use \ReflectionClass;
use \Exception;

class Manager extends CacheManager
{
    /**
     * @var Manager
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @param void
     * @return Manager
     */
    public function __construct($app = null) {

        if (is_null($app))
            $app = static::app()->databaseManager()->getApplication();
        
        parent::__construct($app);

        $cacheConfig = static::app()->config()->get('CacheManager.Config', array());

        $stores = static::app()->config()->get('CacheManager.Stores', array_get($cacheConfig?:array(), 'cache.stores', array())?:array());
        $defaultStore = static::app()->config()->get('CacheManager.DefaultStore', array_get($cacheConfig?:array(), 'cache.default', false)?:false);

        if (empty($cacheConfig)) return null;
        
        //fallback to old config

        if (empty($stores))
        {
            $drivers = static::app()->config()->get('CacheManager.Drivers', array());
            $connections = static::app()->config()->get('CacheManager.Connections', array());

            if (!($defaultStore?:false)) {
                $defaultDriver = static::app()->config()->get('CacheManager.DefaultDriver', array_get($cacheConfig?:array(), 'cache.driver', false)?:false);
                $defaultStore = $defaultDriver;
            }

            $defaultDriver = $defaultStore;

            if (empty($drivers)) {
                if (in_array($defaultDriver, array('apc', 'array', 'file', 'memcached', 'wincache', 'xcache', 'redis', 'database'))) {//Add later on default drivers if needed ...
                    $drivers = array($defaultDriver);
                } else {
                    return null;
                }
            }

            $stores = array();

            foreach ($drivers as $driver)
            {
                array_set($stores, $driver, array_get($connections?:array(), $driver, array())?:(array_get($cacheConfig?:array(), sprintf('cache.%s', $driver), array())?:array()));
            }

            //specific config changes
            foreach ($stores as $storeKey => $store)
            {
                switch ($storeKey)
                {
                    case 'memcached':
                        $store = array(
                            'driver' => $storeKey,
                            'servers' => $store,
                        );
                        array_set($stores, $storeKey, $store);
                        break;
                    default:
                        $store = array(
                            'driver' => $storeKey,
                            'servers' => $store,
                        );
                        array_set($store, 'driver', $storeKey);
                        array_set($stores, $storeKey, $store);
                        break;
                }
            }
        }

        //config is a Fluent instance and cannot access dot notation elements
        foreach ($stores as $storeKey => $store)
        {
            $this->app['config'][sprintf('cache.stores.%s', $storeKey)] = $store;
        }
        $this->app['config']['cache.default'] = $defaultStore?:'file';
        
        if (array_get($stores?:array(), 'redis') || in_array('redis', $stores?:array())) {
            $this->app['config']['database.redis'] = array_get($this->app['config']['cache.stores'], 'redis.database', array())?:array();
            try {
                $storeInstance = call_user_func_array(array(new ReflectionClass('Illuminate\Redis\RedisServiceProvider'), 'newInstance'), array($this->app));
                call_user_func_array(array($storeInstance, 'register'), array());
            } catch(Exception $e) {}
        }
        if (array_get($stores?:array(), 'file') || in_array('file', $stores?:array())) {
            try {
                $storeInstance = call_user_func_array(array(new ReflectionClass('Illuminate\Redis\FilesystemServiceProvider'), 'newInstance'), array($this->app));
                call_user_func_array(array($storeInstance, 'register'), array());
            } catch(Exception $e) {}
        }
        if (array_get($stores?:array(), 'database') || in_array('database', $stores?:array())) {
            try {
                $storeInstance = call_user_func_array(array(new ReflectionClass('Illuminate\Encryption\EncryptionServiceProvider'), 'newInstance'), array($this->app));
                call_user_func_array(array($storeInstance, 'register'), array());

                $this->app->instance('db', static::app()->databaseManager());
            } catch(Exception $e) {}
        }

        try {
            $storeInstance = call_user_func_array(array(new ReflectionClass('Illuminate\Cache\CacheServiceProvider'), 'newInstance'), array($this->app));
            call_user_func_array(array($storeInstance, 'register'), array());
        } catch(Exception $e) {}
    }

    /**
     * Register the Cache Manager onto the Database Manager
     *
     * @param \Illuminate\Container\Container
     * @return void
     */
    public static function register($app = null) {
        try {

            if (is_null($app)) {
                $app = static::app()->databaseManager()->getContainer();
            }

            $app->bindIf('cache', function() use($app) {
                return static::getInstance($app);
            }, true);
        } catch(Exception $e) {}
    }

    /**
     * Get back the Singleton instance
     *
     * @param \Illuminate\Container\Container
     * @return Manager
     */
    public static function getInstance($app = null) {
        if(is_null(static::$_instance)) {
            static::$_instance = new static($app);
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