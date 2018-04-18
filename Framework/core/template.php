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

namespace ClickBlocks\Core;

use ClickBlocks\Cache;

/**
 * This class is templator using php as template language.
 *
 * @version 1.0.0
 * @package cb.core
 */
class Template implements \ArrayAccess
{  
  /**
   * Unique cache identifier of template.
   *
   * @var string $cacheID
   * @access public
   */
  public $cacheID = null;
  
  /**
   * Cache expiration time of template.
   *
   * @var integer $cacheExpire
   * @access public
   */
  public $cacheExpire = 0;
  
  /**
   * Name of the cache group.
   *
   * @var string $cacheGroup
   * @access public
   */
  public $cacheGroup = 'templates';
  
  /**
   * An instance of ClickBlocks\Cache\Cache class.
   *
   * @var ClickBlocks\Cache\Cache $cache
   * @access protected
   */
  protected $cache = null;

  /**
   * Template variables.
   *
   * @var array $vars
   * @access protected
   */
  protected $vars = [];
  
  /**
   * Template string or path to a template file. 
   *
   * @var string $template
   * @access protected
   */
  protected $template = null;
  
  /**
   * Global template variables.
   *
   * @var array $globals
   * @access protedted
   * @static
   */
  protected static $globals = [];
  
  /**
   * Returns array of global template variables.
   *
   * @return array
   * @access public
   * @static
   */
  public static function getGlobals()
  {
    return self::$globals;
  }

  /**
   * Sets global template variables.
   *
   * @param array $globals - new global template variables.
   * @param boolean $merge - determines whether new variables are merged with existing variables.
   * @access public
   * @static
   */
  public static function setGlobals(array $globals, $merge = false)
  {
    if (!$merge) self::$globals = $globals;
    else self::$globals = array_merge(self::$globals, $globals);
  }
  
  /**
   * Constructor.
   *
   * @param string $template - template string or path to a template file.
   * @param integer $expire - template cache life time in seconds.
   * @param string $cacheID - unique cache identifier of template.
   * @param ClickBlocks\Cache\Cache - an instance of caching class.
   * @access public
   */
  public function __construct($template = null, $expire = 0, $cacheID = null, Cache\Cache $cache = null)
  {
    $this->template = $template;
    $this->cacheExpire = (int)$expire;
    if ($this->cacheExpire > 0) 
    {
      $this->setCache($cache ?: \CB::getInstance()->getCache());
      $this->cacheID = $cacheID;
    }
  }
  
  /**
   * Returns an instance of caching class.
   *
   * @return ClickBlocks\Cache\Cache
   * @access public
   */
  public function getCache()
  {
    if ($this->cache === null) $this->cache = \CB::getInstance()->getCache();
    return $this->cache;
  }
  
  /**
   * Sets an instance of caching class.
   *
   * @param ClickBlocks\Cache\Cache $cache
   * @access public
   */
  public function setCache(Cache\Cache $cache)
  {
    $this->cache = $cache;
  }
  
  /**
   * Checks whether or not a template cache lifetime is expired.
   *
   * @return boolean
   * @access public
   */
  public function isExpired()
  {
    if ((int)$this->cacheExpire <= 0) return true;
    return $this->getCache()->isExpired($this->getCacheID());
  }

  /**
   * Returns array of template variables.
   *
   * @return array
   * @access public
   */
  public function getVars()
  {
    return $this->vars;
  }

  /**
   * Sets template variables.
   *
   * @param array $vars
   * @param boolean $merge - determines whether new variables are merged with existing variables.
   * @access public
   */
  public function setVars(array $vars, $merge = false)
  {
    if (!$merge) $this->vars = $vars;
    else $this->vars = array_merge($this->vars, $vars);
  }
  
  /**
   * Returns template string.
   *
   * @return string
   * @access public
   */
  public function getTemplate()
  {
    return $this->template;
  }
  
  /**
   * Sets template.
   *
   * @param string $template - template string or path to a template file.
   * @access public
   */
  public function setTemplate($template)
  {
    $this->template = $template;
  }

