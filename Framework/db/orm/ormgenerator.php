<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core,
    ClickBlocks\Utils;

class ORMGenerator
{
   private $config = null;
   private $info = null;

   public function __construct()
   {
      $this->config = \CB::getInstance()->getConfig();
   }

   public function generateXML($namespace = 'ClickBlocks\\DB', $xmlfile = null)
   {
      if (!$xmlfile) $xmlfile = \CB::dir('engine') . '/db.xml';
      $dom = new \DOMDocument('1.0', 'utf-8');
      $dom->formatOutput = true;
      $root = $dom->createElement('Config');
      $nodeMapping = $dom->createElement('Mapping');
      $nodeMapping->setAttribute('Namespace', $namespace);
      $nodeClasses = $dom->createElement('Classes');
      $info = $this->info();
      foreach (ORM::getInstance()->getDBList() as $db)
      {
         $dbName = $db->getDBName();
         $alias = $info[$dbName]['alias'];
         $scheme = $db->getEngine();
         $nodeDB = $dom->createElement('DataBase');
         $nodeDB->setAttribute('DB', $dbName);
         $nodeDB->setAttribute('Name', $alias);
         $nodeDB->setAttribute('Driver', $scheme);
         $nodeModelPhysical = $dom->createElement('ModelPhysical');
         $nodeModelLogical = $dom->createElement('ModelLogical');
         $nodeTables = $dom->createElement('Tables');
         $nodeLogicTables = $dom->createElement('Tables');
         foreach ((array)$info[$dbName]['tables'] as $table => $data)
         {
            $pc = $cc = 0;
            $nodeLogicTable = $dom->createElement('Table');
            $nodeLogicTable->setAttribute('Name', $table);
            $nodeLogicTable->setAttribute('Repository', $table);
            $nodeFields = $dom->createElement('Fields');
            $nodePhysicalFields = $dom->createElement('Fields');
            $nodeLogicProperties = $dom->createElement('LogicProperties');
            $nodeNavigations = $dom->createElement('NavigationProperties');
            $nodeProperties = $dom->createElement('Properties');
            $nodeNames = array();
            foreach (array('dependences', 'foreignkeys') as $mode)
            {
              if (empty($info[$dbName][$mode][$table])) continue;
              foreach ((array)$info[$dbName][$mode][$table] as $name=>$dd)
              {
                if (!empty($this->config[$alias]['navigationPropertyPattern'])) $checkSubPat = ($mode=='dependences') ? 'from' : 'to';
                else $checkSubPat = false;
                if ($checkSubPat && preg_match($this->config[$alias]['navigationPropertyPattern'], $name, $m) && ($m[$checkSubPat]) ) 
                {
                  $nodeName = $m[$checkSubPat];
                }
                else if ($alias == $info[$dd['toDB']]['alias'] && $table == $dd['toTable'])
                {
                  if ((int)$dd['multiplicity'] == 0) $nodeName = 'parent';
                  else $nodeName = 'children';
                }              
                else 
                {
                  $nodeName = $dd['toTable'];
                }
                if (empty($nodeNames[$nodeName])) $nodeNames[$nodeName] = 0;
                $nodeNames[$nodeName] += 1;
                if ($nodeNames[$nodeName] > 1) $nodeName .= (int)$nodeNames[$nodeName];
                $nodeProperty = $dom->createElement('Property');
                $nodeProperty->setAttribute('Name', self::getNavigationPropertyName($nodeName));

                $nodeProperty->setAttribute('Multiplicity', array_key_exists('multiplicity', $dd) ? (int)$dd['multiplicity'] : 0);
                $nodeProperty->setAttribute('Insertable', 0);
                $nodeProperty->setAttribute('Updateable', 0);
                $nodeProperty->setAttribute('Deleteable', 0);
                $nodeProperty->setAttribute('Readable', 1);
                $nodeFrom = $dom->createElement('From');
                $nodeFrom->setAttribute('Repository', $this->getRepositoryName($alias, $table));
                foreach ($dd['fromFields'] as $field)
                {
                  $nodeField = $dom->createElement('Field');
                  $nodeField->setAttribute('Name', $field);
                  $nodeFrom->appendChild($nodeField);
                }
                $nodeProperty->appendChild($nodeFrom);
                $nodeTo = $dom->createElement('To');
                $nodeTo->setAttribute('Repository', $this->getRepositoryName($info[$dd['toDB']]['alias'], $dd['toTable']));
                foreach ($dd['toFields'] as $field)
                {
                  $nodeField = $dom->createElement('Field');
                  $nodeField->setAttribute('Name', $field);
                  $nodeTo->appendChild($nodeField);
                }
                $nodeProperty->appendChild($nodeTo);
                $nodeSelect = $dom->createElement('Select');
                $nodeSelect->setAttribute('Output', 'object');
                $nodeProperty->appendChild($nodeSelect);
                $nodeNavigations->appendChild($nodeProperty);
                $nodeProperty = $dom->createElement('Property');
                $nodeProperty->setAttribute('Name', $this->getPropertyName($alias, $table, self::getNavigationPropertyName($nodeName)));
                $nodeProperty->setAttribute('Navigation', 1);
                $nodeProperties->appendChild($nodeProperty);
              }
            }
            $nodeTable = $dom->createElement('Table');
            $nodeTable->setAttribute('Name', $table);
            $nodeTable->setAttribute('Engine', $data['engine']);
            $nodeTable->setAttribute('Charset', $data['charset']);
            $nodePK = $dom->createElement('PrimaryKey');
            if (!empty($data['key'])) {
                foreach ((array)$data['key'] as $key)
                {
                  $nodeRef = $dom->createElement('Ref');
                  $nodeRef->setAttribute('Name', $key);
                  $nodePK->appendChild($nodeRef);
                }
            }
            $nodeTable->appendChild($nodePK);
            $nodeClass = $dom->createElement('Class');
            $className = self::getClassName($table);
            $nodeClass->setAttribute('Name', $className);
            $nodeClass->setAttribute('Repository', $this->getRepositoryName($alias, $table));
            foreach (array('Service','Orchestra','Collection') as $type)
            {
              if (empty($this->config[$alias]['skip'.$type])) $nodeClass->setAttribute($type, $type . $className);
            }
            foreach ($data['fields'] as $field)
            {
               $nodeField = $dom->createElement('Field');
               $nodeField->setAttribute('Name', $field['column']);
               $nodeField->setAttribute('Type', $field['type']);
               if ((int)$field['isNullable'] > 0) $nodeField->setAttribute('Null', $field['isNullable']);
               if ((int)$field['isAutoincrement'] > 0) $nodeField->setAttribute('Autoincrement', 1);
               if ((int)$field['isUnsigned'] > 0) $nodeField->setAttribute('Unsigned', 1);
               if ((int)$field['maxLength'] > 0) $nodeField->setAttribute('Length', $field['maxLength']);
               if ((int)$field['precision'] > 0) $nodeField->setAttribute('Precision', $field['precision']);
               if (is_array($field['set'])) $nodeField->setAttribute('Collection', htmlspecialchars(implode(',', $field['set'])));
               if (strlen($field['default'])) $nodeField->setAttribute('Default', htmlspecialchars($field['default']));
               $nodePhysicalFields->appendChild($nodeField);
               $nodeField = $dom->createElement('Field');
               $nodeField->setAttribute('Name', $field['column']);
               $nodeField->setAttribute('Link', $field['column']);
               $nodeFields->appendChild($nodeField);
               $nodeProperty = $dom->createElement('Property');
               $nodeProperty->setAttribute('Name', $this->getPropertyName($alias, $table, $field['column']));
               $nodeProperties->appendChild($nodeProperty);
            }
            $nodeTable->appendChild($nodePhysicalFields);
            $nodeLogicTable->appendChild($nodeFields);
            $nodeLogicTable->appendChild($nodeNavigations);
            $nodeLogicTable->appendChild($nodeLogicProperties);
            $nodeClass->appendChild($nodeProperties);
            $nodeTables->appendChild($nodeTable);
            $nodeLogicTables->appendChild($nodeLogicTable);
            $nodeClasses->appendChild($nodeClass);
         }
         $nodeRoutines = $dom->createElement('Routines');
         foreach ($info[$dbName]['routines'] as $routine)
         {
           $nodeRoutine = $dom->createElement($routine['type'] == 'PROCEDURE' ? 'Procedure' : 'Function');
           $nodeRoutine->setAttribute('Name', $routine['name']);
           $nodeRoutine->setAttribute('Link', $routine['name']);
           $nodeRoutines->appendChild($nodeRoutine);
         }
         $nodeModelPhysical->appendChild($nodeTables);
         $nodeModelLogical->appendChild($nodeLogicTables);
         $nodeModelLogical->appendChild($nodeRoutines);
         $nodeDB->appendChild($nodeModelPhysical);
         $nodeDB->appendChild($nodeModelLogical);
         $root->appendChild($nodeDB);
      }
      $nodeMapping->appendChild($nodeClasses);
      $root->appendChild($nodeMapping);
      $dom->appendChild($root);
      $dir = \CB::dir('engine');
      if (!is_dir($dir)) mkdir($dir, 0775, true);
      file_put_contents($xmlfile, $dom->saveXML());
   }

