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
 * Responsibility of this file: archestra.php
 *
 * @category   DB
 * @package    DB
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 * @since      File available since Release 1.0.0
 */
namespace ClickBlocks\DB;

use ClickBlocks\Core;

/**
 * The class is an abstraction used for the concentration of plain sql query in one place.
 */
class Orchestra
{
  public static $db = null;
  public static $sql = null;
  
  protected static $bll = null;
  
  /**
   * @return DB
   */
  protected static function getDB()
  {
    return (new SQLGenerator())->getDBByClassName(static::$bll);
  }
  
  /**
   * @return SqlBuilder
   */
  protected static function getSQL()
  {
    return (new SQLGenerator())->getDBByClassName(static::$bll)->sql;
  }
   
  protected static function getTableName()
  {
    $info = ORM::getInstance()->getORMInfoObject();
    $tb = $info->getTableAliasByClassName(\ClickBlocks\Utils\PHPParser::getClassName(static::$bll));
    return $info->getTableNameByTableAlias($tb[0],$tb[1]);
  }

  /**
   * Returns array of BLL objects by specified criteria.
   * 
   * @param string|array $where - Where clause (w/o the WHERE keyword), OR associative array with fields and values to search for.
   * @param string ORDER BY clause without the keyword
   * @param int $start Start offset
   * @param int $limit Record limit
   * @param bool $multiple
   * @return array Array of BLL Objects
   */
  protected static function getObjectsByQuery($where, $order = null, $start = null, $limit = null)
  {
    $db = static::$db ?: static::getDB();
    $rows = $db->rows((new SQLGenerator())->getByQuery(static::$bll, $where, $order, $start, $limit), is_array($where) ? $where : []);
    return static::rowsToObjects($rows, true);
  }
   
  /**
   * Returns BLL object by specified criteria. If more than one object met the criteria than first one (order by primary key) 
   * will be returned. If no object found - empty BLL will returned.
   * 
   * @param string|array $where - Where clause (w/o the WHERE keyword), OR associative array with fields and values to search for.
   * @return \ClickBlocks\DB\BLLTable
   */
  protected static function getObjectByQuery($where)
  {
    $rows = static::getObjectsByQuery($where, null, null, 1);
    if (count($rows) == 0) return new static::$bll();
    return $rows[0];
  }
   
  protected static function rowsToObjects(array $rows)
  {
    $objects = [];
    foreach ($rows as $row)
    {
      $tb = new static::$bll();
      $tb->assign($row);
      $objects[] = $tb;
    }
    return $objects;
  }
   
  protected static function getLimitClause($size, $page = null)
  {
    $size = (int)$size;
    if ($size > 0)
	   {
	     if ((string)(int)$page === (string)$page) $limit = ' LIMIT ' . ($size * $page) . ', ' . $size;
	     else $limit = ' LIMIT ' . $size;
 	  }
	   return $limit;
  }

    /**
     * Return object ID by name.
     * @param string $name object name
     * @return integer
     */
    public static function getIDByName($name)
    {
        $data = [
            ':name' => $name
        ];
        return static::getDB()->cell("SELECT ID FROM " . static::getTableName() .  " WHERE LOWER(name) = LOWER(:name)", $data);
    }

    /**
     * Return object ID by name.
     * @param string $name object name
     * @param string $cityID object cityID
     * @return integer
     */
    public static function getIDByNameAndCity($name, $cityID, $type)
    {
        $data = [
            ':name' => $name,
            ':cityID' => $cityID,
            ':type' => $type
        ];
        return static::getDB()->cell("SELECT ID FROM Organizations WHERE LOWER(name) = LOWER(:name) AND cityID = :cityID AND organizationTypeID = :type", $data);
    }

}