  /**
   * Sets new value of a global template variable.
   *
   * @param string $name - global variable name.
   * @param mixed $value - global variable value. 
   * @access public
   */
  public function offsetSet($name, $value)
  {
    self::$globals[$name] = $value;
  }

  /**
   * Checks whether or not a global template variable with the same name exists.
   *
   * @param string $name - global variable name.
   * @return boolean
   * @access public
   */
  public function offsetExists($name)
  {
    return isset(self::$globals[$name]);
  }

  /**
   * Deletes a global template variable.
   *
   * @param string $key - global variable name.
   * @access public
   */
  public function offsetUnset($name)
  {
    unset(self::$globals[$name]);
  }

  /**
   * Gets value of a global template variable.
   *
   * @param string $name - global variable name.
   * @return mixed
   * @access public
   */
  public function &offsetGet($name)
  {
    if (!isset(self::$globals[$name])) self::$globals[$name] = null;
    return self::$globals[$name];
  }

  /**
   * Sets value of a template variable.
   *
   * @param string $name - variable name.
   * @param mixed $value - variable value.
   * @access public
   */
  public function __set($name, $value)
  {
    $this->vars[$name] = $value;
  }

  /**
   * Returns value of a template variable.
   *
   * @param string $name - variable name.
   * @return mixed
   * @access public
   */
  public function &__get($name)
  {
    if (!isset($this->vars[$name])) $this->vars[$name] = null;
    return $this->vars[$name];
  }

  /**
   * Checks whether or not a template variable exist.
   *
   * @param string $name - variable name.
   * @return boolean
   * @access public
   */
  public function __isset($name)
  {
    return isset($this->vars[$name]);
  }

  /**
   * Deletes a template variable.
   *
   * @param string $name
   * @access public
   */
  public function __unset($name)
  {
    unset($this->vars[$name]);
  }

  /**
   * Returns a rendered template.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    $render = function($tpl)
    {
      if (is_file($tpl->getTemplate())) 
      {
        ${'(_._)'} = $tpl; unset($tpl);
        extract(Template::getGlobals());
        extract(${'(_._)'}->getVars());
        ob_start();
        require(${'(_._)'}->getTemplate());
        return ob_get_clean();
      }
      return \CB::exe($tpl->getTemplate(), array_merge(Template::getGlobals(), $tpl->getVars()));
    };
    if ((int)$this->cacheExpire <= 0) return $render($this);
    $hash = $this->getCacheID();
    $cache = $this->getCache();
    if ($cache->isExpired($hash))
    {
      $tmp = [];
      foreach (array_merge(self::$globals, $this->vars) as $name => $value) 
      {
        if ($value instanceof Template)
        {
          $tmp[$name] = $this->vars[$name];
          $this->vars[$name] = $hash . '<?php $' . $name . ';?>' . $hash;
        }
      }
      $content = $render($this); $parts = [];
      foreach (explode($hash, $content) as $part)
      {
        $name = substr($part, 7, -3);
        if (isset($this->vars[$name])) $parts[] = [$name, true];
        else $parts[] = [$part, false];
      }
      foreach ($tmp as $name => $tpl) $this->vars[$name] = $tpl;
      $cache->set($hash, $parts, $this->cacheExpire, $this->cacheGroup);
    }
    else
    {
      $parts = $cache->get($hash);
    }
    $content = ''; $tmp = [];
    foreach ($parts as $part)
    {
      if ($part[1] === false) $content .= $part[0];
      else
      {
        $part = $part[0];
        if (isset($tmp[$part])) $content .= $tmp[$part];
        else 
        {
          $tmp[$part] = $this->vars[$part]->render();
          $content .= $tmp[$part];
        }
      } 
    }
    return $content;
  }

  /**
   * Push a rendered template to a browser.
   *
   * @access public
   */
  public function show()
  {
    echo $this->render();
  }

  /**
   * Converts an instance of this class to a string.
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch (\Exception $e)
    {
      \CB::exception($e);
    }
  }
  
  /**
   * Returns template cache ID.
   *
   * @return mixed 
   * @access protected
   */
  protected function getCacheID()
  {
    return $this->cacheID !== null ? $this->cacheID : md5($this->template);
  }
}