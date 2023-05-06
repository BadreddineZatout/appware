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
use \AppWare\Core\Modules\Pager as AppWarePagerModule;
use \AppWare\Core\Controllers\Base as AppWareController;
use \Exception;

/**
 * Builds a pager control related to a dataset.
 */
class Pager extends Base
{

   /**
    * The id applied to the div tag that contains the pager.
    */
   public $clientID;

   /**
    * @var Pager
    */
   protected static $_currentPager;

   /**
    * The name of the stylesheet class to be applied to the pager. Default is
    * 'Pager';
    */
   public $cssClass;

   /**
    * The number of records in the current page.
    * @var int
    */
   public $currentRecords = false;

   /**
    * The default number of records per page.
    * @var int
    */
   public static $defaultPageSize = 20;

   /**
    * Translation code to be used for "Next Page" link.
    */
   public $moreCode;

   /**
    * If there are no pages to page through, this string will be returned in
    * place of the pager. Default is an empty string.
    */
   public $pagerEmpty;

   /**
    * The xhtml code that should wrap around the page link.
    *  ie. '<div %1$s>%2$s</div>';
    * where %1$s represents id and class attributes and %2$s represents the page link.
    */
   public $wrapper;

   /**
    * The xhtml code that should wrap around the pager link.
    *  ie. '<div %1$s>%2$s</div>';
    * where %1$s represents id and class attributes (if defined by
    * $this->clientID and $this->cssClass) and %2$s represents the pager link.
    */
   public $pageWrapper;

   public $pageWrapperActiveCssClass;

   /**
    * Translation code to be used for "less" link.
    */
   public $lessCode;

   /**
    * The number of records being displayed on a single page of data. Default
    * is 30.
    */
   public $limit;

   /**
    * The total number of records in the dataset.
    */
   public $totalRecords;

   /**
    * The string to contain the record offset. ie. /controller/action/%s/
    */
   public $url;

   /**
    *
    * @var string
    */
   public $urlCallBack;

   /**
    * The first record of the current page (the dataset offset).
    */
   public $offset;

   public $range;

   public $separator;

   /**
    * The last offset of the current page. (ie. Offset to LastOffset of TotalRecords)
    */
   private $_lastOffset;

   /**
    * Certain properties are required to be defined before the pager can build
    * itself. Once they are created, this property is set to true so they are
    * not needlessly recreated.
    */
   private $_propertiesDefined;

   /**
    * A boolean value indicating if the total number of records is known or
    * not. Retrieving this number can be a costly database query, so sometimes
    * it is not retrieved and simple "next/previous" links are displayed
    * instead. Default is false, meaning that the simple pager is displayed.
    */
   private $_totalled;

   /**
    * The current page record.
    */
   public $record;

   /**
    * HTML code to prepend.
    */
   public $htmlBefore;

   public function __construct() {
      $this->clientID = 'Pager';
      $this->cssClass = 'Pager';
      $this->range = 3;
      $this->separator = '&#8230;';
      $this->offset = 0;
      $this->limit = static::$defaultPageSize;
      $this->totalRecords = false;
      $this->wrapper = '<div class="PagerWrap"><div %1$s>%2$s</div></div>';
      $this->pageWrapper = '<div class="PageWrap"><div %1$s>%2$s</div></div>';
      $this->pagerEmpty = '';
      $this->moreCode = '»';
      $this->lessCode = '«';
      $this->url = '/controller/action/$s/';
      $this->_propertiesDefined = false;
      $this->_totalled = false;
      $this->_lastOffset = 0;
      parent::__construct();
   }

   function assetTarget() {
      return false;
   }

