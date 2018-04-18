<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core;

interface IService
{
   public function getByID($pk = null, $expire = null);
   public function updateByID($pk, array $values);
   public function deleteByID($pk);
   public function getOrchestra();
   public function save(IBLLTable $tb, $expire = null);
   public function insert(IBLLTable $tb, $expire = null);
   public function replace(IBLLTable $tb);
   public function update(IBLLTable $tb);
   public function delete(IBLLTable &$tb);
}

class Service implements IService
{
  // Error message templates
  const ERR_SVC_1 = 'The first argument should be an array, because Primary Key of [{var}] is multiple';
  const ERR_SVC_2 = 'The second argument of method [{var}] is forbidden because only the working with db is hapened';
  const ERR_SVC_3 = 'The BLL class "[{var}]" doesn\'t meet the Service "[{var}]"';

   protected $config = null;
   protected $cache = null;
   protected $objectName = null;
   protected $mode = null;

   public static $onBeforeSave = null;
   public static $onBeforeInsert = null;
   public static $onBeforeUpdate = null;
   public static $onBeforeReplace = null;
   public static $onBeforeDelete = null;
   public static $onAfterSave = null;
   public static $onAfterInsert = null;
   public static $onAfterUpdate = null;
   public static $onAfterReplace = null;
   public static $onAfterDelete = null;

   /**
    * Removes all stored cache
    * 
    * @access public
    * @static 
    */
   public static function cleanCache()
   {
      $cache = \CB::getInstance()->getCache();
      $key = \CB::getSiteUniqueID() . '_orm_bll_objects';
      $objs = $cache->get($key);
      if (is_array($objs))
      {
         foreach ($objs as $k => $data)
         {
            if (is_array($data)) foreach ($data as $cacheID => $pk) $cache->delete($cacheID);
         }
      }
      $cache->delete($key);
   }

   /**
    * Class constructor
    *
    * @param string $objectName 
    * @access public
    */
   public function __construct($objectName)
   {
      $this->config = \CB::getInstance()->getConfig();
      $this->cache = \CB::getInstance()->getCache();
      $this->objectName = $objectName;
   }

   /**
    * Returns the current name of the object
    *
    * @return string
    * @access public
    */
   public function getObjectName()
   {
      return $this->objectName;
   }

   /**
    * Get class object \ClickBlocks\DB\Orchestra
    *
    * @return \ClickBlocks\DB\Orchestra
    * @access public
    */
   public function getOrchestra()
   {
      $info = ORM::getInstance()->getORMInfo();
      $namespace = (($info['namespace'] != '\\') ? $info['namespace'] : '') . '\\';
      $class = substr($this->objectName, strlen($namespace) + 1);
      $class = $namespace . $info['classes'][$class]['orchestra'];
      return new $class();
   }

   /**
    *
    * @param type $param
    * @return \ClickBlocks\DB\Service 
    * @access public
    */
   public function __get($param)
   {
      if ($param == 'db' || $param == 'cache')
      {
         $this->mode = $param;
         return $this;
      }
   }

   /**
    * Get the service object by primary key
    *
    * @param array|int $pk
    * @param int $expire
    * @return \ClickBlocks\DB\BLLTable
    * @access public
    */
   public function getByID($pk = null, $expire = null)
   {
      if ($this->useCache() && $this->cacheExists($pk)) return $this->cacheGet($pk);
      $tb = new $this->objectName();
      if ((int)$expire) $tb->expire = (int)$expire;
      $tb->assignByID($pk);
      if ($this->useCache()) $this->cacheSet($tb, $pk);
      $this->mode = null;
      return $tb;
   }

   /**
    * Update service object with a primary key equal to $ pk, using the values ​​from the array $ values
    *
    * @param array|int $pk
    * @param array $values
    * @return \ClickBlocks\DB\Service 
    * @access public
    */
   public function updateByID($pk, array $values)
   {
      $key = $pk;
      $tb = new $this->objectName();
      $dal = $tb->getDAL();
      if (!is_array($pk)) $pk = array($dal->getKey(false) => $pk);
      if ($this->mode != 'cache')
      {
         $tpk = $pk;
         $tvalues = $values;
         $sql = (new SQLGenerator())->updateByID($this->objectName, $tpk, $tvalues, false);
         $dal->getDB()->execute($sql, array_merge($tvalues, $tpk));
      }
      if ($this->useCache())
      {
         if ($this->cacheExists($key)) $tb = $this->cacheGet($key);
         $tb->setValues($pk, false)->setValues($values, false);
         $this->cacheSet($tb);
      }
      $this->mode = null;
      return $this;
   }

