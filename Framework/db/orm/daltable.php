<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core,
    ClickBlocks\Exceptions;

interface IDALTable
{
   public function __set($field, $value);
   public function __get($field);
   public function save();
   public function insert();
   public function replace();
   public function update();
   public function delete();
}

/**
 * 
 * Класс представляет из себя реализацию шаблона проектирования ActiveRecord
 */
class DALTable implements IDALTable, \Serializable
{
  // Error message templates
  const ERR_1 = 'Access to non-existant property: "[{var}]"!';
  
  const ERR_DAL_1 = 'Incorrect value of property "[{var}]"';
  const ERR_DAL_2 = 'It\'s impossible to rewrite autoincrement field "[{var}]"';
  const ERR_DAL_3 = '';
  const ERR_DAL_4 = 'Property "[{var}]" does not exist in table "[{var}]"';
  const ERR_DAL_5 = 'Primary key of table "[{var}]" is already filled';
  const ERR_DAL_6 = 'Primary key of table "[{var}]" is not filled yet';
  const ERR_DAL_7 = 'Value of property "[{var}]" is too long';
  const ERR_DAL_8 = 'Navigation Property "[{var}]" Foreign Keys mismatch. Mismatched values [{var}] vs [{var}]';
  const ERR_DAL_9 = 'Method "[{var}]" does not exist';
  
  const DATETIME_FORMAT = 'Y-m-d H:i:s';
  const DATETIME_FORMAT_SHORT = 'Y-m-d';
  
   private $dbAlias = null;
   private $logicTableName = null;

   /**
    * @var DB
    */
   protected $db = null;
   protected $info = array();
   protected $config = null;

   protected $isInstantiated = false;
   protected $isUpdated = false;
   protected $isDeleted = false;

   public function __construct($dbAlias, $logicTableName)
   {
      $this->dbAlias = $dbAlias;
      $this->logicTableName = $logicTableName;
      $this->init();
   }

   protected function init()
   {
      $this->config = \CB::getInstance()->getConfig();
      $orm = ORM::getInstance();
      $this->db = $orm->getDB($this->dbAlias);
      $info = $orm->getORMInfo();
      $this->info = $info['model'][$this->dbAlias]['tables'][$this->logicTableName];
   }

   public function getDB()
   {
      return $this->db;
   }

   public function getDBAlias()
   {
      return $this->dbAlias;
   }

   public function getDBName()
   {
      return ORM::getInstance()->getORMInfoObject()->getDBNameByDBAlias($this->dbAlias);
   }

   public function getTableAlias()
   {
      return $this->logicTableName;
   }

   public function getTable()
   {
      return $this->info['name'];
   }

   public function getField($field)
   {
      return $this->info['fields'][$field];
   }

   public function getFields()
   {
      return $this->info['fields'];
   }

   public function getLogicField($field)
   {
      return $this->info['logicFields'][$field];
   }

   public function getLogicFields()
   {
      return $this->info['logicFields'];
   }

   public function getNavigationField($field)
   {
      return $this->info['navigationFields'][$field];
   }

   public function getNavigationFields()
   {
      return $this->info['navigationFields'];
   }

   public function getFieldAlias($field)
   {
      return $this->info['aliases'][$field];
   }

   public function getFieldName($alias)
   {
      return $this->info['fields'][$alias]['name'];
   }

   public function getAliases()
   {
      return $this->info['aliases'];
   }

   public function getKeyLength()
   {
      return count($this->info['pk']);
   }

   public function getKey($isRawData = false)
   {
      $pk = $this->info['pk'];
      if ($isRawData) foreach ($pk as $field) $pk[$field] = $this->info['fields'][$field]['name'];
      return ((count($pk) == 1) ? end($pk) : array_values($pk));
   }

   public function getKeyValue($isRawData = false)
   {
      $values = array();
      if ($isRawData) foreach ($this->info['pk'] as $field) $values[$this->info['fields'][$field]['name']] = (string)$this->info['fields'][$field]['value'];
      else foreach ($this->info['pk'] as $field) $values[$field] = (string)$this->info['fields'][$field]['value'];
      return ((count($values) == 1) ? (string)end($values) : $values);
   }

