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

namespace AppWare\Core\Views;

use \AppWare\Core\App as AppWare;
use \AppWare\Core\Modules\Base as AppWareModule;
use \AppWare\Core\Exceptions\ViewNotFound as AppWareViewNotFoundException;

class Base
{
  
    public $masterView;
    
    public $view;
    
    public $data;
    
    public $assets;
    
    public $targetAssets;
    
    public $_deliveryType;
    
    public $_deliveryMethod;

    public $_outputBufferLevel;
    
    public function __construct() {
        $this->masterView = 'default';
        $this->view = 'index';
        $this->data = array();
        $this->_deliveryMethod = APPWARE_DELIVERY_METHOD_XHTML;
        $this->_deliveryType = APPWARE_DELIVERY_TYPE_ALL;
        $this->assets = array();
        $this->targetAssets = array();
        $this->_outputBufferLevel = ob_get_level();
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
        // Define some default views unless one was explicitly defined
        if ($view == '') {
            if ($this->view == '') {
                $this->view = 'index'; // Otherwise go with the default
            }
        } else $this->view = $view;
        
        return $this->view;
    }
    
    public function data($data = false) {
        // Define some default data unless one was explicitly defined
        if ($data !== false) {
            if (is_array($data)) {
                $this->data = $data;
            }
        }
        
        return $this->data;
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

    public function outputBufferLevel($default = -1) {
        if (is_numeric($default) && $default >= 0)
            $this->_outputBufferLevel = $default;

        return $this->_outputBufferLevel;
    }
    
    public function addAsset($key = '', $content, $target = '') {
        if (!empty($key)) {
            if (!isset($this->assets[$key])) {
                $this->assets[$key] = $content;
                if (!empty($target) && $target != $key) {
                    $this->targetAssets[$target][$key] = $key;
                }
            }
        }
    }
    
    public function editAsset($key = '', $content, $target = '') {
        if (!empty($key)) {
            if (isset($this->assets[$key])) {
                $this->assets[$key] = $content;
                if (!empty($target) && $target != $key) {
                    $this->targetAssets[$target][$key] = $key;
                }
            }
        }
    }
    
    public function removeAsset($key = '') {
        if (!empty($key)) {
            if (isset($this->assets[$key])) {
                unset($this->assets[$key]);//Should remove the Asset from any potential target or leave the Render deal with it
            }
        }
    }
    
    public function getAsset($key = '') {
        if (!empty($key)) {
            if (isset($this->assets[$key])) {
                return $this->assets[$key];
            } else return false;
        } else return false;
    }
    
    public function renderMaster($masterView = '') {
        
        $masterView = $this->masterView($masterView);
        $view = $this->view();
        $data = $this->data();

        $rootPath = static::app()->rootFolder();
        $privatePath = static::app()->privateFolder();

        if (!file_exists($masterView)) {
            throw new AppWareViewNotFoundException(str_replace(array($rootPath, $privatePath), array('', ''), $masterView) . ' ' . 'MasterView not found', 404);
        }

        if (!file_exists($view)) {
            throw new AppWareViewNotFoundException(str_replace(array($rootPath, $privatePath), array('', ''), $view) . ' ' . 'View not found', 404);
        }
        
        ob_start();
        
        if (static::app()->service()) {
            static::app()->service()->layout($masterView);
            static::app()->service()->render($view, $data);
        } else echo "";
        
        $masterViewContents = ob_get_contents();
        ob_end_clean();

        static::app()->response()->body($masterViewContents);//Should check with DeliveryType or keep it enforcing the response body whenver directly called.
    }
    
    public function renderView($view = '', $data = false) {
        //Content is not added to Assets and sent to output right away
        
        $view = $this->view($view);
        $data = $this->data($data);

        $rootPath = static::app()->rootFolder();
        $privatePath = static::app()->privateFolder();

        if (!file_exists($view)) {
            throw new AppWareViewNotFoundException(str_replace(array($rootPath, $privatePath), array('', ''), $view) . ' ' . 'View not found', 404);
        }
        
        ob_start();
        
        if (static::app()->service()) {
            static::app()->service()->partial($view, $data);
        } else echo "";
        
        $viewContents = ob_get_contents();
        ob_end_clean();
        
        if ($this->deliveryType() == APPWARE_DELIVERY_TYPE_VIEW) {
            static::app()->response()->body($viewContents);
        } elseif($this->deliveryType() == APPWARE_DELIVERY_TYPE_ALL) {
            echo $viewContents;
        } else {
            //Do nothing ...
        }
    }
    
    public function assetContent($view = '', $data = false) {
        $originalView = $this->view();
        
        $view = $this->view($view);
        $data = $this->data($data);

        $rootPath = static::app()->rootFolder();
        $privatePath = static::app()->privateFolder();

        if (!file_exists($view)) {
            throw new AppWareViewNotFoundException(str_replace(array($rootPath, $privatePath), array('', ''), $view) . ' ' . 'View not found', 404);
        }

        ob_start();
        
        if (static::app()->service()) {
            static::app()->service()->partial($view, $data);
        } else echo "";
        
        $assetContents = ob_get_contents();
        ob_end_clean();
        
        $this->view($originalView);
        
        echo $assetContents;
    }
    
    public function renderAsset($assetName = '') {
        
        ob_start();
        if (isset($this->targetAssets[$assetName])) {
            foreach ($this->targetAssets[$assetName] as $targetAssetName) {
                if (isset($this->assets[$targetAssetName])) {
                    $this->renderAsset($targetAssetName);
                }
            }
        } else {
            if (!empty($assetName)) {
                if (isset($this->assets[$assetName])) {
                    $asset = $this->assets[$assetName];
                    if(is_string($asset)) {
                       echo $asset;
                    } elseif ($asset instanceof AppWareModule) {
                       $asset->render();
                    }
                }
            }
        }
        
        $assetContents = ob_get_contents();
        ob_end_clean();
        
        echo $assetContents;
    }
    
    public function render($view = '', $data = false) {
        
        $view = $this->view($view);
        $data = $this->data($data);
        
        $masterView = $this->masterView();
        
        if ($masterView === NULL) $this->renderView($view, $data);
        else $this->renderMaster();
    }
}