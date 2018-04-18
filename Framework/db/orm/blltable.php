<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core,
    ClickBlocks\Exceptions;

interface IBLLTable extends IDALTable
{

}

/**
 * 
 * Класс, реализующий бизнес-логику и представляющий собою агрегатор для DALTable
 * 
 * @method bool isKeyFilled() true if primary key is currently filled
 * @method \ClickBlocks\DB\DB getDB() returns DB object
 * @method bool isInstantiated() true if record is instantiated in DB table
 * @method string|array getKey($isRawData = false) Key field name/names
 * @method string|array getKeyValue($isRawData = false)
 * @method mixed getOriginalValue($field) get original (persisted) value of the given field
 */
class BLLTable implements IBLLTable, \Serializable
{
  // Error message templates
  const ERR_BLL_1 = 'Property "[{var}]" does not exist in class "[{var}]"';
  const ERR_BLL_2 = 'Method "[{var}]" does not exist in class "[{var}]"';
  const ERR_BLL_3 = 'Value of navigation property "[{var}]" is not a valid BLL object';
  const ERR_BLL_4 = 'Value of navigation property "[{var}]" is not an array';
  const ERR_BLL_5 = 'Method "[{var}]" of "[{var}]" class can not be invoked directly';

   const BUILD_BY_DB = 0;
   const BUILD_BY_CACHE = 1;
   const BUILD_BY_NEW = 2;
   
   private $buildBy = self::BUILD_BY_NEW;

   protected $dal = array();
   protected $config = null;
   protected $fields = array();
   protected $navigationFields = array();
   protected $navigators = array();
   public $expire = null;

   /**
    * 
    * Конструктор класса
    * 
    * @access public
    */
   public function __construct()
   {
      $this->init();
      $this->setExpire($this->config['orm']['cacheBLLObjectExpire']);
   }
   
   /**
    * @param mixed $pk
    * @return static
    */
   public static function getByID($pk)
   {
     $obj = new static();
     $obj->assignByID($pk);
     return $obj;
   }
   
   /**
    * Returns new (not instantiated) object
    * @param array $values if set, prefills new object with provided data by setValues()
    * @return \ClickBlocks\DB\BLLTable
    */
   public static function getNew(array $values = null)
   {
     $obj = new static();
     if ($values) $obj->setValues($values);
     return $obj;
   }

   /**
    * 
    * Функция инициализации
    * 
    * @access protected
    */
   protected function init()
   {
      $this->config = \CB::getInstance()->getConfig();
      $info = ORM::getInstance()->getORMInfo();
      $class = $this->getShortClassName();
      $this->fields = array_merge($info['classes'][$class]['table']['fields'], $info['classes'][$class]['table']['logicFields']);
      $this->navigationFields = $info['classes'][$class]['table']['navigationFields'];
   }
   
   public function getShortClassName()
   {
      $class = get_class($this);
      $k = strrpos($class, '\\');
      if ($k !== false) $class = substr($class, $k + 1);
      return $class;
   }

   /**
    * Устанавливает время жизни 
    * 
    * @param integer $value 
    * @access public
    */
   public function setExpire($value)
   {
      $this->expire = $value;
   }

   /**
    * Получить значение 
    * 
    * @return integer 
    * @access public
    */
   public function getExpire()
   {
      return $this->expire;
   }

   /**
    * 
    * @param IDALTable $tb
    * @param string $className
    * @return \ClickBlocks\DB\BLLTable 
    * @access public
    */
   public function addDAL(IDALTable $tb, $className)
   {
      $this->dal[$className] = $tb;
      return $this;
   }

   /**
    * 
    * @access public
    */
   public function getDAL($className = null)
   {
      return $this->dal[($className) ? $className : get_class($this)];
   }

   /**
    *
    * @return array 
    * @access public
    */
   public function getDALs()
   {
      return $this->dal;
   }

   /**
    *
    * @param type $field
    * @return type 
    * @access public
    */
   public function getField($field)
   {
      foreach ($this->dal as $dal) if (is_array($dal->getField($field))) return $dal->getField($field);
   }

   /**
    * Проверка, получены ли данные из базы
    *
    * @return bool 
    * @access public
    */
   public function isFromDB()
   {
      return ($this->buildBy == self::BUILD_BY_DB);
   }

   /**
    * Проверка, получены ли данные из кэша
    *
    * @return bool
    * @access public
    */
   public function isFromCache()
   {
      return ($this->buildBy == self::BUILD_BY_CACHE);
   }