   public function generateFiles($tableAlias = null)
   {
      $this->info = ORM::getInstance()->getORMInfo();
      $tplDAL = new Core\TemplateOld();
      $tplDAL->setTemplate('dal', \CB::getRoot() . '/Framework/tpl/orm/daltable.tpl');
      $tplBLL = new Core\TemplateOld();
      $tplBLL->setTemplate('bll', \CB::getRoot() . '/Framework/tpl/orm/blltable.tpl');
      $tplSVC = new Core\TemplateOld();
      $tplSVC->setTemplate('svc', \CB::getRoot() . '/Framework/tpl/orm/service.tpl');
      $tplORC = new Core\TemplateOld();
      $tplORC->setTemplate('orc', \CB::getRoot() . '/Framework/tpl/orm/orchestra.tpl');
      $tplCOL = new Core\TemplateOld();
      $tplCOL->setTemplate('orc', \CB::getRoot() . '/Framework/tpl/orm/collection.tpl');
      $tplSPS = new Core\TemplateOld();
      $tplSPS->setTemplate('sps', \CB::getRoot() . '/Framework/tpl/orm/sps.tpl');
      $tplDAL->namespace = $tplBLL->namespace = $tplCOL->namespace = $tplSVC->namespace = $tplORC->namespace = $tplSPS->namespace = $this->info['namespace'];
      $i = 0;
      foreach ($this->info['model'] as $dbalias => $dbs)
      {
         $isAdditionalDB = (0 != $i++);
         foreach ($dbs['tables'] as $table => $data)
         {
            if ($tableAlias && $tableAlias != $table) continue;
            $methods = $properties = array();
            foreach ($data['fields'] as $fieldAlias => $info)
            {
               $properties[$fieldAlias] = ' * @property ' . $info['phpType'] . ' $' . $fieldAlias;
               foreach (array('setter', 'getter', 'get', 'set') as $methodName)
               {
                  if (!isset($info[$methodName])) continue;
                  $method = '    protected function _' . $methodName . ucfirst($fieldAlias) . '($value)' . PHP_EOL . '    {' . PHP_EOL . '        ';
                  $method .= $this->getMethodCode($info[$methodName]);
                  $method .= PHP_EOL . '    }' . PHP_EOL;
                  $methods[$methodName . ucfirst($fieldAlias)] = $method;
               }
            }
            foreach ($data['logicFields'] as $fieldAlias => $info)
            {
               $properties[$fieldAlias] = ' * @property mixed $' . $fieldAlias;
               foreach (array('setter', 'getter', 'get', 'set') as $methodName)
               {
                  if (!isset($info[$methodName])) continue;
                  $method = '    protected function _' . $methodName . ucfirst($fieldAlias) . '(' . (($methodName == 'get') ? '' : '$value') . ')' . PHP_EOL . '    {' . PHP_EOL . '        ';
                  $method .= $this->getMethodCode($info[$methodName]);
                  $method .= PHP_EOL . '    }' . PHP_EOL;
                  $methods[$methodName . ucfirst($fieldAlias)] = $method;
               }
            }
           /* foreach ($data['navigationFields'] as $fieldAlias => $info)
            {
               $navProperties[$class][$fieldAlias] = ' * @property navigation $' . $fieldAlias;
            }*/
            $tplDAL->class = 'DAL' . self::getClassName($table);
            $tplDAL->dbAlias = $dbalias;
            $tplDAL->logicTableName = $table;
            $tplDAL->methods = (count($methods)) ? PHP_EOL . PHP_EOL . implode(PHP_EOL, $methods) : PHP_EOL;
            $tplDAL->properties = implode(PHP_EOL, $properties) . PHP_EOL;
            $file = self::getFileName(\CB::dir('dal') . ($isAdditionalDB ? '/' . $dbalias : ''), $table);
            if (!is_file($file) || !$this->rewriteClass($file, $tplDAL)) self::saveClassToFile($file, $tplDAL->render());
         }
      }
      foreach ($this->info['classes'] as $class => $data)
      {
         $dbalias = $data['db'];
         $table = $data['table']['alias'];
         if ($tableAlias && $tableAlias != $table) continue;
         $parent = '\ClickBlocks\DB\BLLTable';
         $properties = $methods = $sps = array();
         $tableInfo = $this->info['model'][$dbalias]['tables'][$table];
         foreach ($data['table']['fields'] as $field => $className)
         {
            $fieldInfo = $tableInfo['fields'][$field];
            $type = $this->getRelativeClassName($fieldInfo['phpType'], $tplBLL->namespace);
            $properties[$field] = ' * @property ' . $type . ' $' . $field . ' ' . $fieldInfo['type'] .
                ($fieldInfo['length'] ? '(' . $fieldInfo['length'] . (
                    $fieldInfo['precision'] ? ',' . $fieldInfo['precision'] : ''
                ) . ')' : '') .
                (!empty($fieldInfo['collection']) ? '(' . $fieldInfo['collection'] . ')' : '') .
                ($fieldInfo['null'] ? ' nullable' : '') .
                (!empty($fieldInfo['default']) ? ' default ' . $fieldInfo['default'] : '');
         }
         foreach ($data['table']['logicFields'] as $field => $className)
         {
            $properties[$field] = ' * @property mixed $' . $field;
         }
         foreach (array('procedures', 'functions') as $type) 
         {
           if (empty($this->info['model'][$dbalias]['tables'][$table][$type])) continue;
           foreach ((array)$this->info['model'][$dbalias]['tables'][$table][$type] as $sp)
           {
             $db = ORM::getInstance()->getDB($dbalias);
             $args = $db->getRoutine($sp['link'], $type == 'functions');
             $args = $args['args']; $vars = $tmp = array();
             foreach ($args as $arg => $t) 
             {
               if ($t[1] == 'IN') $tmp[] = '\'' . $arg . '\' => $params[\'' . $arg . '\']';
               else if ($t[1] == 'INOUT') 
               {
                 $tmp[] = '\'@' . $arg . '\' => $params[\'' . $arg . '\']';
                 $vars[] = '@' . $arg;
                 $flag = true;
               }
               else
               {
                 $flag = true;             
                 $tmp[] = '\'@' . $arg . '\'';
                 $vars[] = '@' . $arg;
               }
             }
             $method = '    public static function ' . $sp['name'] . '(array $params)' . PHP_EOL . '    {' . PHP_EOL . '        ';
             $method .= ($type == 'functions' ? 'return ' : '') . '\ClickBlocks\DB\ORM::getInstance()->getDB(\'' . $dbalias . '\')->' . ($type == 'functions' ? 'sf' : 'sp') . '(\'' . $sp['link'] . '\', array(' . implode(', ', $tmp) . '));';
             if ($vars) $method .= PHP_EOL . '        return \ClickBlocks\DB\ORM::getInstance()->getDB(\'' . $dbalias . '\')->' . (count($vars) > 1 ? 'row' : 'col') . '(\'SELECT ' . implode(', ', $vars) . '\');';
             $method .= PHP_EOL . '  }' . PHP_EOL;
             $sps[$sp['name']] = $method;
             if (isset($this->info['model'][$dbalias]['tables'][$sp['table']]))
             {
               $methods[$sp['name']] = '    public static function ' . $sp['name'] . '(array $params)' . PHP_EOL . '    {' . PHP_EOL . '        ' . ($type == 'functions' ? 'return ' : '') . 'SPS::' . $sp['name'] . '($params);' . PHP_EOL . '    }' . PHP_EOL;
             }
           }
         }
         foreach ($data['table']['navigationFields'] as  $field => $className)
         {
            $type = $this->getRelativeClassName('\\' . $tableInfo['navigationFields'][$field]['to']['bll'], $tplBLL->namespace);
            if ($tableInfo['navigationFields'][$field]['multiplicity']) {
                $type .= '[]|' . $this->getRelativeClassName('\ClickBlocks\DB\RowCollection', $tplBLL->namespace);
            }
            $properties[$field] = " * @property {$type} \${$field}";
         }
         $inherit = $this->info['model'][$dbalias]['tables'][$table]['inherit'];
         if ($inherit)
         {
            $parent = '\ClickBlocks\DB\BLLTable';
            foreach ($this->info['classes'] as $cls => $info)
            {
               if ($cls != $class && $inherit[1] == $info['table']['alias'])
               {
                  $parent = $cls;
                  break;
               }
            }
            if ($parent == '\ClickBlocks\DB\BLLTable')
            {
              
            }
         }
         $tplBLL->class = $class;
         $tplBLL->dalclass = 'DAL' . self::getClassName($table);
         $tplBLL->parent = $parent;
         $tplBLL->methods = (count($methods)) ? PHP_EOL . PHP_EOL . implode(PHP_EOL, $methods) : PHP_EOL;
         $tplBLL->properties = implode(PHP_EOL, $properties) . PHP_EOL;
         $aliasFolder = $isAdditionalDB ? '/'.$dbalias : '';
         $file = self::getFileName(\CB::dir('bll') . $aliasFolder, $table);
         if (!is_file($file) || !$this->rewriteClass($file, $tplBLL)) self::saveClassToFile($file, $tplBLL->render());
         if ($data['collection'])
         {
            $tplCOL->class = $data['collection'];
            $tplCOL->bllClass = $class;
            $file = self::getFileName(\CB::dir('collections'), $class);
            if (!is_file($file) || !$this->rewriteClass($file, $tplCOL)) self::saveClassToFile($file, $tplCOL->render());
         }
         if ($data['service'])
         {
            $tplSVC->class = $data['service'];
            $tplSVC->objectName = (($this->info['namespace'] != '\\') ? '\\' . $this->info['namespace'] : '') . '\\' . $class;
            $file = self::getFileName(\CB::dir('services'), $class);
            if (!is_file($file) || !$this->rewriteClass($file, $tplSVC)) self::saveClassToFile($file, $tplSVC->render());
         }
         if ($data['orchestra'])
         {
            $tplORC->table = ORM::getInstance()->getDB($dbalias)->wrap($table);
            $tplORC->class = $data['orchestra'];
            $tplORC->className = (($this->info['namespace'] != '\\') ? '\\' . $this->info['namespace'] : '') . '\\' . $class;
            $file = self::getFileName(\CB::dir('orchestras') . $aliasFolder, $class);
            if (!is_file($file) || !$this->rewriteClass($file, $tplORC)) self::saveClassToFile($file, $tplORC->render());
         }
         $tplSPS->methods = (count($sps)) ? implode(PHP_EOL, $sps) : PHP_EOL;
         $file = self::getFileName(\CB::dir('sps') . $aliasFolder, 'sps');
         if (!is_file($file) || !$this->rewriteClass($file, $tplSPS)) self::saveClassToFile($file, $tplSPS->render());
      }
   }

