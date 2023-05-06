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

namespace AppWare\Core\Database;

use \AppWare\Core\App as AppWare;
use \AppWare\Core\Support\Container as Fluent;
use \Illuminate\Contracts\Container\Container;
use \Illuminate\Database\Capsule\Manager as Capsule;
use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Contracts\Foundation\Application;
use \PDO;
use \ReflectionClass;
use \Exception;

class Manager extends Capsule
{
    /**
    * @var Manager
    * @access private
    * @static
    */
    private static $_instance = null;

    /**
     * Setup the IoC container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function setupContainer(Container $container)
    {
        $this->container = $container;

        if (! $this->container->bound('config')) {
            $this->container->instance('config', new Fluent);
        }
    }
    
    /**
    * Constructor
    *
    * @param void
    * @return Manager
    */
    public function __construct() {
        
        parent::__construct();

        $this->setFetchMode(static::app()->config()->get('DatabaseManager.FetchMode', PDO::FETCH_CLASS));
        
        $manager = $this->getDatabaseManager();
        
        $drivers = static::app()->config()->get('DatabaseManager.Drivers', array());
        $connections = static::app()->config()->get('DatabaseManager.Connections', array());
        $defaultConnection = static::app()->config()->get('DatabaseManager.DefaultConnection', false);
        
        $driverInstances = array();
        $defaultEloquentConnection = false;
        
        if ((is_array($drivers) && count($drivers) > 0) || (is_array($connections) && count($connections) > 0)) {
            
            if (is_array($drivers) && count($drivers) > 0) {
                foreach ($drivers as $driver => $class) {
                    if (is_string($driver) && is_string($class)) {
                        try {
                            $driverInstance = call_user_func_array(array(new ReflectionClass($class), 'newInstance'), array($this));
                            call_user_func_array(array($driverInstance, 'register'), array());
                            $driverInstances[$driver] = $driverInstance;
                        } catch (Exception $e) {
                            //No exceptions thrown for now. silently fail ...
                        }
                    } elseif(is_int($driver) && is_string($class)) {
                        if (in_array($class, array('mysql'))) {//Add later on default pdo/eloquent drivers ...
                            $defaultEloquentConnection = true;
                        }
                    }
                }
            }
            
            if (is_array($connections) && count($connections) > 0) {
                foreach ($connections as $name => $connection) {
                    if (is_array($connection)) {
                        $this->addConnection($connection, $name);
                        $manager->setDefaultConnection($name);//Last one wins!
                    }
                }
            }
            
            foreach($driverInstances as $driver => $driverInstance) {
                try {
                    $driverInstance->boot();
                } catch (Exception $e) {
                    //No exceptions thrown for now. silently fail ...
                }
            }
            
            if ($defaultEloquentConnection) {
                //Boot Eloquent Model
                Model::setConnectionResolver($manager);
            }
            
            if ($defaultConnection) $manager->setDefaultConnection($defaultConnection);
            
        } else {//Legacy models
            $this->addConnection(array(
                'driver'    => 'mysql',
                'host'      => static::app()->config()->get('Database.Host'),
                'database'  => static::app()->config()->get('Database.Name'),
                'username'  => static::app()->config()->get('Database.User'),
                'password'  => static::app()->config()->get('Database.Password'),
                'charset'   => static::app()->config()->get('Database.CharacterEncoding', 'utf8'),
                'collation' => static::app()->config()->get('Database.CollationEncoding', 'utf8_unicode_ci'),
                'prefix'    => static::app()->config()->get('Database.DatabasePrefix', ''),
            ), 'default');//Keep the connection name as 'default' for now
            
            //Boot Eloquent Model
            Model::setConnectionResolver($manager);
            //Illuminate\Database\Eloquent\Model::setConnection('default');
        }
    }

    /**
     * Get back the Singleton instance
     *
     * @return Application
     */
    public function getApplication() {
        return $this->getContainer();
    }

    /**
     * Get back the Singleton instance
     *
     * @return Manager
     */
    public static function getInstance() {
        if(is_null(static::$_instance)) {
            static::$_instance = new static();
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