   /**
    * Проверка, являются ли даные новыми
    *
    * @return bool 
    * @access public
    */
   public function isFromNew()
   {
      return ($this->buildBy == self::BUILD_BY_NEW);
   }

   /**
    * 
    *
    * @param array $fields
    * @param bool $isRawData
    * @return \ClickBlocks\DB\BLLTable 
    * @access public
    */
   public function setValues($fields, $isRawData = false)
   {
      foreach ($this->dal as $dal) $dal->setValues($fields, $isRawData);
      return $this;
   }

   /**
    *
    * @param bool $isRawData
    * @return array 
    * @access public
    */
   public function getValues($isRawData = false)
   {
      $values = $aliases = array();
      foreach ($this->dal as $dal)
      {
         $values = array_merge($values, $dal->getValues($isRawData));
         $aliases = array_merge($aliases, $dal->getAliases());
      }
      if (!$isRawData) return array_intersect_key($values, $this->fields);
      return array_intersect_key($values, $aliases);
   }

   /**
    *
    * @param array $data
    * @return \ClickBlocks\DB\BLLTable 
    * @access public
    */
   public function assign(array $data = null)
   {
      $n = 0;
      foreach ($this->dal as $dal) $dal->assign($data);
      $this->buildBy = self::BUILD_BY_DB;
      return $this;
   }

   /**
    *
    * @param string $field
    * @param string $value
    * @throws \Exception 
    * @access public
    */
   public function __set($field, $value)
   {
      if (isset($this->fields[$field]))
      {
         $dal = $this->dal[$this->fields[$field]];
         $fieldInfo = $dal->getField($field);
         $dal->{$field} = $value;
         if (isset($fieldInfo['navigators']) && count($fieldInfo['navigators']) > 0 && isset($fieldInfo['value']) && $fieldInfo['value'] != $value) 
         {
            foreach ($fieldInfo['navigators'] as $navigator=>$v) 
            {
               if (isset($this->navigators[$navigator])) $this->navigators[$navigator]->initialize();
            }
         }
         return;
      }
      else if (isset($this->navigationFields[$field]))
      {
        throw new Core\Exception('Setting Navigation property is not implemented!');
      }
      throw new Core\Exception($this, 'ERR_BLL_1', $field, get_class($this));
   }

   /**
    *
    * @param string $field
    * @return type
    * @throws \Exception 
    * @access public
    */
   public function __get($field)
   {
      if (isset($this->fields[$field])) return $this->dal[$this->fields[$field]]->{$field};
      else if (isset($this->navigationFields[$field]))
      {
         if (!isset($this->navigators[$field])) $this->initNavigator($field);
         return $this->navigators[$field];
      }
      throw new Core\Exception($this, 'ERR_BLL_1', $field, get_class($this));
   }

   /**
    *
    * @param string $field
    * @return bool 
    * @access public
    */
   public function __isset($field)
   {
      return (isset($this->fields[$field]) || isset($this->navigationFields[$field]));
   }
   
   public function __unset($field)
   {
     $this->__set($field,  null);
   }

   /**
    *
    * @param string $method
    * @param array $params
    * @return mixed
    * @throws \Exception 
    * @access public
    */
   public function __call($method, $params)
   {
      if (isset($this->navigationFields[$method])) return call_user_func_array(array($this->navigators[$method], 'limit'), $params);
      foreach ($this->dal as $dal)
      {
         try
         {
            return call_user_func_array(array($dal, $method), $params);
         }
         catch(\Exception $e){}
      }
      throw new Core\Exception($this, 'ERR_BLL_2', $method, get_class($this));
   }
   
   public function __clone()
   {
       $this->navigators = [];
   }

   /**
    *
    * @return string 
    * @access public
    */
   public function serialize()
   {
      $data = get_object_vars($this);
      unset($data['fields']);
      unset($data['navigationFields']);
      unset($data['config']);
      unset($data['navigator']);
      return serialize($data);
   }

   /**
    *
    * @param type $data 
    * @access public
    */
   public function unserialize($data)
   {
      $data = unserialize($data);
      foreach ($data as $k => $v) $this->{$k} = $v;
      $this->init();
      foreach ($this->navigationFields as $field => $class)
      {
         if (!isset($this->navigators[$field]))
         {
            if ($this->isFromCache()) $this->initNavigator($field);
         }
         else $this->navigators[$field]->initialize($this);
      }
   }

   /**
    *
    * @return type 
    * @access public
    */
   public function isCalledFromService()
   {
      $trace = debug_backtrace();
      return ($trace[2]['object'] instanceof Service && $trace[2]['object']->getObjectName() == '\\' . get_class($this));
   }
   
