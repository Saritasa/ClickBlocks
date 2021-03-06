<?php
/**
 * ClickBlocks.PHP v. 1.0
 *
 * Copyright (C) 2014  SARITASA LLC
 * http://www.saritasa.com
 *
 * This framework is free software. You can redistribute it and/or modify
 * it under the terms of either the current ClickBlocks.PHP License
 * viewable at theclickblocks.com) or the License that was distributed with
 * this file.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the ClickBlocks.PHP License
 * along with this program.
 *
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 */

namespace ClickBlocks\MVC;

use ClickBlocks\Core,
    ClickBlocks\Net,
    ClickBlocks\Web\POM;

/**
 * The base class of all classes that intended for rendering HTML and management of UI of web pages.
 * The Page class plays role of Controller in MVC model.
 *
 * @version 1.0.0
 * @package cb.mvc
 * @property-read Core\Template $tpl
 * @property-read POM\Body $body
 */
class Page
{
  // Error message templates.
  const ERR_PAGE_1 = 'Method [{var}] is not allowed to be invoked.';
  const ERR_PAGE_2 = 'Incorrect request to the page.';
  const ERR_PAGE_3 = 'Property [{var}] is undefined.';

  /**
   * Default cache of page classes.
   *
   * @var \ClickBlocks\Cache\Cache $cache
   * @access public
   * @static
   */
  public static $cache = null;

  /**
   * Default cache group.
   *
   * @var string $cacheGroup
   * @access public
   * @static
   */
  public static $cacheGroup = 'pages';

  /**
   * Default cache expire.
   *
   * @var integer $cacheExpire
   * @access public
   * @static
   */
  public static $cacheExpire = 0;

  /**
   * Contains a page object, whose workflow is performed.
   *
   * @var \ClickBlocks\MVC\Page $current
   * @access public
   * @static
   */
  public static $current = null;

  /**
   * Represents the view of a page.
   *
   * @var \ClickBlocks\Web\POM\View $view
   * @access public
   */
  public $view = null;

  /**
   * The URL for redirect if a page is not accessible.
   *
   * @var string
   * @access public
   */
  public $noAccessURL = null;

  /**
   * The URL for redirect if the user session is expired.
   *
   * @var string
   * @access public
   */
  public $noSessionURL = null;

  /**
   * The time (in seconds) of expiration cache of a page.
   *
   * @var integer
   * @access public
   */
  protected $expire = 0;

  /**
   * This list of regular expressions that restrict the area of permitted delegates.
   *
   * @var array $ajaxPermissions
   * @access protected
   */
  protected $ajaxPermissions = ['permitted' => ['/^ClickBlocks\\\\(MVC|Web\\\\POM).*$/i'],
                                'forbidden' => ['/^ClickBlocks\\\\Web\\\\POM\\\\[^\\\\]*\[\d*\]->(__set|__get|__unset|__isset|prop)$/i']];

  /**
   * The sequence of class methods that determines the class workflow.
   * The sequence should consist of two parts: for the first visit to the page (GET non-Ajax request) and for other visits.
   *
   * @var array $sequenceMethods
   * @access protected
   */
  protected $sequenceMethods = ['first' => ['parse', 'init', 'load', 'render', 'unload'],
                                'after' => ['assign', 'load', 'process', 'unload']];

  /**
   * The unique page identifier.
   *
   * @var string $UID
   * @access private
   */
  private $UID = null;

  /**
   * Cache object for storing HTML of the rendered page.
   *
   * @var \ClickBlocks\Cache\Cache $storage
   * @access private
   */
  private $storage = null;

  /**
   * The instance of Body control.
   *
   * @var \ClickBlocks\Web\POM\Body $body
   * @access private
   */
  private $body = null;

  /**
   * The instance of the Body template engine.
   *
   * @var \ClickBlocks\Core\Template $tpl
   * @access private
   */
  private $tpl = null;

  /**
   * Constructor. Creates unique identifier of the page based on page template, page class and site unique ID.
   * This UID can be used for caching of page rendering.
   *
   * @param string $template - template string or path to a template file.
   * @access public
   */
  public function __construct($template = null)
  {
    $this->UID = md5(get_class($this) . $template . \CB::getSiteUniqueID());
    $this->view = new POM\View($template);
    $this->storage = static::$cache ? static::$cache : \CB::getInstance()->getCache();
  }

  /**
   * Used for overloading the dynamic properties "body" and "tpl" that
   * represent the Body control and its template engine object respectively.
   *
   * @param string $param - the property name.
   * @return mixed
   * @access public
   */
  public function __get($param)
  {
    if ($param == 'body') return $this->body ? $this->body : $this->body = $this->view->get('body');
  	if ($param == 'tpl') return $this->tpl ? $this->tpl : $this->tpl = $this->__get('body')->tpl;
	  return new Core\Exception($this, 'ERR_PAGE_3', get_class($this) . '::$' . $param);
  }

