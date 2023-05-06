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

namespace AppWare\Core\Controllers;


use \AppWare\Core\App as AppWare;
use \AppWare\Core\Modules\Base as AppWareModule;
use \Exception;
use \stdClass;

class Base
{
    
    /**
    * The name of the class that has been instantiated. Typically this will be
    * a class that has extended this class.
    *
    * @var string
    */
    protected $className;
    
    public $methodName;
     
    /**
    * The name of the controller that holds the view (used by $this->fetchView
    * when retrieving the view). Default value is $this->className.
    *
    * @var string
    */
    public $controllerName;
    
    public $controllerMethod;
    
    /**
    * The requested url to this controller.
    *
    * @var string
    */
    public $selfUrl;
     
    /**
    * The data that a controller method has built up from models and other calcualtions.
    *
    * @var array The data from method calls.
    */
    public $data = array();
     
    /**
    * The name of the master view that has been requested. Typically this is
    * part of the master view's file name. ie. $this->masterView.'.master.tpl'
    *
    * @var string
    */
    public $masterView;
     
    /**
    * The name of the view that has been requested. Typically this is part of
    * the view's file name. ie. $this->view.'.php'
    *
    * @var string
    */
    public $view;
     
     /**
    * An enumerator indicating how the response should be delivered to the
    * output buffer. Options are:
    *    APPWARE_DELIVERY_METHOD_XHTML: page contents are delivered as normal.
    *    APPWARE_DELIVERY_METHOD_JSON: page contents and extra information delivered as JSON.
    * The default value is APPWARE_DELIVERY_METHOD_XHTML.
    *
    * @var string
    */
    protected $_deliveryMethod;
     
     /**
    * An enumerator indicating what should be delivered to the screen. Options
    * are:
    *    APPWARE_DELIVERY_TYPE_ALL: The master view and everything in the requested asset.
    *    APPWARE_DELIVERY_TYPE_ASSET: Everything in the requested asset.
    *    APPWARE_DELIVERY_TYPE_VIEW: Only the requested view.
    *    APPWARE_DELIVERY_TYPE_BOOL: Deliver only the success status (or error) of the request
    *    APPWARE_DELIVERY_TYPE_NONE: Deliver nothing
    * The default value is APPWARE_DELIVERY_TYPE_ALL.
    *
    * @var string
    */
    protected $_deliveryType;

    /**
    * @var array $field => $value pairs from the form in the $_POST or $_GET collection
    *    (depending on which method was specified for sending form data in $this->method).
    *    Populated & accessed by $this->formValues().
    *    Values can be retrieved with $this->getFormValue($fieldName).
    * @access private
    */
    public $_formValues;

    /**
    * If JSON is going to be delivered to the client (see the render method),
    * this property will hold the values being sent.
    *
    * @var array
    */
    protected $_json;