   private function getRelativeClassName($fullClass, $namespace)
   {
       if (stripos($fullClass, '\\' . $namespace) === 0) $fullClass = substr($fullClass, strlen($namespace) + 2);
       return $fullClass;
   }

   public function info()
   {
      $info = array();
      foreach (ORM::getInstance()->getDBList() as $alias => $db)
      {
         $ignoredTables = explode(',',  strtolower(isset($this->config[$alias]['ignoreTables']) ? $this->config[$alias]['ignoreTables'] : ''));
         $dbName = $db->getDBName();
         $info[$dbName]['alias'] = $alias;
         $tables = $db->getTableList();
         foreach ($tables as $table) {
            $info[$dbName]['tables_lc'][strtolower($table)]['name'] = $table;
         }
         foreach ($tables as $table)
         {
            if (in_array(strtolower($table), $ignoredTables)) continue;
            $ddl = $db->getTableInfo($table);
            $info[$dbName]['tables'][$table]['fields'] = $db->getColumnsInfo($table);
            $info[$dbName]['tables'][$table]['engine'] = isset($ddl['engine']) ? $ddl['engine'] : null;
            $info[$dbName]['tables'][$table]['charset'] = isset($ddl['charset']) ? $ddl['charset'] : null;
            foreach ($info[$dbName]['tables'][$table]['fields'] as $field => $v) if ($v['isPrimaryKey']) {
               $info[$dbName]['tables'][$table]['key'][$field] = $field;
               $info[$dbName]['tables_lc'][strtolower($table)]['key'][$field] = $field;
            }
            if (count($ddl['constraints']))
            {
               foreach ($ddl['constraints'] as $name => $constraint)
               {
                  if (is_array($constraint['reference']['table'])) 
                  {
                    $toDB = $constraint['reference']['table'][0];
                    $toTable = $constraint['reference']['table'][1];
                  }
                  else 
                  {
                    $toDB = $dbName;
                    $toTable = $constraint['reference']['table'];
                  }
                  $toTable = $info[$toDB]['tables_lc'][strtolower($toTable)]['name'];
                  $toFields = $constraint['reference']['columns'];
                  $fromFields = $constraint['columns'];
                  $info[$toDB]['foreignkeys'][$toTable][$name] = array('toDB' => $dbName, 'toTable' => $table, 'toFields' => $fromFields, 'fromFields' => $toFields);
                  $info[$dbName]['dependences'][$table][$name] = array('toDB' => $toDB, 'toTable' => $toTable, 'toFields' => $toFields, 'fromFields' => $fromFields);
               }
            }
         }
         $info[$dbName]['routines'] = []; //$db->getRoutines();
      }
      foreach (ORM::getInstance()->getDBList() as $db)
      {
         $dbName = $db->getDBName();
         $tables = array_keys((array)$info[$dbName]['tables']);
         foreach (array('foreignkeys', 'dependences') as $mode)
         {
            foreach ($tables as $table)
            {
               if (empty($info[$dbName][$mode][$table])) continue;
               foreach ((array)$info[$dbName][$mode][$table] as $n => $data)
               {
                  $pk = $info[$data['toDB']]['tables_lc'][strtolower($data['toTable'])];
                  if (!array_key_exists('key', $pk)) {
                      continue;
                  }
                  $pk = $pk['key'];
                  $multiplicity = count(array_diff(array_keys($pk), $data['toFields'])) > 0;
                  $info[$dbName][$mode][$table][$n]['multiplicity'] = $multiplicity;
               }
            }
         }
      }
      return $info;
   }