   /**
    * Delete values with a primary key equal to $pk.
    * If set useCache - then clean it
    *
    * @param array|int $pk
    * @return \ClickBlocks\DB\Service 
    * @access public
    */
   public function deleteByID($pk)
   {
      $tb = new $this->objectName();
      $dal = $tb->getDAL();
      if (!is_array($pk)) $pk = array($dal->getKey($isRawData) => $pk);
      if ($this->mode != 'cache')
      {
         $tpk = $pk;
         $sql = (new SQLGenerator())->deleteByID($this->objectName, $tpk, false);
         $dal->getDB()->execute($sql, $tpk);
      }
      if ($this->useCache())
      {
         $tb->setValues($pk, false);
         $this->cacheDelete($tb);
      }
      $this->mode = null;
      return $this;
   }

   /**
    * Saves the current changes. This method decides whether to perform update or insert
    *
    * @param IBLLTable $tb
    * @param int $expire
    * @return \ClickBlocks\DB\Service
    * @throws \Exception  occurs if the parameter passed $expire but the cache is not used
    * @access public
    */
   public function save(IBLLTable $tb, $expire = null)
   {
      $this->checkOwn($tb);
      if (self::$onBeforeSave) (new Core\Delegate(self::$onBeforeSave))->call(array($tb));
      if ($this->mode != 'cache' || !$tb->getDAL()->isKeyFilled()) $tb->save();
      if ($this->useCache())
      {
         if ((int)$expire) $tb->expire = (int)$expire;
         $this->cacheSet($tb);
      }
      else if (!is_null($expire)) throw new Core\Exception($this, 'ERR_SVC_2', 'save');
      $this->mode = null;
      if (self::$onAfterSave) (new Core\Delegate(self::$onAfterSave))->call(array($tb));
      return $this;
   }

   /**
    * Добавляет данные в таблицу
    *
    * @param IBLLTable $tb
    * @param int $expire
    * @return \ClickBlocks\DB\Service
    * @throws \Exception 
    * @access public
    */
   public function insert(IBLLTable $tb, $expire = null)
   {
      $this->checkOwn($tb);
      if (self::$onBeforeInsert) (new Core\Delegate(self::$onBeforeInsert))->call(array($tb));
      $tb->insert();
      if ($this->useCache())
      {
         if ((int)$expire) $tb->expire = (int)$expire;
         $this->cacheSet($tb);
      }
      else if (!is_null($expire)) throw new Core\Exception($this, 'ERR_SVC_2', 'save');
      $this->mode = null;
      if (self::$onAfterInsert) (new Core\Delegate(self::$onAfterInsert))->call(array($tb));
      return $this;
   }

   /**
    * Обновляет данные в таблице
    *
    * @param IBLLTable $tb
    * @return \ClickBlocks\DB\Service 
    * @access public
    */
   public function update(IBLLTable $tb)
   {
      $this->checkOwn($tb);
      if (self::$onBeforeUpdate) (new Core\Delegate(self::$onBeforeUpdate))->call(array($tb));
      if ($this->mode != 'cache') $tb->update();
      if ($this->useCache()) $this->cacheSet($tb);
      $this->mode = null;
      if (self::$onAfterUpdate) (new Core\Delegate(self::$onAfterUpdate))->call(array($tb));
      return $this;
   }

   /**
    * Заменяет данные в таблице
    *
    * @param IBLLTable $tb
    * @return \ClickBlocks\DB\Service 
    * @access public
    */
   public function replace(IBLLTable $tb)
   {
      $this->checkOwn($tb);
      if (self::$onBeforeReplace) (new Core\Delegate(self::$onBeforeReplace))->call(array($tb));
      if ($this->mode != 'cache') $tb->replace();
      if ($this->useCache()) $this->cacheSet($tb);
      $this->mode = null;
      if (self::$onAfterReplace) (new Core\Delegate(self::$onAfterReplace))->call(array($tb));
      return $this;
   }

   /**
    * Удаляет данные из таблицы
    *
    * @param IBLLTable $tb
    * @return \ClickBlocks\DB\Service 
    * @access public
    */
   public function delete(IBLLTable &$tb)
   {
      $this->checkOwn($tb);
      if (self::$onBeforeDelete) (new Core\Delegate(self::$onBeforeDelete))->call(array($tb));
      if ($this->mode != 'cache') $tb->delete();
      if ($this->useCache()) $this->cacheDelete($tb);
      $this->mode = null;
      if (self::$onAfterDelete) (new Core\Delegate(self::$onAfterDelete))->call(array($tb));
      $tb = null;
      return $this;
   }