   public function setKeyValue($value)
   {
      if (!is_array($value)) $values = array($value);
      else $values = array_values($value);
      $n = 0; foreach ($this->info['pk'] as $field){ $this->{$field} = $values[$n]; $n++;}
   }

   public function isKeyFilled()
   {
      $flag = true;
      foreach ($this->info['pk'] as $field) $flag &= (isset($this->info['fields'][$field]['value']) && strlen($this->info['fields'][$field]['value']) != 0);
      return (bool)$flag;
   }

   public function __set($field, $value)
   {
      if (!isset($this->info['fields'][$field]))
      {
         if (isset($this->info['logicFields'][$field]))
         {
            if (isset($this->info['logicFields'][$field]['set']))
            {
               call_user_func_array(array($this, $this->info['logicFields'][$field]['set']['name']), array($value));
               return;
            }
         }
         throw new Core\Exception($this, 'ERR_DAL_4', $field, $this->getTable());
      }
      if ($this->isDateOrTimeField($field)) {
        if ($value instanceof \DateTime && !($value instanceof DateTime)) $value = DateTime::fromDateTime($value);
        else if (is_scalar($value)) $value = new DateTime($value);
      }
      if ($value !== NULL && !is_scalar($value) && (!is_object($value) || !($value instanceof SQLExpression || $value instanceof DateTime))) {
        throw new Core\Exception($this, 'ERR_DAL_1', $field);
      }
      if (array_key_exists('value', $this->info['fields'][$field]) && $value != $this->info['fields'][$field]['value'])
      {
        if ($this->isInstantiated)
        {
           if ($this->info['fields'][$field]['autoincrement']) throw new Core\Exception($this, 'ERR_DAL_2', $field);
        }
      }
      if (isset($this->info['fields'][$field]['setter'])) $this->info['fields'][$field]['value'] = call_user_func_array(array($this, $this->info['fields'][$field]['setter']['name']), array($value));
      else $this->info['fields'][$field]['value'] = $value;
   }

   public function __get($field)
   {
      if (!isset($this->info['fields'][$field]))
      {
         if (isset($this->info['logicFields'][$field]))
         {
            if (isset($this->info['logicFields'][$field]['get']))
            {
               return call_user_func_array(array($this, $this->info['logicFields'][$field]['get']['name']), array());
            }
         }
         throw new Core\Exception($this, 'ERR_DAL_4', $field, $this->getTable());
      }
      if (isset($this->info['fields'][$field]['getter'])) return call_user_func_array(array($this, $this->info['fields'][$field]['getter']['name']), array($this->info['fields'][$field]['value']));
      $val = &$this->info['fields'][$field]['value'];
      if ($val !== NULL && is_scalar($val)) {
        if ($this->isDateOrTimeField($field)) {
          $val = new DateTime($val);
          return $val;
        }
        else switch ($this->info['fields'][$field]['phpType'])
        {
          case 'int': return (int)$val;
          case 'float': return (float)$val;
          case 'bool': return (bool)$val;
          default:    return $val;
        }
      }
      return $val;
   }

   public function __isset($field)
   {
      return isset($this->info['fields'][$field]) || isset($this->info['logicFields'][$field]);
   }
   
   public function __unset($field)
   {
     $this->__set($field,  null);
   }

   public function setValues(array $fields, $isRawData = false)
   {
      if ($isRawData)
      {
         foreach ($fields as $k => $v)
         {
            if (isset($this->info['aliases'][$k]))
              $this->{$this->info['aliases'][$k]} = $v;
         }
      }
      else
      {
         foreach ($fields as $k => $v)
         {
            if (isset($this->info['fields'][$k]) || isset($this->info['logicFields'][$k]))
              $this->{$k} = $v;
         }
      }
      return $this;
   }

   public function getValues($isRawData = false)
   {
      $values = array();
      if ($isRawData) foreach ($this->info['fields'] as $k => $v) $values[$v['name']] = $this->{$k};
      else foreach ($this->info['fields'] as $k => $v) $values[$k] = $this->{$k};
      return $values;
   }
   
   public function hasChanged($field = null)
   {
     $this->refreshUpdatedState();
     if (!$field) return $this->isUpdated;
     if (!isset($this->info['fields'][$field])) throw new Core\Exception($this, 'ERR_1', $field);
     return (bool)$this->info['fields'][$field]['hasChanged'];
   }