   private function getPropertyName($alias, $table, $field)
   {
      $db = ORM::getInstance()->getDB($alias);
      if (strpos($alias, '.') !== false) $alias = $db->wrap($alias);
      if (strpos($table, '.') !== false) $table = $db->wrap($table);
      if (strpos($field, '.') !== false) $field = $db->wrap($field);
      return $alias . '.' . $table . '.' . $field;
   }

   private function getRepositoryName($alias, $table)
   {
      $db = ORM::getInstance()->getDB($alias);
      if (strpos($alias, '.') !== false) $alias = $db->wrap($alias);
      if (strpos($table, '.') !== false) $table = $db->wrap($table);
      return $alias . '.' . $table;
   }

   private function getMethodCode($data)
   {
      $method = '';
      switch ($data['type'])
      {
         case 'code':
           $method .= $data['code'];
           break;
         case 'query':
           $params = array();
           foreach ($data['parameters'] as $param)
           {
              if (isset($param['maps']))
              {
                 if (isset($param['name'])) $params[] = "'" . $param['name'] . '\' => $this->' . $param['maps'];
                 else $params[] = '$this->' . $param['maps'];
              }
              else if (isset($param['value']))
              {
                 if (isset($param['name'])) $params[] = "'" . $param['name'] . '\' => \'' . self::getParameter($param['value']) . "'";
                 else $params[] = "'" . self::getParameter($param['value']) . "'";
              }
           }
           if (count($params))
             $method .= 'return $this->db->' . strtolower($data['output']) . '(\'' . addslashes($data['sql']) . '\', array(' . implode(', ', $params) . '));';
           else
             $method .= 'return $this->db->' . strtolower($data['output']) . '(\'' . addslashes($data['sql']) . '\');';
           break;
         case 'function':
           $params = array();
           foreach ($data['parameters'] as $param)
           {
              if (isset($param['maps']))
              {
                 $params[] = '$this->' . $param['maps'];
              }
              else if (isset($param['value']))
              {
                 $params[] = self::getParameter($param['value']);
              }
           }
           $call = explode('::', $data['call']);
           if (count($call) == 2)
           {
              if (!strlen($call[0]))
              {
                 $method = 'call_user_func_array(get_class($this->parent) . \'' . $data['call'] . '\', array(' . implode(', ', $params) . '));';
                 break;
              }
           }
           else
           {
              $call = explode('->', $data['call']);
              if (count($call) == 2)
              {
                 if (!strlen($call[0])) $call = '$this->parent' . $data['call'];
              }
              else $call = $data['call'];
           }
           $method = 'return ' . $call . '(' . implode(', ', $params) . ');';
           break;
      }
      return $method;
   }
   
