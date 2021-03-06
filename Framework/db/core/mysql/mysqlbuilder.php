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
 * Class for building MySQL queries.
 *
 * @version 1.0.0
 * @package cb.db
 */
class MySQLBuilder extends SQLBuilder
{
  /**
   * Error message templates.
   */
  const ERR_MYSQL_1 = 'Renaming a DB column is not supported by MySQL.';
  
  /**
   * Returns MySQL data type that mapped to PHP type.
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
      case 'integer':
      case 'smallint':
      case 'tinyint':
      case 'mediumint':
      case 'bigint':
      case 'year':
      case 'bit':
      case 'serial':
        return 'int';
      case 'double':
      case 'float':
      case 'real':
      case 'decimal':
        return 'float';
      case 'boolean':
      case 'bool':
        return 'bool';
      case 'datetime':
      case 'timestamp':
        return '\ClickBlocks\DB\DateTime';
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
      if (substr($part, 0, 1) == '`' && substr($part, -1, 1) == '`')
      {
        $part = str_replace('``', '`', substr($part, 1, -1));
      }
      if (trim($part) == '') throw new Core\Exception('ClickBlocks\DB\SQLBuilder::ERR_SQL_2');
      $part = '`' . str_replace('`', '``', $part) . '`';
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
        return "'" . addcslashes($value, "\\'\n\r\t\x00\x08\x1a") . "'";
      case self::ESCAPE_VALUE:
        return addcslashes($value, "\\'\"\n\r\t\x00\x08\x1a");
      case self::ESCAPE_LIKE:
        return addcslashes($value, "\\'\"_%\n\r\t\x00\x08\x1a");
      case self::ESCAPE_QUOTED_LIKE:
        return "'%" . addcslashes($value, "\\'_%\n\r\t\x00\x08\x1a") . "%'";
      case self::ESCAPE_LEFT_LIKE:
        return "'%" . addcslashes($value, "\\'_%\n\r\t\x00\x08\x1a") . "'";
      case self::ESCAPE_RIGHT_LIKE:
        return "'" . addcslashes($value, "\\'_%\n\r\t\x00\x08\x1a") . "%'";
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
    return 'SHOW TABLES' . ($scheme !== null ? ' FROM ' . $this->wrap($scheme, true) : '');
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
    return 'SHOW CREATE TABLE ' . $this->wrap($table, true);
  }
  
  /**
   * Returns SQL for getting metadata of the table columns.
   *
   * @param string $table
   * @return string
   * @access public
   */
  public function columnsInfo($table)
  {
    return 'SHOW COLUMNS FROM ' . $this->wrap($table, true);
  }
  
  /**
   * Returns SQL for creating a new DB table.
   *
   * @param string $table - the name of the table to be created.
   * @param array $columns - the columns of the new table.
   * @param string $options - additional SQL fragment that will be appended to the generated SQL.
   * @return string
   * @access public
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
   */
  public function renameTable($oldName, $newName)
  {
    return 'RENAME TABLE ' . $this->wrap($oldName, true) . ' TO ' . $this->wrap($newName, true);
  }
  
  /**
   * Returns SQL that can be used for removing the particular table.
   *
   * @param string $table
   * @return string
   * @access public
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
   */
  public function truncateTable($table)
  {
    return 'TRUNCATE TABLE ' . $this->wrap($table, true);
  }
  