   public function getRow($pk = null, $isRawData = false)
   {
      if (!$pk) return array();
      $dal = $this->getDAL();
      if (!is_array($pk) && $dal->getKeyLength() > 1) throw new Core\Exception('ClickBlocks\DB\Service', 'ERR_SVC_1', get_called_class());
      if (is_array($pk) && (array)$dal->getKey($isRawData) != array_keys($pk)) throw new Core\Exception('Invalid key supplied.' . json_encode($dal->getKey($isRawData)));
      if (!is_array($pk)) $pk = array($dal->getKey($isRawData) => $pk);
      $sql = (new SQLGenerator())->getRow(get_called_class(), $pk, $isRawData);
      $data = [];
      $columns = $dal->getDB()->row($sql->build($data), $data);
      if ($dal->getDB()->getEngine() == 'OCI')
      {
        foreach ($columns as &$value)
        {
          if (is_resource($value)) $value = stream_get_contents($value);
          else if (is_a($value, 'OCI-Lob')) $value = $value->load();
        }
      }
      return $columns;
   }
   
   public function assignByID($pk)
   {
      $this->assign($this->getRow($pk, false));
      return $this;
   }

   /**
    *
    * @return type
    * @throws \Exception 
    * @access public
    */
   public function save(array $options = null)
   {
      $res = '';
      foreach ($this->dal as $dal)
      {
         if (!empty($ID)) 
         {
           $dal->setValues($values);
           $dal->setKeyValue($ID);
         }
         $res = $dal->save($options);
         $ID = isset($options['sequenceName']) ? $res : $dal->getKeyValue(false);
         $values = $dal->getValues();
      }
      return $res;
   }

   /**
    *
    * @return type
    * @throws \Exception 
    * @access public
    */
   public function insert(array $options = null)
   {
      $res = '';
      foreach ($this->dal as $dal)
      {
         if (!empty($ID)) 
         {
           $dal->setValues($values);
           $dal->setKeyValue($ID);
         }
         $res = $dal->insert($options);
         $ID = isset($options['sequenceName']) ? $res : $dal->getKeyValue(false);
         $values = $dal->getValues();
      }
      return $res;
   }

   /**
    *
    * @return type
    * @throws \Exception 
    * @access public
    */
   public function delete()
   {
      $res = 0;
      foreach (array_reverse($this->dal) as $dal) $res += $dal->delete();
      return $res;
   }

   /**
    *
    * @return type
    * @throws \Exception 
    * @access public
    */
   public function update()
   {
      $res = 0;
      foreach ($this->dal as $dal) $res += $dal->update();
      return $res;
   }

   /**
    *
    * @return type
    * @throws \Exception 
    * @access public
    */
   public function replace()
   {
      $res = 0;
      foreach ($this->dal as $dal) $res += $dal->replace();
      return $res;
   }
   
   /**
    * Checks weather this field was changed or not, compared to persistent data in database. Always true for not instantiated records.
    * @param string $field field name to check; if omitted, returns weather any field was changed or not
    */
   public function hasChanged($field = null)
   {
      if (!$field) 
      {
        foreach ($this->dal as $dal) if ($dal->hasChanged()) return true;
        return false;
      }
      if (isset($this->fields[$field]))
      {
         return $this->dal[$this->fields[$field]]->hasChanged($field);
      }
      throw new Core\Exception($this, 'ERR_BLL_1', $field, get_class($this));
   }

   /**
    * @return array
    */
   public function getChangedValues()
   {
      $values = array();
      foreach ($this->fields as $field=>$v) if ($this->hasChanged($field)) $values[$field] = $this->{$field};
      return $values;
   }

   /**
    *
    * @param type $field 
    * @access protected
    */
  protected function initNavigator($field)
  {
    if (isset($this->navigators[$field])) 
    {
      $this->navigators[$field]->initialize();
    } 
    else
    {
      $data = $this->dal[$this->navigationFields[$field]]->getNavigationField($field);
      $shortClass = explode('\\',$data['to']['bll']);
      $this->navigators[$field] = new ROWCollection(end($shortClass));
      $where = array();
      $fromFields = $data['from']['fields'];
      reset($fromFields);
      foreach ($data['to']['fields'] as $k=>$v) 
      {
        $where[$k] = $this->{current($fromFields)};
        next($fromFields);
      }
      $this->navigators[$field]->where($where, true);
    }
  }

  /**
   * @return DateTime current datetime
   */
  public static function now()
  {
    return new DateTime();
  }
}

?>
