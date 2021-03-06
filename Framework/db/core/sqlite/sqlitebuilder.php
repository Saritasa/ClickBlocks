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

use ClickBlocks\Core;

/**
 * Class for building SQLite queries.
 *
 * @version 1.0.0
 * @package cb.db
 */
class SQLiteBuilder extends SQLBuilder
{
  /**
   * Error message templates.
   */
  const ERR_SQLITE_1 = 'Renaming a DB column is not supported by SQLite.';
  const ERR_SQLITE_2 = 'Adding a foreign key constraint to an existing table is not supported by SQLite.';
  const ERR_SQLITE_3 = 'Dropping a foreign key constraint is not supported by SQLite.';
  
  /**
   * Returns SQLite data type that mapped to PHP type.
   *
   * @param string $type - SQL type.
   * @return string
   * @access public
   */
  public function getPHPType($type)
  {
    switch ($type)
    {
      case 'int':
      case 'int2':
      case 'int8':
      case 'integer':
      case 'tinyint':
      case 'smallint':
      case 'mediumint':
      case 'bigint':
        return 'int';
      case 'numeric':
      case 'float':
      case 'real':
      case 'decimal':
      case 'double':
      case 'double precision':
        return 'float';
      case 'bool':
        return 'bool';
    }
    return 'string';
  }
  
  /**
   * Quotes a table or column name for use in SQL queries.
   *
   * @param string $name - a column or table name.
   * @param boolean $isTableName - determines whether table name is used.
   * @return string
   * @access public
   */
  public function wrap($name, $isTableName = false)
  {
    if (strlen($name) == 0) throw new Core\Exception('ClickBlocks\DB\SQLBuilder::ERR_SQL_2');
    $name = explode('.', $name);
    foreach ($name as &$part)
    {
      if ($part == '*') continue;
      if (substr($part, 0, 1) == '"' && substr($part, -1, 1) == '"')
      {
        $part = str_replace('""', '"', substr($part, 1, -1));
      }
      if (trim($part) == '') throw new Core\Exception('ClickBlocks\DB\SQLBuilder::ERR_SQL_2');
      $part = '"' . str_replace('"', '""', $part) . '"';
    }
    return implode('.', $name);
  }
  
  /**
   * Quotes a value (or an array of values) to produce a result that can be used as a properly escaped data value in an SQL statement.
   *
   * @param string | array $value - if this value is an array then all its elements will be quoted.
   * @param string $format - determines the format of the quoted value. This value must be one of the SQLBuilder::ESCAPE_* constants.
   * @return string | array
   * @access public
   */
  public function quote($value, $format = self::ESCAPE_QUOTED_VALUE)
  {
    if (is_array($value))
    {
      foreach ($value as &$v) $v = $this->quote($v, $format);
      return $value;
    }
    switch ($format)
    {
      case self::ESCAPE_QUOTED_VALUE:
        return "'" . str_replace("'", "''", $value) . "'";
      case self::ESCAPE_VALUE:
        return str_replace("'", "''", $value);
      case self::ESCAPE_LIKE:
        return addcslashes(str_replace("'", "''", $value), '_%');
      case self::ESCAPE_QUOTED_LIKE:
        return "'%" . addcslashes(str_replace("'", "''", $value), '_%') . "%'";
      case self::ESCAPE_LEFT_LIKE:
        return "'%" . addcslashes(str_replace("'", "''", $value), '_%') . "'";
      case self::ESCAPE_RIGHT_LIKE:
        return "'" . addcslashes(str_replace("'", "''", $value), '_%') . "%'";
    }
    throw new Core\Exception($this, 'ERR_SQL_4', $format);
  }
  
  /**
   * Returns SQL for getting the table list of the current database.
   *
   * @param string $scheme - a table scheme (database name).
   * @return string
   * @access public
   */
  public function tableList($scheme = null)
  {
    return 'SELECT tbl_name FROM sqlite_master WHERE type = \'table\' AND tbl_name <> \'sqlite_sequence\' ORDER BY name';
  }
  
  /**
   * Returns SQL for getting metadata of the specified table.
   *
   * @param string $table
   * @return string
   * @access public
   */
  public function tableInfo($table)
  {
    return 'SELECT sql FROM sqlite_master WHERE type = \'table\' AND tbl_name = ' . $this->quote($table);
  }

