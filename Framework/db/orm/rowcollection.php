<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core,
    ClickBlocks\Utils,
    ClickBlocks\Utils\PHP,
    ClickBlocks\Exceptions;

/**
 * TODO:
 * - if table A inherited from table B, tables A and B has field with same name and it is aliased in DB.xml, this will not work (because rows array contains raw fields)
 * - refactor where()
 * - add ability to update/delete all rows in set
 * - add ability to insert new rows in one request
 * - add ability to somehow select through multiple join.. (like all Users's friends through many-to-many relationship table)
 */
class ROWCollection implements \Iterator, \ArrayAccess, \SeekableIterator, \Countable, \Serializable
{
  protected $config = null;
  protected $rows = null;
  protected $count = null;
  //protected $removed = array();
  protected $position = 0;
  protected $lastPosition = 0;
  //protected $hasChanged = false;
  /**
   * @var array currently used ONLY for insert function
   */
  protected $objects = null;
  /**
   * @var \ClickBlocks\DB\BLLTable
   */
  protected $lastObject = null;
  protected $objectTemplate = null;
  protected $bllClassName= null;
  protected $field = null;
  /**
   * @var \ClickBlocks\DB\ORMInfo
   */
  protected $info = null;
  protected $sqlGen = null;
  protected $precondition = null;
  protected $where = array();
  protected $order = null;
  protected $limit = null;
  protected $alias = array();
  protected $logicFields = array();
  protected $rawOutput = false;
  protected $emptyObjects = false;
  
  const ERR_1 = 'Class name required.';
  const ERR_2 = 'Invalid field in WHERE: "[{var}]".';
  const ERR_3 = 'Invalid field in ORDER: "[{var}]".';
  const ERR_4 = 'Invalid range in LIMIT: [{var}], [{var}].';
  const ERR_5 = 'Can only set appropriate Object or Array by offset.';
  const ERR_6 = 'Method "[{var}]" of collection does not exist';
  const ERR_SEEK_1 = 'Invalid seek position ([{var}])';

  public function __construct($bllClassName = null, $where = null, $order = null, $limit = null)
  {
    if (!$bllClassName) throw new Core\Exception($this, 'ERR_1');
    $bllClassName = PHP\Tools::getClassName($bllClassName);
    $this->bllClassName = $bllClassName;
    $this->sqlGen = new SQLGenerator();
    $this->info = $this->sqlGen->objInfo;
    $this->alias = $this->info->getTableAliasByClassName($bllClassName);
    foreach ($this->info->getClassParents($bllClassName) as $alias)
    {
       $this->logicFields += $this->info->getFieldNamesByTableAlias($alias[0], $alias[1]);
    }
    if ($where) $this->where($where);
    if ($order) $this->order($order);
    if ($limit) $this->limit($limit);
  }
  
  public static function select($bllClassName = null)
  {
    if ($bllClassName) 
        return new static($bllClassName);
    else 
        return new static();
  }

  public function initialize()
  {
    $this->rows = null;
    $this->count = null;
    $this->position = 0;
    $this->lastPosition = 0;
    $this->lastObject = null;
  }
  
  protected function getTableInfo($dbAlias, $tableAlias)
  {
     return array(
        'db'=>$this->info->getDBNameByDBAlias($dbAlias),
        'table'=>$this->info->getTableNameByTableAlias($dbAlias,$tableAlias)
        );
  }

  public function getRows()
  {
    $this->execute();
    return $this->rows;
  }
  
  /*public function hasChanged()
  {
    return $this->hasChanged;
  }*/

  /**
   * Assign Where constraint to result set. This will overwrite where OR precondition that was previously set by where().
   * @param string|array $where
   * @param bool $setPrecondition - if true - will set precondition that is set separately from main where; it works the same as main $where, except that it is set separately
   * @return \ClickBlocks\DB\ROWCollection
   * @throws Core\Exception if incorrect field was referenced in $where
   */
  public function where($where = null, $setPrecondition = false)
  {
    if (is_array($where))
      foreach ($where as $k=>$v) {
        if (!isset($this->logicFields[$k])) throw new Core\Exception($this, 'ERR_2', $k);
      }
    if ($setPrecondition) 
    {
      if ($where !== $this->precondition) $this->initialize();
      $this->precondition = $where;
    }
    else
    {
      if ($where !== $this->where) $this->initialize();
      $this->where = $where;
    }
    return $this;
  }
  