  /**
   * Returns the unique page ID.
   *
   * @return string
   * @access public
   */
  public function getPageID()
  {
    return $this->UID;
  }

  /**
   * Sets the unique page ID.
   *
   * @param string $UID
   * @access public
   */
  public function setPageID($UID)
  {
    $this->UID = $UID;
  }

  /**
   * Returns the page cache object.
   *
   * @return \ClickBlocks\Cache\Cache
   * @access public
   */
  public function getCache()
  {
    return $this->storage;
  }

  /**
   * Returns FALSE if the page is not cached or its cache is expired. Otherwise, it returns TRUE.
   *
   * @return boolean
   * @access public
   */
  public function isExpired()
  {
    return ($this->expire ?: static::$cacheExpire) ? $this->storage->isExpired($this->UID) : true;
  }

  /**
   * Recovers page HTML from cache.
   * It returns NULL if the page is not cached or its cache is expired.
   *
   * @return string
   * @access public
   */
  public function restore()
  {
    return $this->storage->get($this->UID);
  }

  /**
   * Returns list of workflow methods.
   * if $first is TRUE, the list of methods for the first visit to the page is returned,
   * otherwise the method returns the list of methods for the next visits.
   *
   * @param boolean $first - determines type of sequence of methods.
   * @return array
   * @access public
   */
  public function getSequenceMethods($first = true)
  {
    return $this->sequenceMethods[$first ? 'first' : 'after'];
  }

  /**
   * This method is alias of method get() of class ClickBlocks\Web\POM\View and returns control object by its unique or logic ID.
   * If a control with such ID is not found, it returns FALSE.
   *
   * @param string $id - unique or logic control ID.
   * @param boolean $searchRecursively - determines whether to recursively search a control in all panels.
   * @param \ClickBlocks\Web\POM\Control $context - the panel control inside which the control searching is performed.
   * @return \ClickBlocks\Web\POM\Control|boolean
   * @access public
   */
  public function get($id, $searchRecursively = true)
  {
    return $this->view->get($id, $searchRecursively);
  }

  /**
   * Checks accessibility of the page.
   *
   * @return boolean
   * @access public
   */
  public function access()
  {
    return true;
  }

    /**
     * Parses the page template.
     *
     * @access public
     * @throws Core\Exception
     */
  public function parse()
  {
    $this->view->parse();
  }

  /**
   * Initializes the page view.
   * This method is performed once during the first visit to the page.
   *
   * @access public
   */
  public function init()
  {
    $this->view->invoke('init');
  }

  /**
   * Prepares the page view.
   * This method is executed each time you visit the page.
   *
   * @access public
   */
  public function load()
  {
    $this->view->invoke('load');
  }

  /**
   * Renders the page HTML.
   *
   * @access public
   */
  public function render()
  {
    $html = $this->view->render();
    if ($this->expire ?: static::$cacheExpire) $this->storage->set($this->UID, $html, $this->expire, static::$cacheGroup ?: 'pages');
    echo $html;
  }

    /**
     * Assigns the changes that received from the client side, to controls.
     *
     * @access public
     * @throws Core\Exception
     */
  public function assign()
  {
    $data = Net\Request::getInstance()->data;
    if (!isset($data['ajax-vs']) || !isset($data['ajax-key'])) throw new Core\Exception($this, 'ERR_PAGE_2');
    if (!is_array($data['ajax-vs'])) $data['ajax-vs'] = json_decode($data['ajax-vs'], true);
    if (!$this->view->assign($data['ajax-key'], isset($data['ajax-vs']['vs']) ? $data['ajax-vs']['vs'] : [], $data['ajax-vs']['ts']))
    {
      if ($this->noSessionURL) \CB::go($this->noSessionURL);
      \CB::reload();
    }
  }

  /**
   * Performs the Ajax request.
   *
   * @access public
   * @throws Core\Exception
   * @throws \ReflectionException
   */
  public function process()
  {
    $data = Net\Request::getInstance()->data;
    if (isset($data['ajax-method']))
    {
      $method = new Core\Delegate($data['ajax-method']);
      if (!$method->isPermitted($this->ajaxPermissions)) throw new Core\Exception($this, 'ERR_PAGE_1', $method);
      ob_start();
      $response = $method->call(empty($data['ajax-args']) ? [] : $data['ajax-args']);
      $output = trim(ob_get_contents());
      ob_end_clean();
      if (strlen($output)) $this->view->action('alert', $output);
    }
    $this->view->process($response);
  }

  /**
   * Completes the page workflow.
   * This method is executed each time you visit the page.
   *
   * @access public
   */
  public function unload()
  {
    $this->view->invoke('unload');
  }
}
