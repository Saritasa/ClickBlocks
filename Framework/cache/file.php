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
 * The class is intended for caching of different data using the file system.
 *
 * @version 1.0.0
 * @package cb.cache
 */
class File extends Cache
{  
  /**
   * Error message templates.
   */
  const ERR_CACHE_FILE_1 = 'Cache directory "[{var}]" is not writable.';

  /**
   * The directory in which cache files will be stored.
   *
   * @var string $dir
   * @access private   
   */
  private $dir = null;
   
  /**
   * Sets new directory for storing of cache files.
   * If this directory doesn't exist it will be created.
   *
   * @param string $path
   * @access public
   */
  public function setDirectory($path = null)
  {
    $dir = \CB::dir($path ?: 'cache');
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    if (!is_writable($dir) && !chmod($dir, 0775)) throw new Core\Exception($this, 'ERR_CACHE_FILE_1', $dir);
    $this->dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
  }
  
  /**
   * Returns the current cache directory.
   *
   * @return string
   * @access public
   */
  public function getDirectory()
  {
    return $this->dir;
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
    if (!$this->dir) $this->setDirectory();
    $expire = abs((int)$expire);
    $file = $this->dir . md5($key);
    file_put_contents($file, serialize($content), LOCK_EX);
    $enabled = \CB::isErrorHandlingEnabled();
    $level = \CB::errorHandling(false, E_ALL & ~E_WARNING);
    chmod($file, 0644);
    touch($file, $expire + time());
    \CB::errorHandling($enabled, $level);
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
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (file_exists($file)) 
    {
      $enabled = \CB::isErrorHandlingEnabled();
      $level = \CB::errorHandling(false, E_ALL & ~E_WARNING);
      while ('' === $content = file_get_contents($file)) usleep(100);
      \CB::errorHandling($enabled, $level);
      return $content === false ? null : unserialize($content);
    }
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
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (!file_exists($file)) return true;
    $enabled = \CB::isErrorHandlingEnabled();
    $level = \CB::errorHandling(false, E_ALL & ~E_WARNING);
    $flag = filemtime($file) <= time();
    \CB::errorHandling($enabled, $level);
    return $flag;
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {             
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (file_exists($file))
    {
      $enabled = \CB::isErrorHandlingEnabled();
      $level = \CB::errorHandling(false, E_ALL & ~E_WARNING);
      unlink($file);
      \CB::errorHandling($enabled, $level);
    }
  }
  
  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    if (!$this->dir) $this->setDirectory();
    if (strtolower(substr(PHP_OS, 0, 3)) == 'win') exec('del /Q /F ' . escapeshellarg($this->dir));
    else exec('find ' . escapeshellarg($this->dir) . ' -maxdepth 1 -type f -delete');
  }
   
  /**
   * Garbage collector that should be used for removing of expired cache data.
   *
   * @param float $probability - probability of garbage collector performing.
   * @access public
   */
  public function gc($probability = 100)
  {
    if (!$this->dir) $this->setDirectory();
    if ((float)$probability * 1000 < rand(0, 99999)) return;
    if (strtolower(substr(PHP_OS, 0, 3)) == 'win') exec('forfiles /P ' . escapeshellarg(rtrim($this->dir, '/\\')) . ' /D -0 /C "cmd /c IF @ftime LEQ %TIME:~0,-3% del @file"');
    else exec('find ' . escapeshellarg($this->dir) . ' -type f -mmin +0 -delete');
    $this->normalizeVault();
  }
}