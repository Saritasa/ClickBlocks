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
    ClickBlocks\Cache;

/**
 * This class is designed for controlling of page classes.
 *
 * @version 1.0.0
 * @package cb.mvc
 */
class Controller
{
  // Default message template about site locking.
  const MSG_LOCKED_RESOURCE = 'The requested resource is currently locked.';
  
  /**
   * Cache object which used for locking/unlocking of the application.
   *
   * @var \ClickBlocks\Cache\Cache $cache
   * @access public
   * @static
   */
  public static $cache = null;
  
  /**
   * Routing map.
   *
   * @var array $map
   * @access protected
   */
  protected $map = [];
  
  /**
   * Array of the basic HTTP error handlers (processing 404 and 403 errors is only available).
   *
   * @var array $handlers
   * @access protected
   */
  protected $handlers = [];
  
  /**
   * Constructor. Checks whether the site is locked or not. If the site is locked the appropriate message (template) will be displayed.
   *
   * @param array $map - routing map.
   * @param \ClickBlocks\Cache\Cache $cache - cache object for storing locked mark of the site.
   * @access public
   */
  public function __construct(array $map = [], Cache\Cache $cache = null)
  {
    $cb = \CB::getInstance()['mvc'];
    if (!empty($cb['locked']))
    {
      $cache = $cache ?: (self::$cache instanceof Cache\Cache ? self::$cache : $cb->getCache());
      if (!empty($cb['unlockKey']) && (isset($_REQUEST[$cb['unlockKey']]) || !$cache->isExpired(\CB::getSiteUniqueID() . $cb['unlockKey'])))
      {
        if (isset($_REQUEST[$cb['unlockKey']])) $cache->set(\CB::getSiteUniqueID() . $cb['unlockKey'], true, isset($cb['unlockKeyExpire']) ? $cb['unlockKeyExpire'] : 108000);
      }
      else
      {
        $cb->getResponse()->stop(423, isset($cb['templateLock']) ? file_get_contents(\CB::dir($cb['templateLock'])) : self::MSG_LOCKED_RESOURCE);
      }
    }
    $this->map = $map;
  }

    /**
     * Sets HTTP error handler. Processing of 404th and 403rd errors is only available.
     *
     * @param integer $status - HTTP code error.
     * @param mixed $callback - a delegate.
     * @return self
     * @access public
     * @throws Core\Exception
     */
  public function setErrorHandler($status, $callback)
  {
    $this->handlers[$status] = new Core\Delegate($callback);
    return $this;
  }
  
  /**
   * Returns callback object that associated with HTTP error code or FALSE otherwise.
   *
   * @param integer $status - HTTP code error.
   * @return \ClickBlocks\Core\Delegate | boolean
   * @access public
   */
  public function getErrorHandler($status)
  {
    return isset($this->handlers[$status]) ? $this->handlers[$status] : false;
  }
 
 /**
   * Determines ClickBlocks\MVC\Page class by URL and consistently calls its methods.
   *
   * @param \ClickBlocks\MVC\Page $page
   * @param string | array $methods - HTTP request methods.
   * @param string | \ClickBlocks\Net\URL $url - the URL string to route.
   * @return mixed
   * @access public
   */
  public function execute(Page $page = null, $methods = null, $url = null)
  {
    $cb = \CB::getInstance();
    if ($page === null)
    {
      $router = $cb->getRouter();
      foreach ($this->map as $resource => $info)
      {
        foreach ($info as $httpMethods => $data)
        {
          if (isset($data['secure']))
          {
            $router->secure($resource, $data['secure'], $httpMethods)
                   ->component(empty($data['component']) ? Net\URL::ALL : $data['component']);
          }
          else if (isset($data['redirect']))
          {
            $router->redirect($resource, $data['redirect'], $httpMethods)
                   ->component(empty($data['component']) ? Net\URL::PATH : $data['component'])
                   ->validation(empty($data['validation']) ? array() : $data['validation']);
          }
          else
          {
            $router->bind($resource, $data['callback'], $httpMethods)
                   ->component(empty($data['component']) ? Net\URL::PATH : $data['component'])
                   ->validation(empty($data['validation']) ? array() : $data['validation'])
                   ->ignoreWrongDelegate(empty($data['ignoreWrongDelegate']) ? false : $data['ignoreWrongDelegate'])
                   ->coordinateParameterNames(empty($data['coordinateParameterNames']) ? false : $data['coordinateParameterNames']);
          }
        }
      }
      $res = $router->route($methods, $url);
      if ($res['success'] === false)
      {
        if (isset($this->handlers[404]))
        {
          $this->handlers[404]->call([$this]);
          $cb->getResponse()->stop(404);
        }
        $cb->getResponse()->stop(404, 'The requested page is not found.');
      }
      else
      {
        if (!($res['result'] instanceof Page)) return $res['result'];
        $page = $res['result'];
      }      
    }
    Page::$current = $page;
    if (!$page->access())
    {
      if (!empty($page->noAccessURL)) \CB::go($page->noAccessURL);
      else if (isset($this->handlers[403]))
      {
        $this->handlers[403]->call();
        $cb->getResponse()->stop(403);
      }
      $cb->getResponse()->stop(403, 'Access denied.');
    }
    if ($cb->getRequest()->method == 'GET' && !$cb->getRequest()->isAjax)
    {
      if (!$page->isExpired()) $cb->getResponse()->stop(200, $page->restore());
      foreach ($page->getSequenceMethods(true) as $method) $page->{$method}();
    }
    else
    {
      foreach ($page->getSequenceMethods(false) as $method) $page->{$method}();
    }
  }
}
