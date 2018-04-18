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

namespace ClickBlocks\Utils;

/**
 * Contains the set of static methods for facilitating the work with arrays.
 *
 * @version 1.0.0
 * @package cb.utils
 */
class Arrays
{
  /**
   * Swaps the two elements of the array. The elements are determined by their index numbers.
   *
   * @param array $array - the array in which the two elements will be swapped.
   * @param integer $n1 - the index number of the first element.
   * @param integer $n2 - the index number of the second element.
   * @access public
   */
  public static function swap(array &$array, $n1, $n2)
  {
    $keys = array_keys($array);
    static::aswap($array, $keys[$n1], $keys[$n2]);
  }
  
  /**
   * Swaps the two elements of the array. The elements are determined by their keys.
   *
   * @param array $array - the array in which the two elements will be swapped.
   * @param mixed $key1 - the key of the first element.
   * @param mixed $key2 - the key of the second element.
   * @access public
   */
  public static function aswap(array &$array, $key1, $key2)
  {
    $tmp = [];
    foreach ($array as $key => $value)
    {
      if ($key == $key1) $tmp[$key2] = $array[$key2];
      else if ($key == $key2) $tmp[$key1] = $array[$key1];
      else $tmp[$key] = $value;
    }
    $array = $tmp;
  }
  
  /**
   * Inserts a value or an array to the input array at the specified position.
   *
   * @param array $array - the input array in which a value will be inserted.
   * @param mixed $value - the inserting value.
   * @param integer $offset - the position in the first array.
   * @access public
   */
  public static function insert(array &$array, $value, $offset = 0)
  {
    $array = array_slice($array, 0, $offset, true) + (array)$value + array_slice($array, $offset, null, true);
  }
  
  /**
   * Completely removes the element of a multidimensional array, defined by its keys.
   *
   * @param array $array - the array from which an element will be removed.
   * @param array $keys - the element keys.
   * @access public
   */
  public static function unsetByKeys(array &$array, array $keys)
  {
    $key = array_shift($keys);
    if (array_key_exists($key, $array))
    {
      if ($keys && is_array($array[$key])) 
      {
        static::unsetByKeys($array[$key], $keys);
        if (!$array[$key]) unset($array[$key]);
      }
      else unset($array[$key]);
    }
  }
}