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
 * Responsibility of this file: sqlgenerator.php
 *
 * @category   DB
 * @package    DB
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 * @since      File available since Release 1.0.0
 */
namespace ClickBlocks\DB;

use ClickBlocks\Core,
    ClickBlocks\Utils,
    ClickBlocks\Utils\PHP;

/**
 * Class assistant. generates SQL queries based on input
 * Класс-помошник. генерирует SQL запросы на основе входных данных
 * 
 */
class SQLGenerator
{
   public $objInfo = null;
   private $orm = null;

   /**
    * class constructor
    * конструктор класса
    * 
    * @access public
    */
   public function __construct()
   {
      $this->orm = ORM::getInstance();
      $this->objInfo = $this->orm->getORMInfoObject();
   }

   /**
    * Get the name of the database on behalf of the class
    * Получить имя БД по имени класса
    * 
    * @param string $class
    * @return string
    * @access public
    */
   public function getDBByClassName($class)
   {
      $tb = $this->objInfo->getTableAliasByClassName(Utils\PHPParser::getClassName($class));
      return $this->orm->getDB($tb[0]);
   }

   /**
    * function generates a request for poluchchenie one line on the basis of input data
    * функция генерирует запрос на получчение одной строки на основе входных данных
    * 
    * @param string $class
    * @param array $apk
    * @param bool $isRawData
    * @return string
    * @access public
    */
   public function getRow($class, array &$apk, $isRawData = false)
   {
     $class = PHP\Tools::getClassName($class);
     $tb = $this->objInfo->getTableAliasByClassName($class);
     $db = $this->orm->getDB($tb[0]);
     $sql = $db->sql;
     $sql->select([$this->objInfo->getDBNameByDBAlias($tb[0]) . '.' . $this->objInfo->getTableNameByTableAlias($tb[0], $tb[1]) => 't0']);
     foreach ($this->objInfo->getClassParents($class) as $n => $data)
     {
       if ($data == $tb) continue;
       $condition = [];
       foreach ($this->objInfo->getKeyByTableAlias($data[0], $data[1]) as $m => $key)
       {
         if ($isRawData) $key = $this->objInfo->getFieldNameByFieldAlias($data[0], $data[1], $key);
         $condition = ['t' . ($n + 1) . '.' . $key => $sql->exp($sql->wrap('t0.' . $key))];
       }
       $sql->join([$this->objInfo->getDBNameByDBAlias($data[0]) . '.' . $this->objInfo->getTableNameByTableAlias($data[0], $data[1]) => 't' . ($n + 1)], $condition);
     }
     $w = [];
     foreach ($apk as $k => $v) $w['t0.' . $k] = $v; 
     return $sql->where($w)->limit(1);
      /*$class = Utils\PHPParser::getClassName($class);
      $tb = $this->objInfo->getTableAliasByClassName($class);
      $db = $this->orm->getDB($tb[0]);
      $NN = ($db->getEngine() == 'mssql') ? 'N' : '';
      $pk = array_keys($apk);
      $w = $t = $k = $tmp = array();
      foreach ($this->objInfo->getClassParents($class) as $n => $data)
      {
         $dbName = $db->wrap($this->objInfo->getDBNameByDBAlias($data[0]));
         if ($db->getEngine() == 'mssql') $dbName .= '.[dbo]';
         $table = $db->wrap($this->objInfo->getTableNameByTableAlias($data[0], $data[1]));
         $pk1 = array();
         $t[] = $dbName . '.' . $table . ' t' . $n;
         foreach ($this->objInfo->getKeyByTableAlias($data[0], $data[1]) as $keyAlias)
         {
            $key = $this->objInfo->getFieldNameByFieldAlias($data[0], $data[1], $keyAlias);
            $kk = (!$isRawData) ? $keyAlias : $key;
            if (in_array($kk, $pk))
            {
               $w[] = 't' . $n . '.' . $db->wrap($key) . ' = ' . ((is_numeric($apk[$kk]) || $apk[$kk] == '') ? '' : $NN) . '?';
               $tmp[] = $apk[$kk];
            }
            $pk1[] = $key;
         }
         if (isset($pk2) && is_array($pk2))
         {
            foreach ($pk1 as $i => $key) $k[] = 't' . $n . '.' . $db->wrap($key) . ' = t' . ($n - 1) . '.' . $db->wrap($pk2[$i]);
         }
         $pk2 = $pk1;
      }
      $w = array_merge($k, $w);
      $apk = $tmp;
      switch (strtolower($db->getEngine()))
      {
         case 'mysql':
           return 'SELECT * FROM ' . implode(', ', array_reverse($t)) . ((count($w)) ? ' WHERE ' . implode(' AND ', $w) : '') . ' LIMIT 1';
         case 'mssql':
           return 'SELECT TOP 1 * FROM ' . implode(', ', array_reverse($t)) . ((count($w)) ? ' WHERE ' . implode(' AND ', $w) : '');
       }*/
   }

