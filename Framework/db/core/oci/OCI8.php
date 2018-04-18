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

use ClickBlocks\Core,
    ClickBlocks\Cache,
    ClickBlocks\Net;

/**
 * The class defines the interface for accessing Oracle databases in PHP via OCI8 extension. 
 *
 * @version 1.0.0
 * @package cb.db
 */
class OCI8
{
  /**
   * The connection identifier needed for most other OCI8 operations.
   *
   * @var resource $conn
   * @access protected
   */
  protected $conn = null;
  
  /**
   * Determines whether the autocommit mode is turned off.
   *
   * @var boolean $inTransaction
   * @access protected
   */
  protected $inTransaction = false;
  
  /**
   * Database connection attributes.
   *
   * @var array $attributes
   * @access protected
   */
  protected $attributes = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

  /**
   * Constructor. Creates an OCI8 instance to represent a connection to the requested database.
   *
   * @param string $dsn - the Data Source Name (DSN), contains the information required to connect to the database.
   * @param string $username - the user name for the DSN string.
   * @param string $password - the password for the DSN string.
   * @param array $options - the value array of driver-specific connection options.
   * @access public
   */
  public function __construct($dsn, $username = null, $password = null, array $options = null)
  {
    $method = !empty($options['isNewConnection']) ? 'oci_new_connect' : (!empty($options[\PDO::ATTR_PERSISTENT]) ? 'oci_pconnect' : 'oci_connect');
    $this->conn = $method($username, $password, $dsn, isset($options['charset']) ? $options['charset'] : null, isset($options['sessionMode']) ? $options['sessionMode'] : null);
  }
  
  /**
   * Returns all currently available OCI drivers.
   *
   * @return array
   * @access public
   */
  public function getAvailableDrivers()
  {
    return ['OCI8'];
  }
  
  /**
   * Returns the value of a database connection attribute.
   * An unsuccessful call returns null.
   *
   * @param integer $attribute - the attribute identifier.
   * @return mixed
   * @access public
   */
  public function getAttribute($attribute)
  {
    return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
  }
  
  /**
   * Sets an attribute on the database handle.
   * The method returns TRUE on success or FALSE on failure.
   *
   * @param integer $attribute - the attribute identifier.
   * @param mixed $value - the attribute value.
   */
  public function setAttribute($attribute, $value)
  {
    if (!isset($this->attributes[$attribute])) return false;
    $this->attributes[$attribute] = $value;
    return true;
  }
  
  /**
   * Places quotes around the input string (if required) and escapes special characters within the input string.
   *
   * @param mixed $value - the string to be quoted.
   * @param integer $type - provides a data type hint.
   */
  public function quote($value, $type = \PDO::PARAM_STR)
  {
    if ($type == \PDO::PARAM_INT || $type == \PDO::PARAM_BOOL) return (int)$value;
    return "'" . str_replace("'", "''", $value) . "'";
  }

  /**
   * Returns an array of error information about the last operation performed by this database handle.
   *
   * @return array
   * @access public
   */
  public function errorInfo()
  {
    $error = oci_error($this->conn);
    return [$error['sqltext'], $error['code'], $error['message'], $error['offset']];
  }
  
  /**
   * Returns the Oracle error number.
   *
   * @return integer.
   * @access public
   */
  public function errorCode()
  {
    return oci_error($this->conn)['code'];
  }
  
  /**
   * Prepares SQL using connection and returns the statement identifier.
   *
   * @param string $statement - this must be a valid SQL statement for the target database server.
   * @param array $options - this array holds one or more (key, value) pairs to set attribute values for the statement handle.
   * @return resource
   * @access public
   */
  public function prepare($statement, array $options = [])
  {
    return new OCI8Statement($this, oci_parse($this->conn, $statement));
  }
  
  /**
   * Returns the last value from a sequence object.
   *
   * @param string $sequenceName - name of the sequence object from which the ID should be returned.
   * @return string
   * @access public
   */
  public function lastInsertId($seqname = null)
  {
    if (!$seqname) return;
    $st = $this->prepare('SELECT "' . $seqname . '".currval FROM dual');
    $st->execute();
    return $st->fetchColumn();
  }
  
  /**
   * Turns off autocommit mode. Returns always TRUE.
   *
   * @return boolean
   * @access public
   */
  public function beginTransaction()
  {
    return $this->inTransaction = true;
  }
  
  /**
   * Checks if a transaction is currently active within the driver.
   *
   * @return boolean
   * @access public
   */
  public function inTransaction()
  {
    return $this->inTransaction;
  }
  
  /**
   * Commits a transaction.
   * Returns TRUE on success or FALSE on failure.
   *
   * @return boolean
   * @access public
   */
  public function commit()
  {
    if (oci_commit($this->conn) === false) return false;
    $this->inTransaction = false; 
    return true;
  }
  
  /**
   * Rolls back a transaction.
   * Returns TRUE on success or FALSE on failure.
   *
   * @return boolean
   * @access public
   */
  public function rollBack()
  {
    if (oci_rollback($this->conn) === false) return false;
    $this->inTransaction = false;
    return true;
  }
}
