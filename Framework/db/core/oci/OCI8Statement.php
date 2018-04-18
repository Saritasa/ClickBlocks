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
 * Represents a prepared statement and, after the statement is executed, an associated result set.
 *
 * @version 1.0.0
 * @package cb.db
 */
class OCI8Statement
{
    /**
     * Error message templates.
     */
    const ERR_OCI8STATEMENT_1 = 'Invalid column index.';
    const ERR_OCI8STATEMENT_2 = 'PDO::FETCH_KEY_PAIR fetch mode requires the result set to contain exactly 2 columns.';

    /**
     * The database connection object.
     *
     * @var \ClickBlocks\DB\OCI8 $db
     * @access protected
     */
    protected $db = null;

    /**
     * The identifier of the prepared statement object.
     *
     * @var resource $st
     * @access protected
     */
    protected $st = null;

    /**
     * The default fetch mode.
     *
     * @var array $defaultFetchMode
     * @access protected
     */
    protected $defaultFetchMode = [\PDO::FETCH_BOTH, null, []];

    /**
     * Constructor.
     *
     * @param \ClickBlocks\DB\OCI8 $db - the database connection object.
     * @param resource $stdid - the prepared statement identifier.
     * @access public
     */
    public function __construct(OCI8 $db, $stid)
    {
        $this->st = $stid;
        $this->db = $db;
    }