   /**
    * function generates an UPDATE query based on input
    * функция генерирует запрос UPDATE на основе входных данных
    * 
    * @param string $class
    * @param array $apk
    * @param array $values
    * @param bool $isRawData
    * @return string
    * @access public
    */
   public function updateByID($class, array &$apk, array &$values, $isRawData = false)
   {
      $class = Utils\PHPParser::getClassName($class);
      $tb = $this->objInfo->getTableAliasByClassName($class);
      $db = $this->orm->getDB($tb[0]);
      $NN = ($db->getEngine() == 'mssql') ? 'N' : '';
      $w = $t = $k = $tmp = array();
      $pk = array_keys($apk);
      $parents = $this->objInfo->getClassParents($class);
      foreach ($parents as $n => $data)
      {
         $dbName = $db->wrap($this->objInfo->getDBNameByDBAlias($data[0]));
         if ($db->getEngine() == 'mssql') $dbName .= '.[dbo]';
         $table = $db->wrap($this->objInfo->getTableNameByTableAlias($data[0], $data[1]));
         $pk1 = array();
         $t[$n] = $dbName . '.' . $table;
         foreach ($this->objInfo->getKeyByTableAlias($data[0], $data[1]) as $keyAlias)
         {
            $key = $this->objInfo->getFieldNameByFieldAlias($data[0], $data[1], $keyAlias);
            $kk = (!$isRawData) ? $keyAlias : $key;
            if (in_array($kk, $pk))
            {
               $w[] = $t[$n] . '.' . $db->wrap($key) . ' = ' . ((is_numeric($apk[$kk]) || $apk[$kk] == '') ? '' : $NN) . '?';
               $tmp[] = $apk[$kk];
            }
            $pk1[] = $key;
         }
         if (isset($pk2) && is_array($pk2))
         {
            foreach ($pk1 as $i => $key) $k[] = $t[$n] . '.' . $db->wrap($key) . ' = ' . $t[$n - 1] . '.' . $db->wrap($pk2[$i]);
         }
         $pk2 = $pk1;
      }
      $w = array_merge($k, $w);
      $apk = $tmp;
      $v = $tmp = array();
      foreach ($values as $field => $value)
      {
         foreach ($parents as $n => $data)
         {
            $fieldName = (!$isRawData) ? $this->objInfo->getFieldNameByFieldAlias($data[0], $data[1], $field) : $field;
            if ($fieldName)
            {
               $v[] = $t[$n] . '.' . $db->wrap($fieldName) . ' = ' . ((is_numeric($value) || $value == '') ? '' : $NN) . '?';
               $tmp[] = $value;
            }
         }
      }
      $values = $tmp;
      return 'UPDATE ' . implode(', ', array_reverse($t)) . ' SET ' . implode(', ', $v) . ' WHERE ' . implode(' AND ', $w);
   }

