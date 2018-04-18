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
 * The class is intended for caching of different data using the memcached extension.
 *
 * @version 1.0.0
 * @package cb.cache
 */
class Memory extends Cache
{
  /**
   * Error message templates.
   */
  const ERR_CACHE_MEMORY_1 = 'It\'s impossible to use compression data in memcache because zlib extension has not loaded.';
  
  /**
   * Maximum size in bytes of caching data.
   */
  const MAX_BLOCK_SIZE = 1048576;

  /**
   * Determines whether the data will be compressed before placing in the cache.
   *
   * @var boolean $compress
   * @access public
   */
  public $compress = true;

  /**
   * The instance of \Memcache class.
   *
   * @var \Memcache $mem
   * @access private
   */
  private $mem = null;
  
  /**
   * Checks whether the current type of cache is available or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isAvailable()
  {
    return extension_loaded('memcache');
  }

  /**
   * Constructor.
   *
   * @param array $servers - host for a memcache connection.
   * @param boolean $compress - if value of this parameter is TRUE any data will be compressed before placing in a cache, otherwise data will not be compressed.
   * @access public
   */
  public function __construct(array $servers, $compress = true)
  {
    parent::__construct();
    $this->vaultLifeTime = 2592000; // 1 month
    $this->mem = new \Memcache();
    if (count($servers))
    {
      foreach ($servers as $server)
      {
        $this->mem->addServer(isset($server['host']) ? $server['host'] : '127.0.0.1',
                              isset($server['port']) ? $server['port'] : 11211,
                              isset($server['persistent']) ? $server['persistent'] : true,
                              isset($server['weight']) ? $server['weight'] : 1,
                              isset($server['timeout']) ? $server['timeout'] : 1,
                              isset($server['retryInterval']) ? $server['retryInterval'] : 15,
                              isset($server['status']) ? $server['status'] : true);
      }
    }
    else
    {
      $this->mem->addServer('127.0.0.1', 11211);
    }
    if ($compress && !extension_loaded('zlib')) throw new Core\Exception($this, 'ERR_CACHE_MEMORY_1');
    $this->compress = $compress ? MEMCACHE_COMPRESSED : 0;
  }

  /**
   * Gets the memcache object.
   *
   * @return \Memcache
   * @access public
   */
  public function getMemcache()
  {
    return $this->mem;
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
    $k = md5($key);
    $content = serialize($content);
    if (strlen($content) < self::MAX_BLOCK_SIZE) $this->mem->set($k, $content, $this->compress, $expire);
    else
    {
      foreach (str_split($content, self::MAX_BLOCK_SIZE) as $n => $part)
      {
        $this->mem->set($k . $n, $part, $this->compress, $expire);
      }
      $this->mem->set($k, array($k => $n), $this->compress, $expire);
    }
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
    $key = md5($key);
    $res = $this->mem->get($key);
    if (isset($res[$key]) && is_int($res[$key]))
    {
      for ($i = 0, $j = $res[$key], $res = ''; $i <= $j; $i++)
      {
        $res .= $this->mem->get($key . $i);
      }
    }
    return unserialize($res);
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
    return $this->mem->get(md5($key)) === false;
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {
    $k = md5($key);
    $res = $this->mem->get($k);
    if (isset($res[$k]) && is_int($res[$k]))
    {
      for ($i = 0, $j = $res[$k]; $i <= $j; $i++)
      {
        $this->mem->delete($k . $i);
      }
    }
    else $this->mem->delete($k);
  }

  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    $this->mem->flush();
  }
}