  /**
   * Set Ordering of result set. This will overwrite all ordering previously set by order().
   * @param string|array $field Field alias to order by or array in form [field=>ascending]
   * @param bool $descending true for descending order
   * @return \ClickBlocks\DB\ROWCollection
   * @throws Core\Exception
   */
  public function order($field, $ascending = true)
  {
    if (!is_array($field)) $field = array($field=>$ascending);
    foreach ($field as $k=>$v) 
    {
      if (!isset($this->logicFields[$k])) throw new Core\Exception($this, 'ERR_3', $k);
    }
    $this->order = $field;
    $this->initialize();
    return $this;
  }
  
  /**
   * Set Limit for result set. This will overwrite limit previously set by limit().<br/>
   * if $offset and $count are numbers and set - they will be used according to name <br/>
   * if $offset is number and $count is not set, $offset will be used as $count
   * if $offset is array it will be used in form array(0=>offset, 1=>count)
   * @param int|array $offset
   * @param int $count
   * @return \ClickBlocks\DB\ROWCollection
   */
  public function limit($offset, $count = null)
  {
    if (!$offset && !$count) return $this;
    if (is_array($offset)) 
    {
      $count = $offset[1];
      $offset = $offset[0];
    }
    $normalize = function(array $lim) { return count($lim)==1 ? array(0,$lim[0]) : $lim; };
    $old = is_array($this->limit) ? $normalize($this->limit) : null;
    $this->limit = array((int)$offset);
    if ($count !== NULL) $this->limit[] = (int)$count;
    $new = $normalize($this->limit);
    if ($this->rows !== NULL && $old && $new[0]>=$old[0] && ($new[0]+$new[1])<=($old[0]+$old[1])) 
    {
      $this->rows = array_slice($this->rows, $new[0]-$old[0], $old[0]+$old[1]-$new[0]-$new[1]);
      $this->count = count($this->rows);
      $this->objects = array();
      $this->position = $this->lastPosition = 0;
    }
    else $this->initialize();
    return $this;
  }
  
  /**
   * @param bool $mode if true - value arrays will be returned instead of BLL objects, false - default mode
   * @return \ClickBlocks\DB\ROWCollection
   */
  public function setRawOutput($mode = true)
  {
    $this->rawOutput = (bool)$mode;
    return $this;
  }
  
  /**
   * Defines what to return when using offsetGet on non-existing offset (this has no affect if RawOutput is enabled)
   * @param bool $mode if true - return empty BLL, false - return null (default)
   * @return \ClickBlocks\DB\ROWCollection
   */
  public function allowEmptyObjects($mode = true)
  {
    $this->emptyObjects = (bool)$mode;
    return $this;
  }

  public function count()
  {
    $this->execute(true);
    return $this->count;
  }

  public function seek($position)
  {
    $this->execute();
    $this->position = $position;
    if (!$this->valid()) throw new Core\Exception($this, 'ERR_SEEK_2', $this->position);
  }

  public function rewind()
  {
    $this->position = 0;
    return true;
  }

  /**
   * @return BLLTable
   */
  public function current()
  {
    $this->execute();
    return $this->offsetGet($this->key(), true);
  }

  public function key()
  {
    return $this->position;
  }

  public function next()
  {
    $this->position++;
    return true;
  }

  public function valid()
  {
    $this->execute();
    return isset($this->rows[$this->key()]);
  }

  /**
   * Add object for insertion with insert()
   * @param int|string $offset
   * @param array|BLLTable $value
   * @throws Core\Exception
   */
  public function offsetSet($offset, $value)
  {
    throw new Core\Exception('Not implemented yet...');
    if (is_array($value))
    {
      $bll = $this->getNewObject();
      $bll->setValues($value);
    }
    else if (is_object($value))
    {
      $class = $this->info->getNamespace().'\\'.$this->bllClassName;
      if (!($value instanceof $class)) throw new Core\Exception($this,'ERR_5');
      $bll = $value;
    }
    $this->lastPosition = $offset;
    $this->lastObject = $bll;
    $this->objects[$offset] = $bll;
  }