  /**
   * Returns SQL for getting metadata of the table columns.
   *
   * @param string $table
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function columnsInfo($table)
  {
    return 'PRAGMA table_info(' . $this->quote($table) . ')';
  }

  /**
   * Returns SQL for creating a new DB table.
   *
   * @param string $table - the name of the table to be created.
   * @param array $columns - the columns of the new table.
   * @param string $options - additional SQL fragment that will be appended to the generated SQL.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function createTable($table, array $columns, $options = null)
  {
    $tmp = [];
    foreach ($columns as $column => $type)
    {
      if (is_numeric($column)) $tmp[] = $type;
      else $tmp[] = $this->wrap($column) . ' ' . $type;
    }
    return 'CREATE TABLE ' . $this->wrap($table, true) . (count($tmp) ? ' (' . implode(', ', $tmp) . ')' : '') . ($options ? ' ' . $options : '');
  }

  /**
   * Returns SQL for renaming a table.
   *
   * @param string $oldName - old table name.
   * @param string $newName - new table name.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function renameTable($oldName, $newName)
  {
    return 'ALTER TABLE ' . $this->wrap($oldName, true) . ' RENAME TO ' . $this->quote($newName);
  }

  /**
   * Returns SQL that can be used for removing the particular table.
   *
   * @param string $table
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function dropTable($table)
  {
    return 'DROP TABLE ' . $this->wrap($table, true);
  }

  /**
   * Returns SQL that can be used to remove all data from a table.
   *
   * @param string $table
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function truncateTable($table)
  {
    return 'DELETE FROM ' . $this->wrap($table, true);
  }

  /**
   * Returns SQL for adding a new column to a table.
   *
   * @param string $table - the table that the new column will be added to.
   * @param string $column - the name of the new column.
   * @param string $type - the column type.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function addColumn($table, $column, $type)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' ADD ' . $this->quote($column) . ' ' . $type;
  }

  /**
   * Returns SQL for renaming a column.
   *
   * @param string $table - the table whose column is to be renamed.
   * @param string $oldName - the previous name of the column.
   * @param string $newName - the new name of the column.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function renameColumn($table, $oldName, $newName)
  {
    throw new Core\Exception($this, 'ERR_SQLITE_1');
  }

  /**
   * Returns SQL for changing the definition of a column.
   *
   * @param string $table - the table whose column is to be changed.
   * @param string $oldName - the old name of the column.
   * @param string $newName - the new name of the column.
   * @param string $type - the type of the new column.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function changeColumn($table, $oldName, $newName, $type)
  {
    return 'ALTER TABLE '. $this->wrap($table, true) . ' CHANGE ' . $this->wrap($oldName) . ' ' . $this->quote($newName) . ' ' . $type;
  }

  /**
   * Returns SQL for dropping a DB column.
   *
   * @param string $table - the table whose column is to be dropped.
   * @param string $column - the name of the column to be dropped.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function dropColumn($table, $column)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' DROP ' . $this->wrap($column);
  }

  /**
   * Returns SQL for adding a foreign key constraint to an existing table.
   *
   * @param string $name - the name of the foreign key constraint.
   * @param string $table - the table that the foreign key constraint will be added to.
   * @param array $columns - the column(s) to that the constraint will be added on.
   * @param string $refTable - the table that the foreign key references to.
   * @param array $refColumns - the column(s) that the foreign key references to.
   * @param string $delete - the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
   * @param string $update - the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function addForeignKey($name, $table, array $columns, $refTable, array $refColumns, $delete = null, $update = null)
  {
    throw new Core\Exception($this, 'ERR_SQLITE_2');
  }

  /**
   * Returns SQL for dropping a foreign key constraint.
   *
   * @param string $name - the name of the foreign key constraint to be dropped.
   * @param string $table - the table whose foreign key is to be dropped.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function dropForeignKey($name, $table)
  {
    throw new Core\Exception($this, 'ERR_SQLITE_3');
  }

  /**
   * Returns SQL for creating a new index.
   *
   * @param string $name - the index name.
   * @param string $table - the table that the new index will be created for.
   * @param array $columns - the columns that should be included in the index.
   * @param string $class - the index class. For example, it can be UNIQUE, FULLTEXT and etc.
   * @param string $type - the index type.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function createIndex($name, $table, array $columns, $class = null, $type = null)
  {
    $tmp = [];
    foreach ($columns as $column => $length)
    {
      if (is_string($column)) $tmp[] = $this->wrap($column) . '(' . (int)$length . ')';
      else $tmp[] = $this->wrap($length);
    }
    return 'CREATE ' . ($class ? $class . ' ' : '') . 'INDEX ' . $this->quote($name) . ' ON ' . $this->wrap($table, true) . ' (' . implode(', ' , $tmp) . ')';
  }

  /**
   * Returns SQL for dropping an index.
   *
   * @param string $name - the name of the index to be dropped.
   * @param string $table - the table whose index is to be dropped.
   * @return string
   * @access public
   * @throws Core\Exception
   */
  public function dropIndex($name, $table)
  {
    return 'DROP INDEX ' . $this->wrap($name, true);
  }
  
