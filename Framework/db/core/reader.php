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

namespace ClickBlocks\DB;

/**
 * This class implements an iteration of rows from a query result set.
 *
 * @version 1.0.0
 * @package cb.db
 */
class Reader implements \Countable, \Iterator
{
  /**
   * An instance of \PDOStatement.
   *
   * @var \PDOStatement $st
   * @access protected
   */
  protected $st = null;
  
  /**
   * The current row of the rowset.
   *
   * @var array $row
   * @access protected
   */
  protected $row = null;
  
  /**
   * The index of the current row.
   *
   * @var integer $index
   * @access protected
   */
  protected $index = null;

  /**
   * Class constructor.
   *
   * @param PDOStatement $statement
   * @param integer $mode - fetch mode for this SQL statement.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $mode parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $mode parameter is PDO::FETCH_CLASS.
   * @access public
   */
  public function __construct($statement, $mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    $this->st = $statement;
    $this->setFetchMode($mode, $arg, $ctorargs);
  }
  
  /**
   * Returns SQL statement for more low-level operating.
   *
   * @return \PDOStatement
   * @access public
   */
  public function getStatement()
  {
    return $this->st;
  }
  
  /**
   * Set the default fetch mode for this statement.
   *
   * @param integer $mode - the fetch mode should be one of the PDO::FETCH_* constants.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $mode parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $mode parameter is PDO::FETCH_CLASS.
   * @access public
   */
  public function setFetchMode($mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    if ($arg === null) $this->st->setFetchMode($mode);
    else
    {
      if ($ctorargs === null) $this->st->setFetchMode($mode, $arg);
      else $this->st->setFetchMode($mode, $arg, $ctorargs);
    }
  }
  
  /**
   * Returns the number of rows in the result set.
   *
   * @return integer
   * @access public
   */
  public function count()
  {
    return $this->st->rowCount();
  }

  /**
   * Resets the iterator to the initial state.
   *
   * @access public
   */
  public function rewind() 
  {
    if ($this->index !== null) $this->st->execute();
    $this->row = $this->st->fetch();
    $this->index = 0;
  }
  
  /**
   * Returns the index of the current row.
   *
   * @return integer
   * @access public
   */
  public function key()
  {
    return $this->index;
  }
  
  /**
   * Moves the internal pointer to the next row.
   *
   * @access public
   */
  public function next()
  {
    $this->row = $this->st->fetch();
    $this->index++;
  }
  
  /**
   * Returns the current row.
   *
   * @return mixed
   * @access public
   */
  public function current()
  {
    return $this->row;
  }
  
  /**
   * Returns whether there is a row of data at the current position.
   *
   * @return boolean
   * @access public
   */
  public function valid()
  {
    return $this->row !== false;
  }
}