  public function offsetExists($offset)
  {
    return isset($this->rows[$offset]);
  }

  public function offsetUnset($offset)
  {
    unset($this->rows[$offset]);
    $this->count = count($this->rows);
  }
  
  /**
   * returns new BLL object; cloning is used to speed up object instantiation
   * @return \ClickBlocks\DB\className
   */
  protected function getNewObject()
  {
    if ($this->objectTemplate) return clone $this->objectTemplate;
    $className = $this->info->getNamespace().'\\'.$this->bllClassName;
    $bll = new $className();
    $this->objectTemplate = $bll;
    return $bll;
  }

  /**
   * @return BLLTable
   */
  public function offsetGet($offset)
  {
    $this->execute();
    if ($this->rawOutput) return $this->rows[$offset];
    if ($offset === $this->lastPosition && $this->lastObject) return $this->lastObject;
    $this->lastPosition = $offset;
    if (!isset($this->rows[$offset]) && $this->emptyObjects == false) return NULL;
    $row = isset($this->rows[$offset]) ? $this->rows[$offset] : array();
    $bll = $this->getNewObject();
    if (!empty($row['`_`']['isNew'])) $bll->setValues($row);
    else $bll->assign($row);
    $this->lastObject = $bll;
    return $bll;
  }

  public function __set($param, $value)
  {
    $this->execute();
    $obj = $this[$this->lastPosition];
    if (is_object($obj)) $obj->{$param} = $value;
    else $obj[$param] = $value;
  }

  public function __get($param)
  {
    $this->execute();
    $obj = $this[$this->lastPosition];
    if (is_object($obj)) return $obj->{$param};
    return $obj[$param];
  }

  public function __call($method, $params)
  {
    $this->execute();
    $obj = $this[$this->lastPosition];
    if (is_object($obj)) return call_user_func_array(array($obj, $method), $params);
    throw new Core\Exception($this, 'ERR_6', $method);
  }

  public function serialize()
  {
    $data = get_object_vars($this);
    unset($data['objects']);
    unset($data['info']);
    unset($data['config']);
    return serialize($data);
  }

  public function unserialize($data)
  {
    $data = unserialize($data);
    foreach ($data as $k => $v) $this->{$k} = $v;
  }

  public function delete($ignoreBusinessLogic = false)
  {
    if ($ignoreBusinessLogic && $this->rows === NULL && $this->limit === NULL) 
    {
      $sqlp = $this->getSQL();
      $db = ORM::getInstance()->getDB($this->alias[0]);
      $t = array();
      foreach ($sqlp['TABLES'] as $table) $t[] = $table['alias'];
      $sql = 'DELETE '.implode(',',$t).' FROM '.$sqlp['FROM'].' '.$sqlp['JOIN'].' '.($sqlp['WHERE'] ? 'WHERE '.$sqlp['WHERE'] : '');
      //print_r($sql);
      $db->execute($sql, $sqlp['PARAMS']);
      /*$fields = $sqlp['FIELDS'];
      if ($this->rows !== NULL || $this->limit !== NULL) 
      {
        $this->execute();
        $IDs = array();
        //$where = array();
        foreach ($this->rows as $row) {
          
        }
      }*/
    } 
    else 
    {
      $this->massAction('delete');
    }
    $this->initialize();
  }
  
  /*public function save()
  {
    $this->massAction('save');
    $this->initialize();
  }*/
  
  /*public function update()
  {
    $this->massAction('update');
  }*/
  
  public function insert()
  {
    foreach ($this->objects as $bll) $bll->insert();
    $this->initialize();
  }

  protected function massAction($method /*[, ...]*/)
  {
    if ($this->rows === NULL) return;
    $params = func_get_args();
    array_shift($params);
    $rawMode = $this->rawOutput;
    $this->setRawOutput(false);
    foreach ($this as $bll) {
      call_user_func_array(array($bll, $method), $params);
    }
    $this->setRawOutput($rawMode);
  }
  
  public function loadData()
  {
    $this->execute();
  }
  