  /**
   * Returns SQL for adding a new column to a table.
   *
   * @param string $table - the table that the new column will be added to.
   * @param string $column - the name of the new column.
   * @param string $type - the column type.
   * @return string
   * @access public
   */
  public function addColumn($table, $column, $type)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' ADD ' . $this->wrap($column) . ' ' . $type;
  }
  
  /**
   * Returns SQL for renaming a column.
   *
   * @param string $table - the table whose column is to be renamed.
   * @param string $oldName - the previous name of the column.
   * @param string $newName - the new name of the column.
   * @return string
   * @access public   
   */
  public function renameColumn($table, $oldName, $newName)
  {
    throw new Core\Exception($this, 'ERR_MYSQL_1');
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
   */
  public function changeColumn($table, $oldName, $newName, $type)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' CHANGE ' . $this->wrap($oldName) . ' ' . $this->wrap($newName) . ' ' . $type;
  }
  
  /**
   * Returns SQL for dropping a DB column.
   *
   * @param string $table - the table whose column is to be dropped.
   * @param string $column - the name of the column to be dropped.
   * @return string
   * @access public
   */
  public function dropColumn($table, $column)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' DROP COLUMN ' . $this->wrap($column);
  }
  
  /**
   * Returns SQL for adding a foreign key constraint to an existing table.
   *
   * @param string $name - the name of the foreign key constraint.
   * @param string $table - the table that the foreign key constraint will be added to.
   * @param array $columns - the column(s) to that the constraint will be added on.
   * @param string $refTable - the table that the foreign key references to.
   * @param array $refColumns - the column(s) that the foreign key references to.
   * @param string $delete - the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL.
   * @param string $update - the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL.
   * @return string
   * @access public
   */
  public function addForeignKey($name, $table, array $columns, $refTable, array $refColumns, $delete = null, $update = null)
  {
    foreach ($columns as &$column) $column = $this->wrap($column);
    foreach ($refColumns as &$column) $column = $this->wrap($column);
    $sql = 'ALTER TABLE ' . $this->wrap($table, true) . ' ADD CONSTRAINT ' . $this->wrap($name) . ' FOREIGN KEY (' . implode(', ', $columns) . ') REFERENCES ' . $this->wrap($refTable, true) . ' (' . implode(', ', $refColumns) . ')';
    if ($update != '') $sql .= ' ON UPDATE ' . $update;
    if ($delete != '') $sql .= ' ON DELETE ' . $delete;
    return $sql;
  }
  
  /**
   * Returns SQL for dropping a foreign key constraint.
   *
   * @param string $name - the name of the foreign key constraint to be dropped.
   * @param string $table - the table whose foreign key is to be dropped.
   * @return string
   * @access public
   */
  public function dropForeignKey($name, $table)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' DROP FOREIGN KEY ' . $this->wrap($name);
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
   */
  public function createIndex($name, $table, array $columns, $class = null, $type = null)
  {
    $tmp = [];
    foreach ($columns as $column => $length)
    {
      if (is_string($column)) $tmp[] = $this->wrap($column) . '(' . (int)$length . ')';
      else $tmp[] = $this->wrap($length);
    }
    return 'CREATE ' . ($class ? $class . ' ' : '') . 'INDEX ' . $this->wrap($name, true) . ' ON ' . $this->wrap($table, true) . ' (' . implode(', ' , $tmp) . ')' . ($type ? ' ' . $type : '');
  }
  
  /**
   * Returns SQL for dropping an index.
   *
   * @param string $name - the name of the index to be dropped.
   * @param string $table - the table whose index is to be dropped.
   * @return string
   * @access public
   */
  public function dropIndex($name, $table)
  {
    return 'DROP INDEX ' . $this->wrap($name, true) . ' ON ' . $this->wrap($table, true);
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
      preg_match('/(.*)\((.*)\)|[^()]*/', str_replace('unsigned', '', $row['Type']), $arr);
      $column = $row['Field'];
      $tmp[$column]['column'] = $column;
      $tmp[$column]['type'] = $type = isset($arr[1]) ? $arr[1] : $arr[0];
      $tmp[$column]['phpType'] = $this->getPHPType($tmp[$column]['type']);
      $tmp[$column]['isPrimaryKey'] = ($row['Key'] == 'PRI');
      $tmp[$column]['isNullable'] = ($row['Null'] != 'NO');
      $tmp[$column]['isAutoincrement'] = ($row['Extra'] == 'auto_increment');
      $tmp[$column]['isUnsigned'] = strpos($row['Type'], 'unsigned') !== false;
      $tmp[$column]['default'] = ($type == 'bit') ? substr($row['Default'], 2, 1) : $row['Default'];
      if ($type == 'timestamp' && $tmp[$column]['default']) $tmp[$column]['default'] = new SQLExpression($tmp[$column]['default']);
      $tmp[$column]['maxLength'] = 0;
      $tmp[$column]['precision'] = 0;
      $tmp[$column]['set'] = false;
      if (empty($arr[2])) continue;
      if ($type == 'enum' || $type == 'set') 
      {
        $set = [];
        for ($i = 0, $l = strlen($arr[2]) - 1; $i <= $l; $i++)
        {
          $chr = $arr[2][$i];
          if ($chr == "'") 
          {
            $j = $i;
            while ($i < $l)
            {
              $i++;
              $chr = $arr[2][$i];
              if ($chr == "'")
              {
                if ($i < $l && $arr[2][$i + 1] == "'") $i++;
                else break;
              }
            }
            $set[] = str_replace("''", "'", substr($arr[2], $j + 1, $i - $j - 1));
          }
        }
        $tmp[$column]['set'] = $set;
      }
      else
      {
        $arr = explode(',', $arr[2]);
        if (count($arr) == 1) $tmp[$column]['maxLength'] = (int)trim($arr[0]);
        else
        {
          $tmp[$column]['maxLength'] = (int)trim($arr[0]);
          $tmp[$column]['precision'] = (int)trim($arr[1]);
        }
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
    $sql = $info['Create Table'];
    $info = ['constraints' => [], 'keys' => []];
    $clean = function($column, $smart = false)
    {
      $column = explode(',', $column);
      foreach ($column as &$col) if (substr(trim($col), 0, 1) == '`') $col = substr(trim($col), 1, -1);
      return $smart && count($column) == 1 ? $column[0] : $column;
    };
    $n = 0;
    preg_match_all('/(CONSTRAINT\s+(.+)\s+)?FOREIGN\s+KEY\s*\((.+)\)\s*REFERENCES\s*(.+)\s*\((.+)\)\s*(ON\s+[^,\r\n\)]+)?/mi', $sql, $matches, PREG_SET_ORDER);
    foreach ($matches as $k => $match)
    {
      $actions = [];
      if (isset($match[6]))
      {
        foreach (explode('ON', trim($match[6])) as $act)
        {
          if ($act == '' || !trim($act)) continue;
          $act = explode(' ', trim($act));
          $actions[strtolower($act[0])] = $act[1];
        }
      }
      $info['constraints'][$match[2] == '' ? $n++ : $clean($match[2], true)] = ['columns' => $clean($match[3]), 
                                                                                'reference' => ['table' => $clean($match[4], true), 'columns' => $clean($match[5])],
                                                                                'actions' => $actions];
    }
    preg_match_all('/[^YN]+\s+KEY\s+(.+)\s*\(([^\)]+)\)/mi', $sql, $matches, PREG_SET_ORDER);
    foreach ($matches as $match)
    {
      $info['keys'][$clean($match[1], true)] = array_map($clean, explode(',', $match[2]));
    }
    if (preg_match('/\s+ENGINE\s*=\s*([^\s]+)/mi', $sql, $match)) $info['engine'] = $match[1];
    if (preg_match('/\s+DEFAULT CHARSET\s*=\s*([^\s]+)/mi', $sql, $match)) $info['charset'] = $match[1];
    if (preg_match('/\s+AUTO_INCREMENT\s*=\s*([^\s]+)/mi', $sql, $match)) $info['autoincrementInitialValue'] = $match[1];
    return $info;
  }
  
  /**
   * Returns completed SQL query of INSERT type.
   *
   * @param array $insert - the query data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildInsert(array $insert, &$data)
  {
    $columns = $insert['columns'];
    $sql = parent::buildInsert($insert, $data);
    if (!empty($insert['options']['updateOnKeyDuplicate'])) $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->updateExpression($columns, $data);
    return $sql;
  }
  
  public function joinWhere(&$where, $del)
  {
    if (is_array($where))
    {
      $p = $tmp = array(); $rows = $where;
      foreach ($rows as $k => $v)
      {
        if (is_array($v))
        {
          $p[] = '(' . $this->joinWhere($v, ($del == 'AND') ? 'OR' : 'AND') . ')';
          $tmp[] = $v;
        }
        else if (is_numeric($k))
        {
          $p[] = $v;
          $tmp[] = $v;
        }
        else if (!$this->isSQL($v))
        {
          $tmp[] = $v;
          $p[] = $this->wrap($k) . ((is_numeric($v) || $v == '') ? ' = ?' : ' = N?');
        }
        else
        {
          $p[] = $this->wrap($k) . ' = ' . $v;
          unset($where[$k]);
        }
      }
      $w = join(' ' . $del . ' ', $p);
      $where = $tmp;
    }
    else if (is_string($where)) $w = $where;
    return $w;
  }
}
