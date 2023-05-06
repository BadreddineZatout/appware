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

namespace AppWare\Core\Modules;

use \AppWare\Core\App as AppWare;
use \Exception;

class Base
{
    
    /** The name of the current asset that is being rendered.
     *
     * @var string
     */
    /*public $assetName = '';*/
    
    /**
     * Data that is passed into the view.
     * 
     * @var array
     */
    public $data = array();
    
    public $visible = true;

    public function __construct() {
    }
    
   /**
    * Returns the name of this module. Unless it is overridden, it will simply
    * return the class name.
    *
    * @return string
    */
   public function name() {
       $className = get_class($this);
       $controllerName = str_replace(array((new \ReflectionClass($this))->getNamespaceName(), '\\', static::app()->modulesClassSuffix()), '', $className);
       return $controllerName;
   }
    
    /**
     * Returns the name of the asset where this component should be rendered.
     */
    public function assetTarget() {
       throw new Exception(get_class($this) . " : Any class extended from the Module class must implement it's own assetTarget method.");
    }
    
    /** Get a value out of the controller's data array.
     *
     * @param string $path The path to the data.
     * @param mixed $default The default value if the data array doesn't contain the path.
     * @return mixed
     * @see GetValueR()
     */
    public function data($key, $default = '' ) {
     
       if (is_array($this->data)) {
         if (isset($this->data[$key])) {
             return $this->data[$key];
         }
       }
       
       return $default;
    }
     
    public function render() {
       echo $this->toString();
    }
    
    /**
     * Returns the component as a string to be rendered to the screen. Unless
     * this method is overridden, it will attempt to find and return a view
     * related to this module automatically.
     *
     * @return string
     */
    public function toString() {
       if ($this->visible)
          return $this->fetchAsset();
       else
            return '';
    }
   
   /**
    * Fetches the contents of a view into a string and returns it. Returns
    * false on failure.
    *
    * @param string $view The name of the view to fetch. If not specified, it will use the value
    * of $this->view. If $this->view is not specified, it will use the value
    * of $this->requestMethod (which is defined by the dispatcher class).
    * @param string $controllerName The name of the controller that owns the view if it is not $this.
    * @param string $applicationFolder The name of the application folder that contains the requested controller
    * if it is not $this->applicationFolder.
    */
   public function fetchAsset($view = '') {
      $viewPath = $this->fetchAssetLocation($view);
      
      // Check to see if there is a handler for this particular extension.
      $viewHandler = static::app()->view();
      
      /*$viewContents = '';*/
      ob_start();
      if($viewHandler !== NULL) {
         $viewHandler->assetContent($viewPath, $this->data);
      } else {
        //Throw new ViewHandler Exception ...
      }
      
      return ob_get_clean();
      //echo ob_get_clean();
   }
   
   /**
    * Fetches the location of a view into a string and returns it. Returns
    * false on failure.
    *
    * @param string $view The name of the view to fetch. If not specified, it will use the value
    * of $this->view. If $this->view is not specified, it will use the value
    * of $this->requestMethod (which is defined by the dispatcher class).
    * @param string $controllerName The name of the controller that owns the view if it is not $this.
    *  - If the controller name is false then the name of the current controller will be used.
    *  - If the controller name is an empty string then the view will be looked for in the base views folder.
    * @param string $applicationFolder The name of the application folder that contains the requested controller if it is not $this->applicationFolder.
    * @return string
    */
   public function fetchAssetLocation($view = '') {
      if ($view == '')
         $view = strtolower($this->name());
         
      if (substr($view, -6) == 'module')
         $view = substr($view, 0, -6);
         
         
        $viewsFolder = static::app()->viewsFolder();
        
        $viewPath = $viewsFolder . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $view . '.php';
        
        return $viewPath;
      
      /*if ($viewPath === false && $throwError) {
         throw NotFoundException('View');
//         trigger_error(ErrorMessage("Could not find a '$view' view for the '$controllerName' controller.", $this->className, 'fetchViewLocation'), E_USER_ERROR);
      }

        return $viewPath;*/
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