   public static function getNavigationPropertyName($name)
   {
     $tmp = explode('_', ($name));
     if (count($tmp) == 1) return strtoupper($name) == $name ? lcfirst(strtolower($name)) : lcfirst($name);
     foreach ($tmp as $k => $v) if (trim($v)) $tmp[$k] = ucfirst(strtolower(trim($v))); else unset($tmp[$k]);
     return lcfirst(implode('', $tmp));
   }

   private static function getParameter($value)
   {
      if (strtolower($value) == 'true' || strtolower($value) == 'false') return $value;
      if (is_numeric($value)) return $value;
      return addslashes($value);
   }

   public static function getClassName($table)
   {
      $tmp = explode('_', ($table));
      if (count($tmp) == 1) return strtoupper($table) == $table ? ucfirst(strtolower($table)) : ucfirst($table);
      foreach ($tmp as $k => $v) if (trim($v)) $tmp[$k] = ucfirst(strtolower(trim($v))); else unset($tmp[$k]);
      return implode('', $tmp);
   }

   private static function getFileName($dir, $table, $fileName = null)
   {
      $file = $dir . '/';
      if (stripos($table, 'lookup') !== false) $file .= 'lookup/';
      $file .= (!$fileName) ?  str_replace('_', '', strtolower($table)) . '.php' : $fileName;
      return $file;
   }

