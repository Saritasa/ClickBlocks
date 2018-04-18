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
 * Responsibility of this file: vaultreader.php
 *
 * @category   DB
 * @package    DB
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 * @since      File available since Release 1.0.0
 */
 
namespace ClickBlocks\DB\Sync;

/**
 * Class is used for reading database structure changes from the vault. 
 *
 * @abstract
 */
class VaultReader implements IReader
{
  /**
   * Path to the vault file.
   *
   * @var string $file
   * @access protected
   */
  protected $file = null;
  
  /**
   * Database structure received from the vault. 
   *
   * @var array $info
   * @access protected
   */
  protected $info = null;
  
  /**
   * Regular expression for detecting the tables, containing synchronizing data. 
   *
   * @var array $infoTablesPattern
   * @access protected
   */
  protected $infoTablesPattern = null;

  /**
   * Constructor.
   *
   * @params string $file - path to the vault file.
   * @access public
   */
  public function __construct($file)
  {
    $this->file = $file;
  }
  
  /**
   * Sets the regular expression for the names of the tables, containing synchronizing data.
   *
   * @param string $pattern - the regular expression.
   * @access public
   */
  public function setInfoTables($pattern)
  {
    $this->infoTablesPattern = $pattern;
  }
  
  /**
   * Returns the regular expression for detecting the tables, containing synchronizing data.
   * 
   * @return string
   * @access public
   */
  public function getInfoTables()
  {
    return $this->infoTablesPattern;
  }
  
  /**
   * Resets the received data of the database structure. 
   * Repetitive call of the "read" method will allow to receive up-to-date information concerning database structure.
   *
   * @return self
   * @access public
   */
  public function reset()
  {
    $this->info = null;
    return $this;
  }
  
  /**
   * Reads database structure.
   *
   * @return array - you can find the format of returned array in file synchronizer/structure_db.txt
   * @access public
   */
  public function read()
  {
    if ($this->info) return $this->info;
    if (!is_file($this->file)) $this->info = array();
    else $this->info = unserialize(gzuncompress(file_get_contents($this->file)));
    if ($this->infoTablesPattern) foreach ($this->info['data'] as $table => $data)
    {
      if (preg_match($this->infoTablesPattern, $table)) continue;
      unset($this->info['data'][$table]);
    }
    return $this->info;
  }
}