   /**
    * function generates a DELETE request on the basis of input data
    * функция генерирует запрос DELETE на основе входных данных
    * 
    * @param string $class
    * @param array $apk
    * @param bool $isRawData
    * @return string
    * @access public
    */
   public function deleteByID($class, array &$apk, $isRawData = false)
   {
      $class = Utils\PHPParser::getClassName($class);
      $tb = $this->objInfo->getTableAliasByClassName($class);
      $db = $this->orm->getDB($tb[0]);
      $NN = ($db->getEngine() == 'mssql') ? 'N' : '';
      $w = $t = $tmp = array();
      $pk = array_keys($apk);
      foreach ($this->objInfo->getClassParents($class) as $n => $data)
      {
         $dbName = $db->wrap($this->objInfo->getDBNameByDBAlias($data[0]));
         if ($db->getEngine() == 'mssql') $dbName .= '.[dbo]';
         $table = $db->wrap($this->objInfo->getTableNameByTableAlias($data[0], $data[1]));
         $pk1 = array();
         foreach ($this->objInfo->getKeyByTableAlias($data[0], $data[1]) as $keyAlias)
         {
            $key = $this->objInfo->getFieldNameByFieldAlias($data[0], $data[1], $keyAlias);
            $kk = (!$isRawData) ? $keyAlias : $key;
            $t[] = 't' . $n;
            if (in_array($kk, $pk))
            {
               $w[] = 't' . $n . '.' . $db->wrap($key) . ' = ' . ((is_numeric($apk[$kk]) || $apk[$kk] == '') ? '' : $NN) . '?';
               $tmp[] = $apk[$kk];
            }
            $pk1[] = $key;
         }
         if (is_array($pk2))
         {
            $k = array();
            foreach ($pk1 as $i => $key) $k[] = 't' . $n . '.' . $db->wrap($key) . ' = t' . ($n - 1) . '.' . $db->wrap($pk2[$i]);
            $j[] = 'INNER JOIN ' . $dbName . '.' . $table . ' AS t' . $n . ' ON ' . implode(' AND ', $k);
         }
         $pk2 = $pk1;
      }
      $apk = $tmp;
      $dbName = $db->wrap($this->objInfo->getDBNameByDBAlias($tb[0]));
      if (strtolower($db->getEngine()) == 'mssql') $dbName .= '.[dbo]';
      $table = $db->wrap($this->objInfo->getTableNameByTableAlias($tb[0], $tb[1]));
      $sql = 'DELETE ' . implode(', ', $t) . ' FROM ' . $dbName . '.' . $table . ' AS t0' . endl;
      if (count($j)) $sql .= implode(endl, $j) . endl;
      $sql .= 'WHERE ' . implode(' AND ', $w);
      return $sql;
   }

   /**
    * function generates a query SELECT * based on input
    * функция формирует запрос SELECT * на основе входных данных
    * 
    * @param string $class
    * @param string|array $where
    * @param int $start
    * @param int $limit
    * @return string
    * @access public
    */
   public function getByQuery($class, $where = null, $order = null, $start = null, $limit = null)
   {
      $class = Utils\PHPParser::getClassName($class);
      $tb = $this->objInfo->getTableAliasByClassName($class);
      $db = $this->orm->getDB($tb[0]);
      $t = $k = array();
      foreach ($this->objInfo->getClassParents($class) as $n => $data)
      {
         $dbName = $db->wrap($this->objInfo->getDBNameByDBAlias($data[0]));
         if (strtolower($db->getEngine()) == 'mssql') $dbName .= '.[dbo]';
         $table = $db->wrap($this->objInfo->getTableNameByTableAlias($data[0], $data[1]));
         $pk1 = array();
         foreach ($this->objInfo->getKeyByTableAlias($data[0], $data[1]) as $keyAlias)
         {
            $key = $this->objInfo->getFieldNameByFieldAlias($data[0], $data[1], $keyAlias);
            $t[] = $dbName . '.' . $table . ' AS t' . $n;
            $pk1[] = $key;
         }
         if (isset($pk2) && is_array($pk2))
         {
            foreach ($pk1 as $i => $key) $k[] = 't' . $n . '.' . $db->wrap($key) . ' = t' . ($n - 1) . '.' . $db->wrap($pk2[$i]);
         }
         $pk2 = $pk1;
      }
      $where = trim($db->sql->joinWhere($where, 'AND'));
      if ($where) $k[] = $where;
      if (count($k)) $where = $db->sql->joinWhere($k, ' AND ');
      if ($where) $where = ' WHERE ' . $where;
      if ($order) $order = ' ORDER BY ' . $order;
      if ((int)$limit)
      {
         if ((int)$start) $l = ' LIMIT ' . (int)$start . ', ' . (int)$limit;
         else $l = ' LIMIT ' . (int)$limit;
      }
      return 'SELECT * FROM ' . implode(', ', array_reverse($t)) .  $where . $order . $l;
   }
}

?>