    public function __construct() {
        $this->className = get_class($this);
        $this->controllerName = strtolower(str_replace(array((new \ReflectionClass($this))->getNamespaceName(), '\\', static::app()->controllersClassSuffix()), '', $this->className));
        $this->masterView = $this->masterView();
        $this->methodName = '';
        $this->controllerMethod = strtolower($this->methodName);
        $this->selfUrl = static::app()->webRootPath() . $this->controllerName . DIRECTORY_SEPARATOR . $this->controllerMethod;
        $this->view = $this->view();
        $this->data = array();
        $this->_deliveryMethod = APPWARE_DELIVERY_METHOD_XHTML;
        $this->_deliveryType = APPWARE_DELIVERY_TYPE_ALL;
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

    /**
    * Returns the requested delivery type of the controller if $default is not
    * provided. Sets and returns the delivery type otherwise.
    *
    * @param string $default One of the APPWARE_DELIVERY_TYPE_* constants.
    * @return string
    */
    public function deliveryType($default = '') {
       if ($default)
          $this->_deliveryType = $default;

       return $this->_deliveryType;
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
   
    /**
    * Set data from a method call.
    *
    * @param string $key The key that identifies the data.
    * @param mixed $value The data.
    * @param mixed $addProperty Whether or not to also set the data as a property of this object.
    * @return mixed The $value that was set.
    */
    public function setData($key, $value = NULL) {
       if (is_array($key)) {
          $this->data = array_merge($this->data, $key);
          return $key;
       }
 
       $this->data[$key] = $value;
       return $value;
    }
   
    /**
    * Returns the requested delivery method of the controller if $default is not
    * provided. Sets and returns the delivery method otherwise.
    *
    * @param string $default One of the APPWARE_DELIVERY_METHOD_* constants.
    * @return string
    */
    public function deliveryMethod($default = '') {
       if ($default != '')
          $this->_deliveryMethod = $default;
 
       return $this->_deliveryMethod;
    }
   
    public function masterView($masterView = '') {
        // Define some default master views unless one was explicitly defined
        if ($masterView == '') {
            if ($this->masterView == '') {
                $this->masterView = 'default'; // Otherwise go with the default
            }
        } else $this->masterView = $masterView;
        
        return $this->masterView;
    }
    
    public function view($view = '') {
        // Define some default master views unless one was explicitly defined
        if ($view == '') {
            if ($this->view == '') {
                $this->view = $this->controllerMethod; // Otherwise go with the default
            }
        } else $this->view = $view;
        
        return $this->view;
    }
    
    public function request() {
        return static::app()->request();
    }
    
    /**
    * Defines & retrieves the view and master view. Renders all content within
    * them to the screen.
    *
    * @param string $view
    * @param string $controllerName
    * @param string $applicationFolder
    * @param string $assetName The name of the asset container that the content should be rendered in.
    * @todo $view, $controllerName, and $applicationFolder need correct variable types and descriptions.
    */
    public function render($view = '', $controllerName = false, $assetName = 'Content') {
        
        if ($this->deliveryMethod() == APPWARE_DELIVERY_METHOD_XHTML || $this->deliveryMethod() == APPWARE_DELIVERY_METHOD_CLI) {
            $view = $this->view($view);
            if ($controllerName === false)
                $controllerName = $this->controllerName;
            
            if ($this->deliveryType() == APPWARE_DELIVERY_TYPE_ALL) {
                $this->fetchMaster($view, $controllerName);
            } elseif ($this->deliveryType() == APPWARE_DELIVERY_TYPE_VIEW) {
                $this->fetchView($view, $controllerName);
            } elseif ($this->deliveryType() == APPWARE_DELIVERY_TYPE_ASSET) {
                $this->fetchAsset($view, $controllerName);
            } else {
                $errorControllerName = static::app()->errorControllerName;
                $errorControllerClass = static::app()->errorControllerClass;
                $errorNotFoundMethod = static::app()->errorNotFoundMethod;
                $errorErrorMethod = static::app()->errorErrorMethod;
                
                if ($controllerName == strtolower($errorControllerName) && $this->deliveryMethod() == APPWARE_DELIVERY_METHOD_CLI) {
                    $rootPath = static::app()->rootFolder();
                    $privatePath = static::app()->privateFolder();
                    
                    $e = $this->data('exception', new Exception("Unhandeled exception"));
                    
                    //Pay attention to sending it to stderr?
                    echo get_class($e) . ' : ' . $e->getMessage() . "\n";
                    echo str_replace(array($rootPath, $privatePath), array('', ''), $e->getTraceAsString()) . "\n";
                }
                //APPWARE_DELIVERY_TYPE_NONE ?
                //$content = "";
            }
        } elseif ($this->deliveryMethod() == APPWARE_DELIVERY_METHOD_JSON) {
            $this->fetchJson();
        } else {
             //APPWARE_DELIVERY_TYPE_NONE ?
             //$content = "";
        }
         
         //echo $content;
         //return $content;
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
    public function fetchMaster($view = '', $controllerName = false) {
       $viewPath = $this->fetchViewLocation($view, $controllerName);
       
       // Check to see if there is a handler for this particular extension.
       $viewHandler = static::app()->view();
       
       /*$viewContents = '';*/
       //ob_start();
       if($viewHandler !== NULL) {
          // Use the view handler to parse the view.
          $viewHandler->deliveryType($this->deliveryType());
          $viewHandler->deliveryMethod($this->deliveryMethod());
          
         $masterView = $this->masterView();
         $masterViewPath = $this->fetchMasterViewLocation($masterView);
         
         $view = $this->view();
         $viewPath = $this->fetchViewLocation($view);

         $viewHandler->masterView($masterViewPath);
         $viewHandler->view($viewPath);

         $viewHandler->render($viewPath, $this->data);
       } else {
         //Throw new ViewHandler Exception ...
       }
       /*$viewContents = ob_get_clean();
       
       return $viewContents;*/
        //ob_get_clean();
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
    public function fetchView($view = '', $controllerName = false) {
       $viewPath = $this->fetchViewLocation($view, $controllerName);
       
       // Check to see if there is a handler for this particular extension.
       $viewHandler = static::app()->view();
       
       /*$viewContents = '';*/
        //ob_start();
       if($viewHandler !== NULL) {
          // Use the view handler to parse the view.
          $viewHandler->deliveryType($this->deliveryType());
          $viewHandler->deliveryMethod($this->deliveryMethod());
          
          $masterView = $this->masterView();
          $masterViewPath = $this->fetchMasterViewLocation($masterView);
          
          $viewHandler->masterView($masterViewPath);
          $viewHandler->renderView($viewPath, $this->data);
       } else {
         //Throw new ViewHandler Exception ...
       }
       /*$viewContents = ob_get_clean();
       
       return $viewContents;*/
        //ob_get_clean();
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
    public function fetchAsset($view = '', $controllerName = false) {
       $viewPath = $this->fetchViewLocation($view, $controllerName);
       
       // Check to see if there is a handler for this particular extension.
       $viewHandler = static::app()->view();
       
       /*$viewContents = '';*/
        //ob_start();
       if($viewHandler !== NULL) {
          // Use the view handler to parse the view.
          $viewHandler->deliveryType($this->deliveryType());
          $viewHandler->deliveryMethod($this->deliveryMethod());
          
          $masterView = $this->masterView();
          $masterViewPath = $this->fetchMasterViewLocation($masterView);
          
          $viewHandler->masterView($masterViewPath);
          $viewHandler->renderView($viewPath, $this->data);
       } else {
         //Throw new ViewHandler Exception ...
       }
       /*$viewContents = ob_get_clean();
       
       return $viewContents;*/
        //ob_get_clean();
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
    public function fetchViewLocation($view = '', $controllerName = false) {
         // Accept an explicitly defined view, or look to the method that was called on this controller
         if ($view == '')
            $view = $this->view;
   
         if ($view == '')
            $view = $this->controllerMethod;
   
         if ($controllerName === false)
            $controllerName = $this->controllerName;
          
          
         $viewsFolder = static::app()->viewsFolder();
         
         $viewPath = $viewsFolder . DIRECTORY_SEPARATOR . $controllerName . DIRECTORY_SEPARATOR . $view . '.php';
         
         return $viewPath;
       
       /*if ($viewPath === false && $throwError) {
          throw NotFoundException('View');
 //         trigger_error(ErrorMessage("Could not find a '$view' view for the '$controllerName' controller.", $this->className, 'fetchViewLocation'), E_USER_ERROR);
       }
 
         return $viewPath;*/
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
    public function fetchMasterViewLocation($masterView = '') {
         // Accept an explicitly defined view, or look to the method that was called on this controller
         if ($masterView == '')
            $masterView = $this->masterView;
   
         if ($masterView == '')
            $masterView = 'default';
          
         $viewsFolder = static::app()->viewsFolder();
         
         $masterViewPath = $viewsFolder . DIRECTORY_SEPARATOR . $masterView . '.master.php';
         
         return $masterViewPath;
       
       /*if ($masterViewPath === false && $throwError) {
          throw NotFoundException('View');
 //         trigger_error(ErrorMessage("Could not find a '$masterView' master view", $this->className, 'fetchMasterViewLocation'), E_USER_ERROR);
       }
 
         return $viewPath;*/
    }
    
    /**
    * Fetches the contents of a property data and returns it depending on the format, encoding and compression.
    */
    public function fetchJson() {
        try {
            echo $this->parseOutput($this->encodeJson($this->_json));
        } catch (Exception $e) {
            $output = new stdClass;
            $error = array();
            $error['code'] = $e->getCode();
            $error['message'] = $e->getMessage();
            $output->error = $error;
            $outputResult = $this->encodeJson($output);
            try {
                echo $this->parseOutput($outputResult);
            } catch (Exception $e) {
                echo $outputResult;
            }
        }
        //exit();
    }
    
    /**
    * Do any handling before to output the result.
    */
    protected function parseOutput($outputResult = false) {
       return $outputResult;
    }
    
    /**
    * Do any handling before to output the result.
    */
    protected function encodeJson($output = false) {
       return json_encode($output);
    }
    
    /**
    * Adds the specified module to the specified asset target. 
    * 
    * If no asset name is defined, it will use the asset name defined by the 
    * module's Name method.
    *
    * @param mixed $module A module or the name of a module to add to the page.
    * @param string $assetName The asset name to store the module in.
    * @param string $assetTarget The asset target to render the module in.
    */
    public function addModule($module, $assetName = '', $assetTarget = '') {
       $assetModule = $module;
       
       if (!is_object($assetModule)) {
         if (is_string($assetModule)) {
             if (property_exists($this, $module) && is_object($this->$module)) {
                $assetModule = $this->$module;
             } else {
                 try {
                     $modulesNameSpace = static::app()->modulesNameSpace();
                     $modulesClassSuffix = static::app()->modulesClassSuffix();

                     $module = sprintf('%s\\%s%s', $modulesNameSpace, $module, $modulesClassSuffix);
                     
                     $assetModule = new $module();
                 } catch(Exception $e) {
                     $assetModule = $module;//Add it anyhow as a string asset
                 }
             }
         } else $assetModule = $assetName;
       }
       
       if (is_object($assetModule)) {
          $assetName = ($assetName == '' ? $assetModule->name() : $assetName);
          $assetTarget = ($assetTarget == '' ? $assetModule->assetTarget() : $assetTarget);
          $viewHandler = static::app()->view();
          $viewHandler->addAsset($assetName, $assetModule, $assetTarget);
       } else {
         if (empty($assetName)) {
             $viewHandler = static::app()->view();
             $viewHandler->addAsset($assetName, $assetModule);
         }
       }
    }
    
    public function getModule($assetName = '') {
         if (!empty($assetName)) {
             $viewHandler = static::app()->view();
             $assetModule = $viewHandler->getAsset($assetName);
             if ($assetModule instanceof AppWareModule) return $assetModule;
             else return false;
         } else return false;
    }
    
    /**
     * If JSON is going to be sent to the client, this method allows you to add
     * extra values to the JSON array.
     *
     * @param string $key The name of the array key to add.
     * @param string $value The value to be added. If empty, nothing will be added.
     */
    public function setJson($key, $value = '') {
       $this->_json[$key] = $value;
    }
    
   /**
    * If this object has a "Head" object as a property, this will set it's Title value.
    * 
    * @param string $title The value to pass to $this->head->title().
    * @return string
    */
   public function title($title = NULL) {
      if (!is_null($title))
         $this->setData('title', $title);
      return $this->data('title');
   }
   
    /**
     * Examines the sent form variable collection to see if any data was sent
     * via the form back to the server. Returns true on if anything is found.
     *
     * @return boolean
     */
    public function isPostBack() {
       $posts = array_diff_key(static::request()->paramsPost()->all(), array("rq" => "rq", "Page" => "Page", "DeliveryType" => "DeliveryType", "DeliveryMethod" => "DeliveryMethod", "DeliveryMasterView" => "DeliveryMasterView", ));
       if (count($posts) > 0) {
            return true;
       } elseif (static::request()->method('post')) {
            return true;
       } else {
            $gets = array_diff_key(static::request()->paramsGet()->all(), array("rq" => "rq", "Page" => "Page", "DeliveryType" => "DeliveryType", "DeliveryMethod" => "DeliveryMethod", "DeliveryMasterView" => "DeliveryMasterView", ));
            if (count($gets) > 0) {
                return true;
            } else return false;
       }
    }
    
    /**
     * If the form has been posted back, this method return an associative
     * array of $fieldName => $value pairs which were sent in the form.
     *
     * Note: these values are typically used by the model and it's validation object.
     *
     * @return array
     */
    public function formValues($newValue = NULL) {
       if($newValue !== NULL) {
          $this->_formValues = $newValue;
          return $this->_formValues;
       }
       
       $magicQuotes = get_magic_quotes_gpc();
 
       if (!is_array($this->_formValues)) {
          /*$tableName = $this->inputPrefix;
          if(strlen($tableName) > 0)
             $tableName .= '/';*/
          $tableName = '';
          $tableNameLength = strlen($tableName);
          
          $this->_formValues = array();
          /*$collection = $this->method == 'get' ? $_GET : $_POST;
          $inputType = $this->method == 'get' ? INPUT_GET : INPUT_POST;*/
          
          $collection = static::request()->params();
          
          //TODO unset user's cookie key
          unset($collection['rq'], $collection['PHPSESSID'], $collection['Page'], $collection['DeliveryType'], $collection['DeliveryMethod'], $collection['DeliveryMasterView']);
          
          foreach($collection as $field => $value) {
             $fieldName = substr($field, $tableNameLength);
             //$fieldName = $this->_unescapeString($fieldName);
             if (substr($field, 0, $tableNameLength) == $tableName) {
                if ($magicQuotes) {
                   if (is_array($value)) {
                      foreach ($value as $i => $v) {
                         $value[$i] = stripcslashes($v);
                      }
                   } else {
                      $value = stripcslashes($value);
                   }
                }
                
                $this->_formValues[$fieldName] = $value;
             }
          }
          
          // Make sure that unchecked checkboxes get added to the collection
          if (array_key_exists('Checkboxes', $collection)) {
             $uncheckedCheckboxes = $collection['Checkboxes'];
             if (is_array($uncheckedCheckboxes) === true) {
                $count = count($uncheckedCheckboxes);
                for($i = 0; $i < $count; ++$i) {
                   if (!array_key_exists($uncheckedCheckboxes[$i], $this->_formValues))
                      $this->_formValues[$uncheckedCheckboxes[$i]] = false;
                }
             }
          }
          
          // Make sure that Date inputs (where the day, month, and year are
          // separated into their own dropdowns on-screen) get added to the
          // collection as a single field as well...
          if (array_key_exists(
             'DateFields', $collection) === true) {
             $dateFields = $collection['DateFields'];
             if (is_array($dateFields) === true) {
                $count = count($dateFields);
                for($i = 0; $i < $count; ++$i) {
                    if (array_key_exists(
                            $dateFields[$i],
                            $this->_formValues) ===
                        false
                    ) // Saving dates in the format: YYYY-MM-DD
                    {
                        $year = data_get(
                            $this->_formValues,
                            $dateFields[$i] .
                            '_Year',
                            0);
                        $month = data_get(
                            $this->_formValues,
                            $dateFields[$i] .
                            '_Month',
                            0);
                        $day = data_get(
                            $this->_formValues,
                            $dateFields[$i] .
                            '_Day',
                            0);
                        $month = str_pad(
                            $month,
                            2,
                            '0',
                            STR_PAD_LEFT);
                        $day = str_pad(
                            $day,
                            2,
                            '0',
                            STR_PAD_LEFT);
                        $this->_formValues[$dateFields[$i]] = $year .
                            '-' .
                            $month .
                            '-' .
                            $day;
                    }
                }
             }
          }
       }
       
       // print_r($this->_formValues);
       return $this->_formValues;
    }
 
    /**
     * Gets the value associated with $fieldName from the sent form fields.
     * If $fieldName isn't found in the form, it returns $default.
     *
     * @param string $fieldName The name of the field to get the value of.
     * @param mixed $default The default value to return if $fieldName isn't found.
     * @return mixed
     */
    public function getFormValue($fieldName, $default = '') {
       return $this->getValue($fieldName, $this->formValues(), $default);
    }
 
    /**
    * Return the value from an associative array or an object.
    *
    * @param string $key The key or property name of the value.
    * @param mixed $collection The array or object to search.
    * @param mixed $default The value to return if the key does not exist.
    * @param bool $remove Whether or not to remove the item from the collection.
    * @return mixed The value from the array or object.
    */
    private function getValue($key, &$collection, $default = false, $remove = false) {
        $result = $default;
        if(is_array($collection) && array_key_exists($key, $collection)) {
            $result = $collection[$key];
            if($remove)
               unset($collection[$key]);
        } elseif(is_object($collection) && property_exists($collection, $key)) {
            $result = $collection->$key;
            if($remove)
               unset($collection->$key);
        }
			
      return $result;
    }
   
    /** Convert various forms of querystring limit/offset, page, limit/range to database limit/offset
    *
    * @param string $offsetOrPage The page query in one of the following formats:
    *  - p<x>: Get page x.
    *  - <x>-<y>: This is a range viewing records x through y.
    *  - <x>lim<n>: This is a limit/offset pair.
    *  - <x>: This is a limit where offset is given in the next parameter.
    * @param int $limitOrPageSize The page size or limit.
    * @return array
    */
    protected function offsetLimit($offsetOrPage = '', $limitOrPageSize = '') {
       $limitOrPageSize = is_numeric($limitOrPageSize) ? $limitOrPageSize : 50;
 
       if (is_numeric($offsetOrPage)) {
          $offset = $offsetOrPage;
          $limit = $limitOrPageSize;
       } elseif (preg_match('/p(\d+)/i', $offsetOrPage, $matches)) {
          $page = $matches[1];
          $offset = $limitOrPageSize * ($page - 1);
          $limit = $limitOrPageSize;
       } elseif (preg_match('/(\d+)-(\d+)/', $offsetOrPage, $matches)) {
          $offset = $matches[1] - 1;
          $limit = $matches[2] - $matches[1] + 1;
       } elseif (preg_match('/(\d+)lim(\d*)/i', $offsetOrPage, $matches)) {
          $offset = $matches[1];
          $limit = $matches[2];
          if (!is_numeric($limit))
             $limit = $limitOrPageSize;
       } elseif (preg_match('/(\d+)lin(\d*)/i', $offsetOrPage, $matches)) {
          $offset = $matches[1] - 1;
          $limit = $matches[2];
          if (!is_numeric($limit))
             $limit = $limitOrPageSize;
       } else {
          $offset = 0;
          $limit = $limitOrPageSize;
       }
 
       if ($offset < 0)
          $offset = 0;
       if ($limit < 0)
          $limit = 50;
 
       return array($offset, $limit);
    }
}
