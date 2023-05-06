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

namespace AppWare\Core\Legacy;

use \AppWare\Core\App as AppWareApp;
use \AppWare\Core\Config as AppWareConfig;
use \AppWare\Core\Views\Base as AppWareView;
use \AppWare\Core\Auth as AppWareAuth;
use \AppWare\Core\Mailer as AppWareMailer;
use \AppWare\Core\Form\Validation as AppWareValidation;
use \AppWare\Core\Logger as AppWareLogger;
use \AppWare\Core\ErrorHandlers\Base as AppWareErrorHandler;
use \AppWare\Core\Form\Form as AppWareForm;
use \AppWare\Core\Database\Manager as AppWareDatabaseManager;
use \AppWare\Core\Controllers\Base as AppWareController;
use \Klein;
use \FireLogger;

/**
 * AppWare
 * @package AppWare
 * @author  Ramzi HABIB
 * @since   2.0.0
 */
class App extends AppWareApp
{

    public function Start() {
        return parent::start();
    }

    static public function ThrowErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView) {
        parent::throwErrorPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
    }

    static public function ThrowNotFoundPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView) {
        parent::throwNotFoundPage($e, $deliveryType, $deliveryMethod, $deliveryMasterView);
    }

    /**
     * Config singleton
     *
     * @param void
     * @return AppWareConfig
     */
    static public function Config() {
        return parent::config();
    }

    /**
     * The controller at hand
     *
     * @param void
     * @return AppWareController
     */
    static public function Controller($controller = null) {
        return parent::controller($controller);
    }

    /**
     * Router singleton
     *
     * @param void
     * @return Klein\Klein
     */
    static public function Router() {
        return parent::router();
    }

    /**
     * Force the request query
     *
     * @param string $query
     * @return string
     */
    static public function RequestQuery($query = null) {
        return parent::requestQuery($query);
    }

    /**
     * Router singleton request
     *
     * @param void
     * @return Klein\Request
     */
    static public function Request() {
        return parent::request();
    }

    /**
     * Router singleton response
     *
     * @param void
     * @return Klein\Response
     */
    static public function Response() {
        return parent::response();
    }

    /**
     * Router singleton service provider
     *
     * @param void
     * @return Klein\ServiceProvider
     */
    static public function Service() {
        return parent::service();
    }

    /**
     * View handler singleton
     *
     * @param void
     * @return AppWareView
     */
    static public function View() {
        return parent::view();
    }

    /**
     * Old database singleton. Replaced with the Capsule instead
     *
     * @param void
     * @return AppWareDatabaseManager
     */
    static public function Db() {
        return parent::db();
    }

    /**
     * Auth manager singleton
     *
     * @param void
     * @return AppWareAuth
     */
    static public function Auth() {
        return parent::auth();
    }

    /**
     * Mailer singleton
     *
     * @param void
     * @return AppWareMailer
     */
    static public function Mailer() {
        return parent::mailer();
    }

    /**
     * Model validation singleton
     *
     * @param void
     * @return AppWareValidation
     */
    static public function Validation() {
        return parent::validation();
    }

    /**
     * FireLogger singleton
     *
     * @param void
     * @return FireLogger
     */
    static public function FireLogger() {
        return parent::fireLogger();
    }

    /**
     * Logger singleton
     *
     * @param void
     * @return AppWareLogger
     */
    static public function Logger() {
        return parent::logger();
    }

    /**
     * Error handler singleton
     *
     * @param void
     * @return AppWareErrorHandler
     */
    static public function ErrorHandler() {
        return parent::errorHandler();
    }

    /**
     * Form handler singleton
     *
     * @param void
     * @return AppWareForm
     */
    static public function Form() {
        return parent::form();
    }

    /**
     * Capsule database manager singleton
     *
     * @param void
     * @return AppWareDatabaseManager
     */
    static public function DatabaseManager() {
        return parent::databaseManager();
    }

    public function Dispatch($args = null) {
        return parent::dispatch($args);
    }

    static public function RootFolder() {
        return parent::rootFolder();
    }

    static public function PrivateFolder() {
        return parent::privateFolder();
    }

    static public function TemporaryFolder() {
        return parent::temporaryFolder();
    }

    static public function WebFolder() {
        return parent::webFolder();
    }

    static public function WebRoot($webroot = '') {
        return parent::webRoot($webroot);
    }

    static public function WebRootPath() {
        return parent::webRootPath();
    }

    static public function WebRootUrl($url) {
        return parent::webRootUrl($url);
    }

    static public function WebUrl($url) {
        return parent::webUrl($url);
    }

    static public function WebPath() {
        return parent::webPath();
    }

    static public function WebMediasPath() {
        return parent::webMediasPath();
    }

    static public function WebMediasUrl($url) {
        return parent::webMediasUrl($url);
    }

    static public function LibFolder() {
        return parent::libFolder();
    }

    static public function ConfigFolder() {
        return parent::configFolder();
    }

    static public function CacheFolder() {
        return parent::cacheFolder();
    }

    static public function PublicFolder() {
        return parent::publicFolder();
    }

    static public function AssetsFolder() {
        return parent::assetsFolder();
    }

    static public function MediasFolder() {
        return parent::mediasFolder();
    }

    static public function MediasRootFolder() {
        return parent::mediasRootFolder();
    }

    static public function ViewsFolder() {
        return parent::viewsFolder();
    }

    static public function Redirect($destination = false, $statusCode = null) {
        parent::redirect($destination, $statusCode);
    }

    static public function NoAuthRedirect() {
        parent::noAuthRedirect();
    }

    static public function SEOLink($link) {
        $chars = array("¥" => "Y", "µ" => "u", "&AACUTE;" => "A", "&AGRAVE;" => "A", "À" => "A", "Ä" => "A","Å" => "A","Ä" => "A","Ã" => "A","Â" => "A","Æ" => "A",
            "&ACIRC;" => "A", "&ATILDE;" => "A", "&AUML;" => "A", "&ARING;" => "A",
            "&AElig;" => "A", "&CCEDIL;" => "C", "&EGRAVE;" => "E", "&EACUTE;" => "E", "É" => "E", "Ê" => "E","É" => "E","Ë" => "E","È" => "E","Ç" => "C",
            "&ECIRC;" => "E", "&EUML;" => "E", "&IUML;" => "I", "&IACUTE;" => "I","Ï" => "I","Î" => "I","Í" => "I",
            "&ICIRC;" => "I", "&IGRAVE;" => "I", "&ETH;" => "D", "&NTILDE;" => "N","Ñ" => "N","Ò" => "O","Ó" => "O","Ô" => "O","Õ" => "O","Ö" => "O","Ð" => "D",
            "&OGRAVE;" => "O", "&OACUTE;" => "O", "&OCIRC;" => "O", "&OTILDE;" => "O",
            "&OUML;" => "O", "&OSLASH;" => "O", "&UACUTE;" => "U", "&UGRAVE;" => "U","Û" => "U","Ú" => "U","Ü" => "U","Ù" => "U","ý" => "y","ß" => "B",
            "&UCIRC;" => "U", "&UACUTE;" => "U", "&YACUTE;" => "Y", "&sZLIG;" => "s", "Ý" => "Y", "?" => "Z", "?" => "z",
            "&aGRAVE;" => "a", "&aACUTE;" => "a", "&aCIRC;" => "a", "&aTILDE;" => "a",
            "&aUML;" => "a", "&aRING;" => "a", "&aELIG;" => "a", "&cCEDIL;" => "c",
            "&eACUTE;" => "e", "&eCIRC;" => "e", "&eUML;" => "e", "?" => "g", "?" => "G", "?" => "S", "?" => "s", "?" => "i", "?" => "E", "?" => "e",
            "&iGRAVE;" => "i", "&iACUTE;" => "i", "&iCIRC;" => "i", "&iUML;" => "i",
            "&eTH;" => "o", "&nTILDE;" => "n", "&oACUTE;" => "o", "&oGRAVE;" => "o",
            "&oCIRC;" => "o", "&oTILDE;" => "o", "&oUML;" => "o", "&oSLASH;" => "o",
            "&uGRAVE;" => "u", "&uACUTE;" => "u", "&uACUTE;" => "u", "&uUML;" => "u",
            "à" => "A", "ê" => "E", "ù" => "U", "ô" => "O", "ó" => "O", "á" => "A",
            "ë" => "E", "ú" => "U", "õ" => "O", "â" => "A", "ç" => "C", "û" => "U",
            "ö" => "O", "ã" => "A", "ì" => "I", "ü" => "U", "ø" => "O", "ä" => "A",
            "í" => "I", "ÿ" => "Y", "è" => "E", "å" => "A", "î" => "I", "ñ" => "N",
            "é" => "E", "ò" => "O", "ï" => "I", "&yACUTE;" => "y", "&yUML;" => "y",
            " " => "-", "/" => "-", "+" => "-", "=" => "-", "." => "-", "'" => "", "" => "", "%" => "", "{" => "", "}" => "", "~" => "", "\\" => "", "^" => "", "¯" => "", "¡" => "", "¢" => "c", "´" => "", "º" => "", "&quot;" => "", "" => "-", "?" => "-", "	" => "-",
            "&" => "-", "\"" => "", ";" => "", "," => "", "*" => "-", "$" => "S", "?" => "", "!" => "", "%" => "", ">" => "", "<" => "",
            "(" => "", ")" => "", "[" => "", "]" => "", "#" => "", "@" => "A", "" => "E", "°" => "",
            "²" => "", "|" => "-", ":" => "-", "æ" => "ae", "¿" => ""
        , "?" => "", "" => "-", "" => "f", "" => "", "" => "", "" => "", "" => "", "" => "", "" => "", " " => "-", "`" => ""
        , "" => "", "" => "oe", "" => "", "" => "", "" => "", "" => "", "" => "", "" => "-", "~" => ""
        , "" => "tm", "" => "", "" => "oe", "¡" => "", "£" => "L", "¤" => "", "¥" => "Y", "¦" => "-", "§" => "s"
        , "¨" => "-", "©" => "c", "ª" => "a", "«" => "", "¬" => "", "®" => "", "¯" => "-", "±" => "", "²" => ""
        , "³" => "", "´" => "", "µ" => "u", "¶" => "p", "·" => "", "¸" => "", "¹" => "", "Ø" => "O", "»" => ""
        , "ð" => "a", "×" => "x", "¼" => "", "÷" => "", "Þ" => "P", "½" => "", "þ" => "", "ß" => "s", "¾" => "", "?" => "", "?" => "oe", "Á" => "A", "?" => "", "?" => "", "?" => "", "?" => "", "?" => "OE", "?" => "-", "?" => "", "?" => "", "?" => "", "?" => "s", "?" => "S", "?" => "Y");
        $link = str_replace("\xC2\xA0", " ", $link);
        $link = str_replace('\xE2\x80\x99','',$link);
        $link = strtr("$link", $chars);
        $link = trim($link);
        $link = trim($link,'-');
        $link = trim($link);
        $link = trim($link,'-');
        $link = urlencode($link);
        $link = str_replace('%E2%80%99','',$link);
        $link = str_replace('%C2%A0','-',$link);
        $link = str_replace('%81','-',$link);
        $link = str_replace('%8D','-',$link);
        $link = str_replace('%8F','-',$link);
        $link = str_replace('%9D','-',$link);
        $link = str_replace('%90','-',$link);
        $link = str_replace('%AD','-',$link);
        $link = str_replace('%C2','A',$link);
        $link = str_replace("--","-",$link);
        $link = str_replace("--","-",$link);
        $link = str_replace("--","-",$link);
        $link = str_replace("--","-",$link);
        $link = str_replace("--","-",$link);
        $link = substr($link, 0, 100);
        if ($link == '') $link = '-';
        return strtolower($link);
    }
}