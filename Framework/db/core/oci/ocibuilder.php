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
 * Class for building Oracle database queries.
 *
 * @version 1.0.0
 * @package cb.db
 */
class OCIBuilder extends SQLBuilder
{
  /**
   * Error message templates.
   */
  const ERR_OCI_1 = 'You are trying to rename column during its modifying. It\'s not possible in OCI. To rename column you should use renameColumn method.';
  
  /**
   * Determines whether the named ($seq > 0) or question mark placeholder ($seq is 0 or FALSE) are used in the SQL statement.
   *
   * @var integer $seq
   * @access protected
   */
  protected $seq = 1;
  
  /**
   * Returns OCI data type that mapped to PHP type.
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
        return 'int';
      case 'number':
      case 'float':
      case 'numeric':
      case 'decimal':
      case 'dec':
      case 'double':
      case 'real':
        return 'float';
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
      if (substr($part, 0, 1) == '"' && substr($part, -1, 1) == '"') $part = substr($part, 1, -1);
      if (trim($part) == '') throw new Core\Exception('ClickBlocks\DB\SQLBuilder::ERR_SQL_2');
      $part = '"' . $part . '"';
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
        return addcslashes(str_replace("'", "''", $value), '\_%');
      case self::ESCAPE_QUOTED_LIKE:
        return "'%" . addcslashes(str_replace("'", "''", $value), '\_%') . "%' ESCAPE '\'";
      case self::ESCAPE_LEFT_LIKE:
        return "'%" . addcslashes(str_replace("'", "''", $value), '\_%') . "' ESCAPE '\'";
      case self::ESCAPE_RIGHT_LIKE:
        return "'" . addcslashes(str_replace("'", "''", $value), '\_%') . "%' ESCAPE '\'";
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
    if ($scheme === null) return 'SELECT table_name FROM user_tables';
    return 'SELECT object_name FROM all_objects WHERE object_type = \'TABLE\' AND owner = ' . $this->quote($scheme);
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
    return 'SELECT dbms_metadata.get_ddl(\'TABLE\', ' . $this->quote($table) . ') AS "info" FROM dual';
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
    return 'SELECT t1.*,
                   (SELECT t3.constraint_type FROM all_cons_columns t2
                    INNER JOIN all_constraints t3 ON t3.owner = t2.owner AND t3.constraint_name = t2.constraint_name
                    WHERE t2.table_name = t1.table_name AND t2.column_name = t1.column_name AND t3.constraint_type = \'P\' GROUP BY t3.constraint_type) AS key 
            FROM user_tab_cols t1
            WHERE t1.table_name = ' . $this->quote($table);
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
    return 'ALTER TABLE ' . $this->wrap($oldName, true) . ' RENAME TO ' . $this->wrap($newName, true);
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
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' RENAME COLUMN ' . $this->wrap($oldName) . ' TO ' . $this->wrap($newName);
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
    if ($oldName != $newName) throw new Core\Exception($this, 'ERR_OCI_1'); 
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' MODIFY ' . $this->wrap($newName) . ' ' . $type;
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
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' DROP CONSTRAINT ' . $this->wrap($name);
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
      preg_match('/(.*)\((.*)\)|[^()]*/', $row['DATA_TYPE'], $arr);
      $column = $row['COLUMN_NAME'];
      $tmp[$column]['column'] = $column;
      $tmp[$column]['type'] = $type = strtolower(isset($arr[1]) ? $arr[1] : $arr[0]);
      $tmp[$column]['phpType'] = $this->getPHPType($type);
      $tmp[$column]['isPrimaryKey'] = $row['KEY'] == 'P';
      $tmp[$column]['isNullable'] = $row['NULLABLE'] == 'Y';
      $tmp[$column]['isAutoincrement'] = false;
      $tmp[$column]['isUnsigned'] = false;
      $tmp[$column]['default'] = $tmp[$column]['isNullable'] ? $row['DATA_DEFAULT'] : substr($row['DATA_DEFAULT'], 0, -1);
      $tmp[$column]['maxLength'] = strlen($row['DATA_PRECISION']) ? (int)$row['DATA_PRECISION'] : (int)$row['DATA_LENGTH'];
      if ($tmp[$column]['type'] == 'clob')
        $tmp[$column]['maxLength'] = 0;
      $tmp[$column]['precision'] = (int)$row['DATA_SCALE'];
      $tmp[$column]['set'] = false;
      if (strlen($tmp[$column]['default']))
      {
        if (($type == 'timestamp' || $type == 'date') && $tmp[$column]['default'] == 'CURRENT_TIMESTAMP') $tmp[$column]['default'] = new SQLExpression($tmp[$column]['default']);
        else if ($tmp[$column]['default'][0] == "'" && $tmp[$column]['default'][strlen($tmp[$column]['default']) - 1] == "'") $tmp[$column]['default'] = str_replace("''", "'", substr($tmp[$column]['default'], 1, -1));
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
    if (is_object($info['info'])) $sql = $info['info']->load();
    else $sql = stream_get_contents($info['info']);
    $info = ['constraints' => []];
    $clean = function($column, $smart = false)
    {
      $column = explode(',', $column);
      foreach ($column as &$col) if (substr(trim($col), 0, 1) == '"') $col = substr(trim($col), 1, -1);
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
          if ($act == '') continue;
          $act = explode(' ', trim($act));
          $actions[strtolower($act[0])] = $act[1];
        }
      }
      $info['constraints'][$match[2] == '' ? $n++ : $clean($match[2], true)] = ['columns' => $clean($match[3]), 
                                                                                'reference' => ['table' => $clean($match[4], true), 'columns' => $clean($match[5])],
                                                                                'actions' => $actions];
    }
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
    $res = $this->insertExpression($insert['columns'], $data);
    if (!is_array($res['values'])) $sql .= $res['values'];
    else if (count($res['values']) == 1)
    {
      $sql = 'INSERT INTO ' . $this->wrap($insert['table'], true) . ' ';
      foreach ($res['values'] as &$values) $values = '(' . implode(', ', $values) . ')';
      $sql .= '(' . implode(', ', $res['columns']) . ') VALUES ' . implode(', ', $res['values']);
    }
    else
    {
      $sql = 'INSERT ALL';
      $tb = $this->wrap($insert['table'], true);
      $cols = '(' . implode(', ', $res['columns']) . ')';
      foreach ($res['values'] as $values) $sql .= ' INTO ' . $tb . ' ' . $cols . ' VALUES (' . implode(', ', $values) . ')';
      $sql .= ' SELECT 1 FROM DUAL';
    }
    return $sql;
  }
  
  /**
   * Returns completed SQL query of SELECT type.
   *
   * @param array $select - the query data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildSelect(array $select, &$data)
  {
    if ($this->db && substr($this->db->getClientVersion(), 0, 2) == '12') return parent::buildSelect($select, $data);
    if (empty($select['limit'])) return parent::buildSelect($select, $data);
    $limit = $select['limit'];
    unset($select['limit']);
    $alias = 'a' . rand(111111, 999999);
    $sql = parent::buildSelect($select, $data);
    $sql = 'SELECT ' . $alias . '.*' . ($limit['offset'] !== null ? ', ROWNUM rnum' : '') . ' FROM (' . $sql . ') ' . $alias;
    if ($limit['limit'] !== null) $sql .= ' WHERE ROWNUM <= ' . ((int)$limit['limit'] + (int)$limit['offset']);
    if ($limit['offset']  !== null) $sql = 'SELECT * FROM (' . $sql . ') WHERE rnum > ' . (int)$limit['offset'];
    return $sql;
  }
  
  /**
   * Returns LIMIT clause of the SQL statement.
   *
   * @param array $limit - the LIMIT data.
   * @return string
   * @access protected
   */
  protected function buildLimit(array $limit)
  {
    if (count($limit) == 1) return ' OFFSET ' . $limit[0] . ' ROWS';
    return ' OFFSET ' . $limit[0] . ' ROWS FETCH NEXT ' . $limit[1] . ' ROWS ONLY';
  }
}