  /**
   * Normalizes the metadata of the DB columns.
   *
   * @param array $info - the column metadata.
   * @return array
   * @access public
   */
  public function normalizeColumnsInfo(array $info)
  {
    $tmp = [];
    foreach ($info as $row)
    {
      $row['type'] = strtolower($row['type']);
      preg_match('/(.*)\((.*)\)|[^()]*/', str_replace('unsigned', '', $row['type']), $arr);
      $column = $row['name'];
      $tmp[$column]['column'] = $column;
      $tmp[$column]['type'] = $type = isset($arr[1]) ? $arr[1] : $arr[0];
      $tmp[$column]['phpType'] = $this->getPHPType($tmp[$column]['type']);
      $tmp[$column]['isPrimaryKey'] = (bool)$row['pk'];
      $tmp[$column]['isNullable'] = ($row['notnull'] == 0);
      $tmp[$column]['isAutoincrement'] = ($tmp[$column]['type'] == 'integer');
      $tmp[$column]['isUnsigned'] = strpos($row['type'], 'unsigned') !== false;
      $tmp[$column]['default'] = $row['dflt_value'];
      $tmp[$column]['maxLength'] = 0;
      $tmp[$column]['precision'] = 0;
      $tmp[$column]['set'] = false;
      if (empty($arr[2])) continue;
      $arr = explode(',', $arr[2]);
      if (count($arr) == 1) $tmp[$column]['maxLength'] = (int)trim($arr[0]);
      else
      {
        $tmp[$column]['maxLength'] = (int)trim($arr[0]);
        $tmp[$column]['precision'] = (int)trim($arr[1]);
      }
    }
    return $tmp;
  }
  
  /**
   * Normalizes the DB table metadata.
   *
   * @param array $info - the table metadata.
   * @return array
   * @access public
   */
  public function normalizeTableInfo(array $info)
  {
    $sql = $info['sql'];
    $info = ['constraints' => []];
    $clean = function($column, $smart = false)
    {
      $column = explode(',', $column);
      foreach ($column as &$col) if (substr(trim($col), 0, 1) == '"') $col = substr(trim($col), 1, -1);
      return $smart && count($column) == 1 ? $column[0] : $column;
    };
    $action = function($match)
    {
      $actions = [];
      foreach (explode('ON', trim($match)) as $act)
      {
        if ($act == '') continue;
        $act = explode(' ', trim($act));
        $actions[strtolower($act[0])] = $act[1];
      }
      return $actions;
    };
    $n = 0;
    preg_match_all('/[(,\r\n]\s*(.+)\s*REFERENCES\s*(.+)\s*\((.+)\)\s*(ON\s+[^,\r\n\)]+)?/mi', $sql, $matches, PREG_SET_ORDER);
    foreach ($matches as $k => $match)
    {
      $column = trim(explode(' ', $match[1])[0]);
      if (strtolower($column) == 'constraint') continue;
      $actions = isset($match[4]) ? $action($match[4]) : [];
      $info['constraints'][$n++] = ['columns' => $clean($column), 
                                    'reference' => ['table' => $clean($match[2], true), 'columns' => $clean($match[3])],
                                    'actions' => $actions];
    }
    preg_match_all('/(CONSTRAINT\s+(.+)\s+)?FOREIGN\s+KEY\s*\((.+)\)\s*REFERENCES\s*(.+)\s*\((.+)\)\s*(ON\s+[^,\r\n\)]+)?/mi', $sql, $matches, PREG_SET_ORDER);
    foreach ($matches as $k => $match)
    {
      $actions = isset($match[6]) ? $action($match[6]) : [];
      $info['constraints'][$match[2] == '' ? $n++ : $clean($match[2], true)] = ['columns' => $clean($match[3]), 
                                                                                'reference' => ['table' => $clean($match[4], true), 'columns' => $clean($match[5])],
                                                                                'actions' => $actions];
    }
    return $info;
  }
}