   public function getOriginalValue($field)
   {
     if (!isset($this->info['fields'][$field])) throw new Core\Exception($this, 'ERR_1', $field);
     return $this->info['fields'][$field]['origValue'];
   }

   public function assign(array $data = null)
   {
      if (is_array($data) && count($data))
      {
         foreach ($data as $k => $v)
         {
            $field = isset($this->info['aliases'][$k]) ? $this->info['aliases'][$k] : null;
            if (!$field) continue;
            $fieldinfo = &$this->info['fields'][$field];
            $fieldinfo['value'] = $v;
            $fieldinfo['hasChanged'] = false;
            if (isset($fieldinfo['get'])) $fieldinfo['value'] = call_user_func_array(array($this, $fieldinfo['get']['name']), array($fieldinfo['value']));
            $fieldinfo['origValue'] = $fieldinfo['value'];
         }
         $this->isInstantiated = true;
      }
      else
      {
         foreach ($this->info['fields'] as $field => $info)
         {
            if (!$info['default'] && $info['null']) $this->info['fields'][$field]['value'] = NULL;
            else $this->info['fields'][$field]['value'] = $info['default'];
            if (isset($info['get'])) $this->info['fields'][$field]['value'] = call_user_func_array(array($this, $this->info['fields'][$field]['get']['name']), array($this->info['fields'][$field]['value']));
            $this->info['fields'][$field]['origValue'] = $this->info['fields'][$field]['value'];
         }
         $this->isInstantiated = false;
      }
      $this->isUpdated = false;
   }

   public function getAll($where = null, $orderBy = null, $limit = null)
   {
      if ($w = $this->db->sql->joinWhere($where, 'AND')) $w = ' WHERE ' . $w;
      switch ($this->db->getEngine())
      {
         case 'mysql':
           $sql = 'SELECT * FROM ' . $this->db->wrap($this->getTable()) . $w . (($orderBy) ? ' ORDER BY ' . $orderBy : '') . (($limit) ? ' LIMIT ' . $limit : '');
           break;
         case 'mssql':
           $sql = 'SELECT ' . (($limit) ? ' TOP ' . $limit : '') . ' * FROM ' . $this->db->wrap($this->getTable()) . $w . (($orderBy) ? ' ORDER BY ' . $orderBy : '');
           break;
      }
      return $this->db->rows($sql, is_array($where) ? $where : array());
   }

   public function deleteAll()
   {
      return $this->db->execute('TRUNCATE TABLE ' . $this->db->sql->wrap($this->getTable()));
   }

   public function isNumericField($field)
   {
      $type = $this->db->sql->getPHPType($this->info['fields'][$field]['type']);
      return ($type == 'int' || $type == 'float');
   }

   public function isTextField($field)
   {
      return ($this->db->sql->getPHPType($this->info['fields'][$field]['type']) == 'string');
   }

   public function isDateOrTimeField($field)
   {
      switch ($this->info['fields'][$field]['type'])
      {
        case 'datetime':
        case 'timestamp':
        case 'date':
        case 'time':
        case 'year':
          return true;
      }
      return false;
   }

   public function getLength($field)
   {
      return (int)$this->info['fields'][$field]['length'];
   }

   public function getPrecision($field)
   {
      return (int)$this->info['fields'][$field]['precision'];
   }

   public function save(array $options = null)
   {
      $res = '';
      if (!$this->isInstantiated) $res = $this->insert($options);
      else $res = $this->update();
      return $res;
   }
   
   private function refreshUpdatedState()
   {
     if ($this->isInstantiated) {
       $this->isUpdated = false;
       foreach ($this->info['fields'] as &$field) {
         if ($field['hasChanged'] === false && $field['value'] != $field['origValue']) {
           $field['hasChanged'] = true;
           $this->isUpdated = true;
         }
       } 
     }
   }
   
   private function afterSaveData()
   {
     $this->isUpdated = false;
     $this->isInstantiated = true;
     foreach ($this->info['fields'] as &$field) {
       $field['hasChanged'] = false;
       if (empty($field['value']) || $field['value'] instanceof SQLExpression && strtoupper($field['value']) == 'NULL') $field['value'] = null;
       $field['origValue'] = is_object($field['value']) ? $field['value']->__toString() : $field['value'];
     }
   }

