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
 * The class intended for caching of different data using the APC extension. 
 *
 * @version 1.0.0
 * @package cb.cache
 */
class APC extends Cache
{
  /**
   * Error message templates.
   */
  const ERR_CACHE_APC_1 = 'APC cache extension is not enabled. Set option apc.enabled to 1 in php.ini';
  const ERR_CACHE_APC_2 = 'APC cache extension is not enabled cli. Set option apc.enable_cli to 1 in php.ini';
  
  /**
   * Checks whether the current type of cache is available or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isAvailable()
  {
    return extension_loaded('apc');
  }

  /**
   * Constructor.
   * 
   * @access public
   */
  public function __construct()
  {
    parent::__construct();
    if (!ini_get('apc.enabled')) throw new Core\Exception($this, 'ERR_CACHE_APC_1');
    if (php_sapi_name() === 'cli' && !ini_get('apc.enable_cli')) throw new Core\Exception($this, 'ERR_CACHE_APC_2');
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
    apc_store($key, $content, $expire);
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
    return apc_fetch($key);
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
    return !apc_exists($key);
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {
    apc_delete($key);
  }
   
  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    apc_clear_cache('user');
  }
}