   /**
    * Define all required parameters to create the Pager and PagerDetails.
    */
   public function configure($offset, $limit, $totalRecords, $url, $forceConfigure = false, $extraOptions = array()) {
      if ($this->_propertiesDefined === false || $forceConfigure === true) {
         if (is_array($url)) {
            if (count($url) == 1)
               $this->urlCallBack = array_pop($url);
            else
               $this->urlCallBack = $url;
         } else {
            $this->url = $url;
         }

         $this->offset = $offset;
         $this->limit = is_numeric($limit) && $limit > 0 ? $limit : $this->limit;
         $this->totalRecords = $totalRecords;
         $this->_lastOffset = $this->offset + $this->limit;
         $this->_totalled = ($this->totalRecords >= $this->limit) ? false : true;
         if ($this->_lastOffset > $this->totalRecords)
            $this->_lastOffset = $this->totalRecords;

         $this->wrapper = $this->getValue('Wrapper', $extraOptions, $this->wrapper);
         $this->pageWrapper = $this->getValue('PageWrapper', $extraOptions, $this->pageWrapper);
         $this->pageWrapperActiveCssClass = $this->getValue('PageWrapperActiveCssClass', $extraOptions, $this->pageWrapperActiveCssClass);

         $this->range = $this->getValue('Range', $extraOptions, 3);
         $this->separator = $this->getValue('Separator', $extraOptions, '&#8230;');

         $this->_propertiesDefined = true;

      }
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
    * Gets the controller this pager is for.
    * @return AppWareController.
    */
   public function controller() {
      return static::app()->controller();
   }

   public static function current($value = NULL) {
      if ($value !== NULL) {
         static::$_currentPager = $value;
      } elseif (static::$_currentPager == NULL) {
         static::$_currentPager = new AppWarePagerModule();
      }

      return static::$_currentPager;
   }

   // Builds a string with information about the page list's current position (ie. "1 to 15 of 56").
   // Returns the built string.
   public function details($formatString = '') {
      if ($this->_propertiesDefined === false)
         return 'You must configure the pager with $pager->configure() before retrieving the pager details.';

      $details = false;
      if ($this->totalRecords > 0) {
         if ($formatString != '') {
            $details = sprintf($formatString, $this->offset + 1, $this->_lastOffset, $this->totalRecords);
         } else if ($this->_totalled === true) {
            $details = sprintf('%1$s to %2$s of %3$s', $this->offset + 1, $this->_lastOffset, $this->totalRecords);
         } else {
            $details = sprintf('%1$s to %2$s', $this->offset, $this->_lastOffset);
         }
      }
      return $details;
   }

   /**
    * Whether or not this is the first page of the pager.
    *
    * @return bool True if this is the first page.
    */
   public function firstPage() {
      $result = $this->offset == 0;
      return $result;
   }

   public static function formatUrl($url, $page, $limit = '') {
      // Check for new style page.
      if (strpos($url, '{Page}') !== false)
         return str_replace(array('{Page}', '{Size}'), array($page, $limit), $url);
      else
         return sprintf($url, $page, $limit);
   }

   /**
    * Whether or not this is the last page of the pager.
    *
    * @return bool True if this is the last page.
    */
   public function lastPage() {
      return $this->offset + $this->limit >= $this->totalRecords;
   }

   public static function rel($page, $currentPage) {
      if ($page == $currentPage - 1)
         return 'prev';
      elseif ($page == $currentPage + 1)
         return 'next';

      return NULL;
   }

   public function pageUrl($page) {
      if ($this->urlCallBack) {
         return call_user_func($this->urlCallBack, $this->record, $page);
      } else {
         $paramsGet = array_diff_key($this->controller()->request()->paramsGet()->all(), array("rq" => "rq", "Page" => "Page", ));
         $pageUrl =  static::formatUrl($this->url, 'p'.$page);

         return $pageUrl . ((count($paramsGet) > 0)?(strpos($pageUrl, '?')?'&':'?') . http_build_query($paramsGet):'');
      }
   }

   /**
    * Builds page navigation links.
    *
    * @param string $type Type of link to return: 'more' or 'less'.
    * @throws Exception
    * @return string HTML page navigation links.
    */
   public function toString($type = 'more') {
      if ($this->_propertiesDefined === false)
         throw new Exception('You must configure the pager with $pager->configure() before retrieving the pager.');

      // Urls with url-encoded characters will break sprintf, so we need to convert them for backwards compatibility.
      $this->url = str_replace(array('%1$s', '%2$s', '%s'), '{Page}', $this->url);

      if ($this->totalRecords === false) {
         return $this->toStringPrevNext($type);
      }

      $this->cssClass = implode(' ', array($this->cssClass, 'NumberedPager'));

      $pageCount = ceil($this->totalRecords / $this->limit);
      $currentPage = ceil($this->offset / $this->limit) + 1;

      // Show $range pages on either side of current
      $range = $this->range;

      // String to represent skipped pages
      $separator = $this->separator;

      // Show current page plus $range pages on either side
      $pagesToDisplay = ($range * 2) + 1;
      if ($pagesToDisplay + 2 >= $pageCount) {
         // Don't display an ellipses if the page count is only a little bigger that the number of pages.
         $pagesToDisplay = $pageCount;
      }

      $pager = '';
      $previousText = $this->lessCode;
      $nextText = $this->moreCode;

      // Previous
      if ($currentPage == 1) {
         $pager = $this->pageWrap('<span class="Previous">'.$previousText.'</span>');
      } else {
         $pager .= $this->pageWrap($this->anchor($previousText, $this->pageUrl($currentPage - 1), 'Previous', array('rel' => 'prev')));
      }

      // Build Pager based on number of pages (Examples assume $range = 3)
      if ($pageCount <= 1) {
         // Don't build anything

      } else if ($pageCount <= $pagesToDisplay) {
         // We don't need elipsis (ie. 1 2 3 4 5 6 7)
         for ($i = 1; $i <= $pageCount ; $i++) {
            $pager .= $this->pageWrap($this->anchor($i, $this->pageUrl($i), $this->_getCssClass($i, $currentPage), array('rel' => static::rel($i, $currentPage))), ($i==$currentPage?($this->pageWrapperActiveCssClass?array("class" => $this->pageWrapperActiveCssClass):array()):array()));
         }

      } else if ($currentPage + $range <= $pagesToDisplay + 1) { // +1 prevents 1 ... 2
         // We're on a page that is before the first elipsis (ex: 1 2 3 4 5 6 7 ... 81)
         for ($i = 1; $i <= $pagesToDisplay; $i++) {
            $pageParam = 'p'.$i;
            $pager .= $this->pageWrap($this->anchor($i, $this->pageUrl($i), $this->_getCssClass($i, $currentPage), array('rel' => static::rel($i, $currentPage))), ($i==$currentPage?($this->pageWrapperActiveCssClass?array("class" => $this->pageWrapperActiveCssClass):array()):array()));
         }

         $pager .= $this->pageWrap('<span class="Ellipsis">'.$separator.'</span>');
         $pager .= $this->pageWrap($this->anchor($pageCount, $this->pageUrl($pageCount)));

      } else if ($currentPage + $range >= $pageCount - 1) { // -1 prevents 80 ... 81
         // We're on a page that is after the last elipsis (ex: 1 ... 75 76 77 78 79 80 81)
         $pager .= $this->pageWrap($this->anchor(1, $this->pageUrl(1)));
         $pager .= $this->pageWrap('<span class="Ellipsis">'.$separator.'</span>');

         for ($i = $pageCount - ($pagesToDisplay - 1); $i <= $pageCount; $i++) {
            $pageParam = 'p'.$i;
            $pager .= $this->pageWrap($this->anchor($i, $this->pageUrl($i), $this->_getCssClass($i, $currentPage), array('rel' => static::rel($i, $currentPage))), ($i==$currentPage?($this->pageWrapperActiveCssClass?array("class" => $this->pageWrapperActiveCssClass):array()):array()));
         }

      } else {
         // We're between the two elipsises (ex: 1 ... 4 5 6 7 8 9 10 ... 81)
         $pager .= $this->pageWrap($this->anchor(1, $this->pageUrl(1)));
         $pager .= $this->pageWrap('<span class="Ellipsis">'.$separator.'</span>');

         for ($i = $currentPage - $range; $i <= $currentPage + $range; $i++) {
            $pageParam = 'p'.$i;
            $pager .= $this->pageWrap($this->anchor($i, $this->pageUrl($i), $this->_getCssClass($i, $currentPage), array('rel' => static::rel($i, $currentPage))), ($i==$currentPage?($this->pageWrapperActiveCssClass?array("class" => $this->pageWrapperActiveCssClass):array()):array()));
         }

         $pager .= $this->pageWrap('<span class="Ellipsis">'.$separator.'</span>');
         $pager .= $this->pageWrap($this->anchor($pageCount, $this->pageUrl($pageCount)));
      }

      // Next
      if ($currentPage == $pageCount) {
         $pager .= $this->pageWrap('<span class="Next">'.$nextText.'</span>');
      } else {
         $pageParam = 'p'.($currentPage + 1);
         $pager .= $this->pageWrap($this->anchor($nextText, $this->pageUrl($currentPage + 1), 'Next', array('rel' => 'next'))); // extra sprintf parameter in case old url style is set
      }
      if ($pageCount <= 1)
         $pager = '';

      $clientID = $this->clientID;
      $clientID = $type == 'more' ? $clientID.'After' : $clientID.'Before';

      if (isset($this->htmlBefore)) {
         $pager = $this->htmlBefore.$pager;
      }

      return $pager == '' ? '' : sprintf($this->wrapper, $this->attribute(array('id' => $clientID, 'class' => $this->cssClass)), $pager);
   }

   public function toStringPrevNext($type = 'more') {
      $this->cssClass = implode(' ', array($this->cssClass, 'PrevNextPager'));
      $currentPage = $this->pageNumber($this->offset, $this->limit);

      $pager = '';

      if ($currentPage > 1) {
         $pageParam = 'p'.($currentPage - 1);
         $pager .= $this->pageWrap($this->anchor('Previous', $this->pageUrl($currentPage - 1), 'Previous', array('rel' => 'prev')));
      }

      $hasNext = true;
      if ($this->currentRecords !== false && $this->currentRecords < $this->limit)
         $hasNext = false;

      if ($hasNext) {
         $pageParam = 'p'.($currentPage + 1);
         $pager = implode(' ', array($pager, $this->pageWrap($this->anchor('Next', $this->pageUrl($currentPage + 1), 'Next', array('rel' => 'next')))));
      }

      $clientID = $this->clientID;
      $clientID = $type == 'more' ? $clientID.'After' : $clientID.'Before';

      if (isset($this->htmlBefore)) {
         $pager = $this->htmlBefore.$pager;
      }

      return $pager == '' ? '' : sprintf($this->wrapper, $this->attribute(array('id' => $clientID, 'class' => $this->cssClass)), $pager);
   }

   public static function write($options = array()) {
      static $writeCount = 0;

      if (!static::$_currentPager) {
         static::$_currentPager = new AppWarePagerModule();
      }
      $pager = static::$_currentPager;

      $pager->wrapper = $pager->getValue('Wrapper', $options, $pager->wrapper);
      $pager->pageWrapper = $pager->getValue('PageWrapper', $options, $pager->pageWrapper);
      $pager->moreCode = $pager->getValue('MoreCode', $options, $pager->moreCode);
      $pager->lessCode = $pager->getValue('LessCode', $options, $pager->lessCode);

      $pager->clientID = $pager->getValue('ClientID', $options, $pager->clientID);

      $pager->limit = $pager->getValue('Limit', $options, $pager->controller()->data('_Limit', $pager->limit));
      $pager->htmlBefore = $pager->getValue('HtmlBefore', $options, $pager->getValue('HtmlBefore', $pager, ''));
      $pager->currentRecords = $pager->getValue('CurrentRecords', $options, $pager->controller()->data('_CurrentRecords', $pager->currentRecords));

      // Try and figure out the offset based on the parameters coming in to the controller.
      if (!$pager->offset) {
         $page = $pager->controller()->request()->param('Page', false);
         if (!$page) {
            $page = 'p1';
            foreach($pager->controller()->request()->params() as $arg) {
               if (preg_match('`p\d+`', $arg)) {
                  $page = $arg;
                  break;
               }
            }
         }
         list($offset, $limit) = $pager->offsetLimit($page, $pager->limit);
         $totalRecords = $pager->getValue('RecordCount', $options, $pager->controller()->data('RecordCount', false));

         $get = array_diff_key($pager->controller()->request()->paramsGet()->all(), array("rq" => "rq", "Page" => "Page", "DeliveryType" => "DeliveryType", "DeliveryMethod" => "DeliveryMethod", "DeliveryMasterView" => "DeliveryMasterView", ));
         $url = $pager->getValue('Url', $options, $pager->controller()->selfUrl.'?Page={Page}&'.http_build_query($get));

         $pager->configure($offset, $limit, $totalRecords, $url);
      }

      echo $pager->toString($writeCount > 0 ? 'more' : 'less');
      $writeCount++;

//      list($offset, $limit) = offsetLimit(GetValue, 20);
//		$pager->configure(
//			$offset,
//			$limit,
//			$totalAddons,
//			"/settings/addons/$section?Page={Page}"
//		);
//		$Sender->setData('_Pager', $pager);
   }

   private function _getCssClass($thisPage, $highlightPage) {
      return $thisPage == $highlightPage ? 'Highlight' : false;
   }

   /**
    * Are there more pages after the current one?
    */
   public function hasMorePages() {
      return $this->totalRecords > $this->offset + $this->limit;
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
   protected function getValue($key, &$collection, $default = false, $remove = false) {
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

   /**
    * Builds and returns an anchor tag.
    */
   protected function anchor($text, $destination = '', $cssClass = '', $attributes = array()) {
      if (!is_array($cssClass) && $cssClass != '')
         $cssClass = array('class' => $cssClass);

      if ($destination == '')
         return $text;

      if (!is_array($attributes))
         $attributes = array();

      return '<a href="'.htmlspecialchars($destination, ENT_COMPAT, 'UTF-8').'"'.$this->attribute($cssClass).$this->attribute($attributes).'>'.$text.'</a>';
   }

   /**
    * Builds and returns a page tag wrapper.
    */
   protected function pageWrap($pageTag = '', $attributes = array()) {

      if (!is_array($attributes))
         $attributes = array();

      return $pageTag == '' ? '' : sprintf($this->pageWrapper, $this->attribute($attributes), $pageTag);
   }

   /**
    * Takes an attribute (or array of attributes) and formats them in
    * attribute="value" format.
    */
   protected function attribute($name) {
      $return = '';
      if (!is_array($name)) {
         $name = array($name => '');
      }
      foreach ($name as $attribute => $val) {
         if ($val != '' && $attribute != 'Standard') {
            $return .= ' '.$attribute.'="'.htmlspecialchars($val, ENT_COMPAT, 'UTF-8').'"';
         }
      }
      return $return;
   }

   /** Get the page number from a database offset and limit.
    *
    * @param int $offset The database offset, starting at zero.
    * @param int $limit The database limit, otherwise known as the page size.
    * @param bool|string $urlParam Whether or not the result should be formatted as a url parameter, suitable for OffsetLimit.
    *  - bool: true means yes, false means no.
    *  - string: The prefix for the page number.
    * @param bool $first Whether or not to return the page number if it is the first page.
    * @return string
    */
   protected function pageNumber($offset, $limit, $urlParam = false, $first = true) {
      $result = floor($offset / $limit) + 1;

      if ($urlParam !== false && !$first && $result == 1)
         $result = '';
      elseif ($urlParam === true)
         $result = 'p'.$result;
      elseif (is_string($urlParam))
         $result = $urlParam.$result;

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

?>