   public function insert(array $options = null)
   {
      $this->refreshUpdatedState();
      if (count($this->info['pk']) == 1 && $this->info['fields'][end($this->info['pk'])]['autoincrement'])
      {
         if ($this->isKeyFilled()) throw new Core\Exception($this, 'ERR_DAL_5', $this->getTable());
         $res = $this->info['fields'][end($this->info['pk'])]['value'] = $this->db->insert($this->getTable(), $this->getData(), $options);
      }
      else
      {
         if (!$this->isKeyFilled()) throw new Core\Exception($this, 'ERR_DAL_6', $this->getTable());
         $res = $this->db->insert($this->getTable(), $this->getData(), $options);
      }
      $this->afterSaveData();
      return $res;
   }

   public function replace()
   {
      $this->refreshUpdatedState();
      $id = (int)$this->db->replace($this->getTable(), $this->getData(true));
      if (count($this->info['pk']) == 1 && $this->info['fields'][end($this->info['pk'])]['autoincrement'])
      {
         $this->info['fields'][end($this->info['pk'])]['value'] = $id;
      }
      $this->afterSaveData();
      return (int)$this->db->getAffectedRows();
   }

   public function update()
   {
      $this->refreshUpdatedState();
      $res = '';
      if (!$this->isKeyFilled()) throw new Core\Exception($this, 'ERR_DAL_6', $this->getTable());
      if ($this->isUpdated) $res = $this->db->update($this->getTable(), $this->getData(), $this->getKeyData());
      $this->afterSaveData();
      return (int)$res;
   }

   public function delete()
   {
      if (!$this->isKeyFilled()) throw new Core\Exception($this, 'ERR_DAL_6', $this->getTable());
      $this->isDeleted = true;
      $this->isInstantiated = false;
      return (int)$this->db->delete($this->getTable(), $this->getKeyData());
   }

   public function __toString()
   {
      return 'Table: ' . $this->getTable();
   }

   public function serialize()
   {
      $data = get_object_vars($this);
      $data['info'] = $this->getValues(false);
      unset($data['db']);
      unset($data['config']);
      return serialize($data);
   }

   public function unserialize($data)
   {
      $data = unserialize($data);
      $values = $data['info'];
      foreach ($data as $k => $v) $this->{$k} = $v;
      $this->init();
      $flag = $this->isInstantiated;
      $this->isInstantiated = false;
      $this->setValues($values, false);
      $this->isInstantiated = $flag;
   }

   protected function getData($isReplace = false)
   {
      $p = array();
      foreach ($this->info['fields'] as $alias => $value)
      {
         $field = $value['name'];
         if (!$isReplace && $value['autoincrement']) continue;
         if ($this->isInstantiated && empty($value['hasChanged'])) continue;
         if (isset($value['set'])) $value['value'] = call_user_func_array(array($this, $value['set']['name']), array($value['value']));
         $len = $this->getLength($alias);
         $nonStringField = $this->isNumericField($alias) || $this->isDateOrTimeField($alias);
         if (!isset($value['value']))
         {
            if (strlen($value['default'])) $p[$field] = $value['default'];
            else $p[$field] = NULL;
         }
         else
         {
            $p[$field] = $value['value'];
            if ($this->isNumericField($alias) && is_bool($p[$field])) $p[$field] = (int)$p[$field];
            else if ($p[$field] instanceof DateTime) $p[$field] = $p[$field]->__toString();
            if (!$nonStringField && $len > 0 && (function_exists('mb_strlen') && ( $len <  mb_strlen($value['value'], isset($this->config['charset']) ? $this->config['charset'] : 'utf8'))))
            {
              throw new Core\Exception($this, 'ERR_DAL_7', $alias);
            }
         }
         if (!is_object($p[$field]) && $nonStringField && in_array(strtoupper($p[$field]), array('NOW()','NULL','CURRENT_TIMESTAMP'))) $p[$field] = new SQLExpression($p[$field]);
      }
      return $p;
   }

   protected function getKeyData()
   {
      $values = array();
      foreach ($this->info['pk'] as $field) $values[$this->info['fields'][$field]['name']] = $this->info['fields'][$field]['value'];
      return $values;
   }
   
   public function isInstantiated()
   {
      return (bool)$this->isInstantiated;
   }
}

?>