   /**
    * Проверяет, существует ли кэш
    *
    * @param array|int $pk
    * @return bool
    * @access protected
    */
   protected function cacheExists($pk)
   {
      return !$this->cache->isExpired($this->getCacheKey($pk));
   }

   /**
    * Получает результат кешированного запроса
    *
    * @param array|int $pk
    * @return type 
    * @access protected
    */
   protected function cacheGet($pk)
   {
      $str = $this->cache->get($this->getCacheKey($pk));
      $str = preg_replace('/buildBy";i:[\d]+/', 'buildBy";i:' . BLLTable::BUILD_BY_CACHE, $str);
      $str = str_replace('isInstantiated";b:0', 'isInstantiated";b:1', $str);
      $str = str_replace('isUpdated";b:1', 'isUpdated";b:0', $str);
      $str = str_replace('isSaved";b:1', 'isSaved";b:0', $str);
      $str = str_replace('isDeleted";b:1', 'isDeleted";b:0', $str);
      return unserialize($str);
   }

   /**
    * Кэширует запрос
    *
    * @param IBLLTable $tb
    * @return type 
    * @access protected
    */
   protected function cacheSet(IBLLTable $tb)
   {
      if (!$tb->getDAL()->isKeyFilled()) return;
      $pk = $tb->getDAL()->getKeyValue();
      $this->cache->set($this->getCacheKey($pk), serialize($tb), $tb->expire);
      $this->saveObjectIDInCache($pk);
   }

   /**
    * Удаляет кеш
    *
    * @param IBLLTable $tb
    * @access protected
    */
   protected function cacheDelete(IBLLTable $tb)
   {
      if (!$tb->getDAL()->isKeyFilled()) return;
      $key = $this->getCacheKey($tb->getDAL()->getKeyValue());
      $this->cache->delete($key);
      $this->deleteObjectIDFromCache($key);
   }

   /**
    * Получает строку из таблицы
    *
    * @param IBLLTable $tb
    * @param array|int $pk
    * @param bool $isRawData
    * @return type
    * @throws \Exception если первичных ключей несколько, а передан в 
    * @access protected
    */
   protected function getRow(IBLLTable $tb, $pk = null, $isRawData = false)
   {
      return $tb->getRow($pk, $isRawData);
   }

   /**
    *
    * @param array|int $pk 
    * @access private
    */
   private function saveObjectIDInCache($pk)
   {
      $key = $this->getObjectsCacheKey();
      $objs = $this->cache->get($key);
      $objs[get_class($this)][$this->getCacheKey($pk)] = $pk;
      $this->cache->set($key, $objs, $this->config['orm']['cacheBLLObjectExpire']);
   }

   /**
    *
    * @param int $cacheID 
    * @access private
    */
   private function deleteObjectIDFromCache($cacheID)
   {
      $key = $this->getObjectsCacheKey();
      $objs = $this->cache->get($key);
      if (is_array($objs)) unset($objs[get_class($this)][$cacheID]);
      $this->cache->set($key, $objs, $this->config['orm']['cacheBLLObjectExpire']);
   }

   /**
    * получить ключ кэша 
    *
    * @param array|int $pk
    * @return string 
    * @access private
    */
   private function getCacheKey($pk)
   {
      if (!is_array($pk)) $pk = (string)$pk;
      else foreach ($pk as $k => $v) $pk[$k] = (string)$v;
      return \CB::getSiteUniqueID() . '_orm_bll_object_' . $this->objectName . '_' . md5(serialize($pk));
   }

   /**
    * получить ключ кеша объекта
    *
    * @return string 
    * @access private
    */
   private function getObjectsCacheKey()
   {
      return \CB::getSiteUniqueID() . '_orm_bll_objects';
   }

   /**
    * 
    *
    * @param IBLLTable $tb
    * @throws \Exception The BLL class doesn't meet the Service 
    * @access private
    */
   private function checkOwn(IBLLTable $tb)
   {
      if ($this->objectName != '\\' . get_class($tb)) throw new Core\Exception($this, 'ERR_SVC_3', get_class($tb), get_class($this));
   }

   /**
    * проверка используется ли кэш
    *
    * @return bool 
    * @access private
    */
   private function useCache()
   {
      return (($this->config['orm']['useCacheForDataObjects'] || $this->mode == 'cache') && $this->mode != 'db');
   }
}

?>