    /**
     * Changes the default fetch mode for a OCI8Statement object.
     *
     * @param integer $style - determines how OCI8 returns the rows.
     * @param mixed $arg - this argument have a different meaning depending on the value of the $style parameter.
     * @param array $args - arguments of custom class constructor when the $style parameter is PDO::FETCH_CLASS.
     * @access public
     */
    public function setFetchMode($style = \PDO::FETCH_BOTH, $arg = null, array $args = [])
    {
        $this->defaultFetchMode = [$style, $arg, $args];
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder in the SQL statement that was used to prepare the statement.
     * Returns TRUE on success or FALSE on failure.
     *
     * @param mixed $parameter - the parameter identifier.
     * @param mixed $value - the value to bind to the parameter.
     * @param integer $type - the explicit data type for the parameter using PDO::PARAM_* constants.
     * @return boolean
     * @access public
     */
    public function bindValue($parameter, $value, $type = \PDO::PARAM_STR)
    {
        return oci_bind_by_name($this->st, $parameter, $value, -1, $this->getOCI8Type($type));
    }

    /**
     * Binds a PHP variable to a corresponding named placeholder in the SQL statement that was used to prepare the statement.
     * Returns TRUE on success or FALSE on failure.
     *
     * @param mixed $parameter - the parameter identifier.
     * @param mixed $variable - name of the PHP variable to bind to the SQL statement parameter.
     * @param integer $type - the explicit data type for the parameter using PDO::PARAM_* constants.
     * @param integer $length - the length of the data type. To indicate that a parameter is an OUT parameter from a stored procedure, you must explicitly set the length.
     * @param mixed $options - the driver specific options.
     * @return boolean
     * @access public
     */
    public function bindParam($parameter, &$variable, $type = \PDO::PARAM_STR, $length = -1, $options = null)
    {
        return oci_bind_by_name($this->st, $parameter, $variable, $length, $this->getOCI8Type($type));
    }

    /**
     * Execute the prepared statement.
     * Returns TRUE on success or FALSE on failure.
     *
     * @param array $parameters - an array of values with as many elements as there are bound parameters in the SQL statement being executed.
     * @return boolean
     * @access public
     */
    public function execute(array $parameters = null)
    {
        if ($parameters) foreach ($parameters as $k => $v) $this->bindValue($k, $v);
        $errMode = $this->db->getAttribute(\PDO::ATTR_ERRMODE);
        if ($errMode != \PDO::ERRMODE_WARNING)
        {
            $enabled = \CB::isErrorHandlingEnabled();
            $level = \CB::errorHandling(false, E_ALL & ~E_WARNING);
        }
        $res = oci_execute($this->st, $this->db->inTransaction() ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS);
        if ($errMode != \PDO::ERRMODE_WARNING) \CB::errorHandling($enabled, $level);
        if ($res === false && $errMode == \PDO::ERRMODE_EXCEPTION) throw new \Exception(oci_error($this->st)['message']);
        return $res;
    }

    /**
     * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement executed by the corresponding OCI8Statement object.
     *
     * @return integer
     * @access public
     */
    public function rowCount()
    {
        return oci_num_rows($this->st);
    }

    /**
     * Fetches multiple rows from a query into a two-dimensional array.
     *
     * @param integer $style - determines how OCI8 returns the rows.
     * @param mixed $arg - this argument have a different meaning depending on the value of the $style parameter.
     * @param array $args - arguments of custom class constructor when the $style parameter is PDO::FETCH_CLASS.
     * @access public
     */
    public function fetchAll($style = null, $arg = null, array $args = [])
    {
        if ($style === null) list($style, $arg, $args) = $this->defaultFetchMode;
        if (($style & \PDO::FETCH_COLUMN) == \PDO::FETCH_COLUMN || $style == \PDO::FETCH_FUNC || $style == \PDO::FETCH_KEY_PAIR) $s = OCI_NUM;
        else if (($style & \PDO::FETCH_CLASS) == \PDO::FETCH_CLASS) $s = OCI_ASSOC;
        else if (($style & 7) == \PDO::FETCH_BOTH) $s = OCI_BOTH;
        else if (($style & 7) == \PDO::FETCH_ASSOC) $s = OCI_ASSOC;
        else if (($style & 7) == \PDO::FETCH_NUM) $s = OCI_NUM;
        else $s = OCI_BOTH;
        if (oci_fetch_all($this->st, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + $s) === false) return false;
        if ($style == \PDO::FETCH_FUNC)
        {
            $tmp = [];
            foreach ($rows as $row) $tmp[] = call_user_func_array($arg, $row);
            $rows = $tmp;
        }
        else if ($style == \PDO::FETCH_KEY_PAIR)
        {
            $tmp = [];
            if (count($rows) && count($rows[0]) != 2) throw new Core\Exception($this, 'ERR_OCI8STATEMENT_2');
            foreach ($rows as $row) $tmp[$row[0]] = $row[1];
            $rows = $tmp;
        }
        else if (($style & \PDO::FETCH_COLUMN) == \PDO::FETCH_COLUMN)
        {
            $arg = (int)$arg; $tmp = [];
            if (($style & \PDO::FETCH_UNIQUE) == \PDO::FETCH_UNIQUE)
            {
                foreach ($rows as $row) $tmp[$row[$arg]] = $row[$arg];
            }
            else if (($style & \PDO::FETCH_GROUP) == \PDO::FETCH_GROUP)
            {
                if (count($rows))
                {
                    $row = $rows[0]; $argn = count($row);
                    if ($argn < 2) throw new Core\Exception($this, 'ERR_OCI8STATEMENT_1');
                    $argn = $arg == $argn - 1 ? 0 : $arg + 1;
                    foreach ($rows as $row) $tmp[$row[$arg]][] = $row[$argn];
                }
            }
            else
            {
                foreach ($rows as $row) $tmp[] = $row[$arg];
            }
            $rows = $tmp;
        }
        else if (($style & \PDO::FETCH_CLASS) == \PDO::FETCH_CLASS)
        {
            $tmp = [];
            $ref = new \ReflectionClass($arg);
            foreach ($rows as $row)
            {
                $class = $ref->newInstanceArgs($args);
                foreach ($row as $k => $v)
                {
                    if ($ref->hasProperty($k))
                    {
                        $prop = $ref->getProperty($k);
                        $prop->setAccessible(true);
                        $prop->setValue($class, $v);
                    }
                    else
                    {
                        $class->{$k} = $v;
                    }
                }
                $tmp[] = $class;
            }
            $rows = $tmp;
        }
        return $rows;
    }

    /**
     * Fetches a row from a result set associated with a OCI8Statement object.
     *
     * @param integer $style - determines how OCI8 returns the row.
     * @return mixed
     * @access public
     */
    public function fetch($style = null)
    {
        if ($style === null) $style = $this->defaultFetchMode[0];
        if (($style & \PDO::FETCH_OBJ) == \PDO::FETCH_OBJ) return oci_fetch_object($this->st);
        if (($style & 7) == \PDO::FETCH_BOTH) $style = OCI_BOTH;
        else if (($style 7) == \PDO::FETCH_ASSOC) $style = OCI_ASSOC;
    else if (($style 7) == \PDO::FETCH_NUM) $style = OCI_NUM;
    else $style = OCI_BOTH;
    return oci_fetch_array($this->st, $style + OCI_RETURN_NULLS);
  }

    /**
     * Returns a single column from the next row of a result set or FALSE if there are no more rows.
     *
     * @param integer $column - 0-indexed number of the column you wish to retrieve from the row.
     * @return string
     * @access public
     */
    public function fetchColumn($column = 0)
    {
        $row = oci_fetch_row($this->st);
        if ($row === false) return false;
        return array_key_exists($column, $row) ? $row[$column] : array_shift($row);
    }

    /**
     * Converts the given PDO data type to OCI data type.
     *
     * @param integer $pdoType - the PDO data type identifier.
     * @return integer
     * @access protected
     */
    protected function getOCI8Type($pdoType)
    {
        switch ($pdoType)
        {
            case \PDO::PARAM_INT:
            case \PDO::PARAM_BOOL:
                return OCI_B_INT;
            default:
                return SQLT_CHR;
        }
    }
}