   private static function saveClassToFile($file, $code)
   {
      $dir = dirname($file);
      if (!is_dir($dir)) mkdir($dir, 0775, true);
      file_put_contents($file, '<?php' . PHP_EOL . PHP_EOL . $code . PHP_EOL . PHP_EOL . '?>');
   }

   private function rewriteClass($file, Core\TemplateOld $tpl)
   {
      $fullClassName = (($tpl->namespace != '\\') ? $tpl->namespace : '') . '\\' . $tpl->class;
      $info = new Utils\InfoClass($file);
      if (!isset($info->{$fullClassName})) return false;
      $tpl->class = 'T' . $tpl->class;
      $tplClass = (($tpl->namespace != '\\') ? $tpl->namespace : '') . '\\' . $tpl->class;
      $tplInfo = new Utils\InfoClass();
      $tplInfo->parseCode('<?php ' . PHP_EOL . $tpl->render() . PHP_EOL . '?>', 'template');
      foreach ($tplInfo->{$tplClass}->methods as $method => $methodInfo)
      {
         $needCode = trim($methodInfo->code);
         if ($method != '__construct')
         {
            $info->{$fullClassName}->methods[$method] = $methodInfo;
         }
         else
         {
            $code = $info->{$fullClassName}->methods[$method]->code;
            if (!Utils\PHPParser::inCode($needCode, $code))
            {
               $info->{$fullClassName}->methods[$method]->code = PHP_EOL . '        ' . $needCode . $code;
            }
         }
      }
      if (isset($tplInfo->{$tplClass}->properties['bll']) && $tplInfo->{$tplClass}->properties['bll']->isStatic)
      {
        $info->{$fullClassName}->properties['bll'] = clone $tplInfo->{$tplClass}->properties['bll'];
      }
      foreach ($info->{$fullClassName}->methods as $method => $methodInfo)
      {
         if (substr($method,0,5) == '_init' && !isset($tplInfo->{$tplClass}->methods[$method])) unset($info->{$fullClassName}->methods[$method]);
      }
      $info->{$fullClassName}->comment = $tplInfo->{$tplClass}->comment;
      $info->{$fullClassName}->parentName = $tplInfo->{$tplClass}->parentName;
      $info->{$fullClassName}->parentShortName = $tplInfo->{$tplClass}->parentShortName;
      $info->{$fullClassName}->parentNamespace = $tplInfo->{$tplClass}->parentNamespace;
      $dir = dirname($file);
      if (!is_dir($dir)) mkdir($dir, 0775, true);
      file_put_contents($file, $info->getCodeFile($file));
      return true;
   }
}

?>
