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
 * Responsibility of this file: dbreader.php
 *
 * @category   DB
 * @package    DB
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 * @since      File available since Release 1.0.0
 */
namespace ClickBlocks\DB\Sync;

/**
 * Base class for all classes reading database structure.
 *
 * @abstract
 */
abstract class DBReader implements IReader
{
  /**
   * Read database structure. 
   *
   * @var array $info - you can find format of this array in file /lib/db/synchronizer/structure_db.txt
   * @access protected
   */
  protected $info = null;
  
  /**
   * Regular expression for detecting the tables, containing synchronizing data.
   *
   * @var string $infoTablesPattern
   * @access protected
   */
  protected $db =  null;
  
  /**
   * Regular expression for detecting the tables, containing synchronizing data.
   *
   * @var string $infoTablesPattern
   * @access protected
   */
  protected $infoTablesPattern = null;
  
  /**
   * Constructor.
   *
   * @param ClickBlocks\DB\Sync\DBCore $db
   * @access public
   */
  public function __construct(DBCore $db)
  {
    $this->db = $db;
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
   * Executes specified SQL query and returns result of its execution. 
   *
   * @param PDO $pdo
   * @param string $type - type of the query.
   * @param array $params - parameters of the query.
   * @return mixed
   * @access protected
   */
  protected function getData(\PDO $pdo, $type, array $params = null)
  {
    $st = $pdo->prepare($this->db->getSQL('info', $type, $params));
    $st->execute();
    return $st;
  }
}