  protected function getSQL()
  {
    $db = ORM::getInstance()->getDB($this->alias[0]);
    $tables = $this->info->getClassParents($this->bllClassName);
    $fields = array();
    foreach ($tables as $i=>$alias)
    {
      $tables[$i] = array(
          'db'=>$db->wrap($this->info->getDBNameByDBAlias($alias[0])),
          'table'=>$db->wrap($this->info->getTableNameByTableAlias($alias[0],$alias[1])),
          'fields'=>array('t'.$i.'.*'),
          'alias'=>'t'.$i,
          'pk'=>$this->info->getKeyInfoByTableAlias($alias[0],$alias[1]),
          ); 
      $_fields = $this->info->getFieldNamesByTableAlias($alias[0], $alias[1]);
      foreach ($_fields as $alias=>$name) {
        $fields[$alias] = $tables[$i]['alias'].'.'.$db->wrap($name);
      }
    }
    if ($db->getEngine() == 'mssql')
    {
       foreach ($tables as $k=>$v) $tables[$k]['db'] .= '.[dbo]';
    }
    $params = $w = array();
    foreach (array('where','precondition') as $type)
    {
      if (is_array($this->{$type}))
      {
        foreach ($this->{$type} as $fieldAlias => $value)
        {
          if ($value instanceof SQLExpression) 
          {
            $w[] = $fields[$fieldAlias] . ' = '.$value;
          }
          else
          {
            $params[] = $value;
            $w[] = $fields[$fieldAlias] . ' = ?';
          }
        }
      }
      else if (is_string($this->{$type}))
      {
        $w[] = $this->{$type};
      }
    }
    $selectTables = $innerJoins = array();
    $mainTable = reset($tables);
    foreach($tables as $table)
    {
      $selectTables[] = implode(',', $table['fields']);
      if ($table == $mainTable) {
        $fromTables[] = "{$table['db']}.{$table['table']} {$table['alias']}";
      } 
      else
      {
        $on = array();
        foreach ($mainTable['pk'] as $key=>$info) {
           $on[] = $mainTable['alias'].'.'.$db->wrap($info['name']).' = '.$table['alias'].'.'.$db->wrap($info['name']);
        }
        $innerJoins[] = "INNER JOIN {$table['db']}.{$table['table']} {$table['alias']} ON " . implode(' AND ',$on);
      }
    }
    $result = array(
        'SELECT'=>implode(', ',$selectTables),
        'FROM'=>implode(', ',$fromTables),
        'JOIN'=>implode(' ', $innerJoins),
        'WHERE'=>implode(' AND ', $w),
        'PARAMS'=>$params,
        'FIELDS'=>$fields,
        'TABLES'=>$tables,
    );
    if ($this->order && count($this->order)) {
      $o = array();
      foreach ($this->order as $k=>$v) $o[] = $fields[$k].' '.(((bool)$v && strcasecmp($v, 'desc')) ? 'ASC' : 'DESC');
      $result['ORDER'] = 'ORDER BY '.implode(',', $o);
    }
    if ($this->limit && $db->getEngine() == 'mysql') {
      $result['LIMIT'] = 'LIMIT '.implode(', ', $this->limit);
    }
    return $result;
  }

  protected function execute($count = false)
  {
    if ($this->rows !== NULL) return;
    if ($count && $this->count !== NULL) return;
    $db = ORM::getInstance()->getDB($this->alias[0]);
    
    $sqlp = $this->getSQL();
    
    $sql = 'SELECT '.($count ? 'COUNT(*)' : $sqlp['SELECT']).' FROM '.$sqlp['FROM'].' '.$sqlp['JOIN'].' '.($sqlp['WHERE'] ? 'WHERE '.$sqlp['WHERE'] : '').' '.($count ? '' : isset($sqlp['ORDER']) ? $sqlp['ORDER'] : '').' '.(isset($sqlp['LIMIT']) ? $sqlp['LIMIT'] : '');
    
    //echo $sql.PHP_EOL;
    //print_r($params);
    
    if ($count) 
    {
      $this->count = $db->cell($sql, $sqlp['PARAMS']);
    }
    else
    {
      $this->rows = $db->rows($sql, $sqlp['PARAMS']);
      $this->count = count($this->rows);
    }
  }

  
}

?>
