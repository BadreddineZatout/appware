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

class CommandLine extends Base
{
    
    public function __construct() {
        if (php_sapi_name() !== 'cli') exit();
	
	$cliGroup = static::app()->config()->get('AppWare.CommandLineInterfaceGroup', false);
	$cliUser = static::app()->config()->get('AppWare.CommandLineInterfaceUser', false);
	
	if ($cliGroup) {
	    $group = posix_getgrnam($cliGroup);
	    posix_setgid($group['gid']);
	}
	
	if ($cliUser) {
	    $user = posix_getpwnam($cliUser);
	    posix_setuid($user['uid']); 
	}
	
	parent::__construct();
	
        $this->_deliveryMethod = APPWARE_DELIVERY_METHOD_CLI;
        $this->_deliveryType = APPWARE_DELIVERY_TYPE_NONE;
    }
    
}