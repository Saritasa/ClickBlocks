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

namespace ClickBlocks\Cache;

use ClickBlocks\Core;

/**
 * The class is intended for caching of different data using PHP sessions.
 * You can use this type of cache for testing caching in your applications.
 *
 * @version 1.0.0
 * @package cb.cache
 */
class Session extends Cache
{
  /**
   * Checks whether the current type of cache is available or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isAvailable()
  {
    return session_id() != '';
  } 

  /**
   * Conserves some data identified by a key into cache.
   *
   * @param string $key - a data key.
   * @param mixed $content - some data.
   * @param integer $expire - cache lifetime (in seconds).
   * @param string $group - group of a data key.
   * @access public
   */  
  public function set($key, $content, $expire, $group = null)
  {
    $expire = abs((int)$expire);
    $_SESSION['__CACHE__'][$key] = [serialize($content), $expire + time()];
    $this->saveKeyToVault($key, $expire, $group);
  }

  /**
   * Returns some data previously conserved in cache.
   *
   * @param string $key - a data key.
   * @return mixed
   * @access public
   */
  public function get($key)
  {                    
    if (isset($_SESSION['__CACHE__'][$key])) return unserialize($_SESSION['__CACHE__'][$key][0]);
  }

  /**
   * Checks whether cache lifetime is expired or not.
   *
   * @param string $key - a data key.
   * @return boolean
   * @access public
   */
  public function isExpired($key)
  {                      
    return empty($_SESSION['__CACHE__'][$key]) || $_SESSION['__CACHE__'][$key][1] <= time();
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {             
    unset($_SESSION['__CACHE__'][$key]);
  }
  
  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    unset($_SESSION['__CACHE__']);
  }
   
  /**
   * Garbage collector that should be used for removing of expired cache data.
   *
   * @param float $probability - probability of garbage collector performing.
   * @access public
   */
  public function gc($probability = 100)
  {
    if ((float)$probability * 1000 < rand(0, 99999)) return;
    if (empty($_SESSION['__CACHE__']) || !is_array($_SESSION['__CACHE__'])) return;
    foreach ($_SESSION['__CACHE__'] as $key => $item) if ($item[1] < time()) $this->remove($key);
    $this->normalizeVault();
  }
}