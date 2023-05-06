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

use \AppWare\Core\App as AppWare;
use \PHPMailer;

class Mailer extends PHPMailer
{
    /**
     * @var Mailer
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @param boolean
     * @return Mailer
     */
    public function __construct($throwExceptions = false) {

        $throwExceptions = static::app()->config()->get('Mailer.ThrowExceptions', $throwExceptions);

        parent::__construct($throwExceptions);

        $this->isSMTP();                                      // Set mailer to use SMTP
        $this->Host = static::app()->config()->get('Mailer.SMTPHost', '');  // Specify main and backup server
        $this->Port = static::app()->config()->get('Mailer.SMTPPort', 25);
        $this->Helo = static::app()->config()->get('Mailer.SMTPHelo', '');  // Specify main and backup server
        $this->SMTPAuth = true;                               // Enable SMTP authentication
        $this->Username = static::app()->config()->get('Mailer.SMTPUser', '');                            // SMTP username
        $this->Password = static::app()->config()->get('Mailer.SMTPPassword', '');                           // SMTP password
        $this->SMTPSecure = static::app()->config()->get('Mailer.SMTPSecure', 'tls');                            // Enable encryption, 'ssl' also accepted
        $this->SMTPDebug = static::app()->config()->get('Mailer.SMTPDebug', 0);

        $this->From = static::app()->config()->get('Mailer.From', '');
        $this->FromName = static::app()->config()->get('Mailer.FromName', '');

        $this->CharSet = static::app()->config()->get('Mailer.CharSet', 'utf-8');

        $this->WordWrap = 50;                                 // Set word wrap to 50 characters
        $this->isHTML(false);                                  // Set email format to HTML
    }

    /**
     * Get back the Singleton instance
     *
     * @param boolean
     * @return Mailer
     */
    public static function getInstance($throwExceptions = false) {
        if(is_null(static::$_instance)) {
            static::$_instance = new static($throwExceptions);
        }
        return static::$_instance;
    }

    /**
     * Mail send helper
     *
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @param boolean
     * @return Mailer
     */
    public function sendMail($subject = '', $toAddress = '', $toName = '', $body = '', $fromAddress = '', $fromName = '', $isHTML = false) {
        if ($subject == '' || $toAddress == '' || $body == '')
            return false;

        $this->addAddress($toAddress, $toName);
        $this->Subject = $subject;
        $this->Body    = $body;
        if ($fromAddress != '')
            $this->From = $fromAddress;
        if ($fromName != '')
            $this->FromName = $fromName;

        if ($isHTML)
            $this->isHTML(true);
        else
            $this->isHTML(false);

        return $this->send();
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
