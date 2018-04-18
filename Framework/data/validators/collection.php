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

namespace ClickBlocks\Data\Validators;

use ClickBlocks\Core;

/**
 * This validator compares the given array with another array.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Collection extends Validator
{
  /**
   * Error message templates.
   */
  const ERR_VALIDATOR_ARRAY_1 = 'The first argument of "validate" method should be an array.';
  const ERR_VALIDATOR_ARRAY_2 = 'The property "array" should be an array.';
  const ERR_VALIDATOR_ARRAY_3 = 'Invalid "type" property. It should be one of the following values: "numeric", "associative" or "mixed".';

  /**
   * The array to be compared with.
   *
   * @var array $array
   * @access public
   */
  public $array = null;
  
  /**
   * Determines whether the strict comparison is used.
   *
   * @var boolean $strict
   * @access public
   */
  public $strict = false;
  
  /**
   * Determines the required type of the validating array.
   * Possible values are "numeric", "associative" and "mixed".
   * The null value means no type checking.
   *
   * @var string $type
   * @access public
   */
  public $type = null;
  
  /**
   * Validates a value.
   * The method returns TRUE if the given array equal the another array. Otherwise, the method returns FALSE.
   *
   * @param mixed $entity - the array for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    if (!is_array($entity)) throw new Core\Exception($this, 'ERR_VALIDATOR_ARRAY_1');
    if ($this->type !== null)
    {
      switch (strtolower($this->type))
      {
        case 'numeric':
          foreach ($entity as $k => $v) if (!is_numeric($k))
          {
            $this->reason = ['code' => 1, 'reason' => 'not numeric array'];
            return false;
          }
          break;
        case 'associative':
          foreach ($entity as $k => $v) if (!is_string($k))
          {
            $this->reason = ['code' => 2, 'reason' => 'not associative array'];
            return false;
          }
          break;
        case 'mixed':
          $n = 0;
          foreach ($entity as $k => $v) 
          {
            if (is_string($k)) $n |= 1;
            else if (is_numeric($k)) $n |= 2;
            if ($n == 3) break;
          }
          if ($n != 3)
          {
            $this->reason = ['code' => 3, 'reason' => 'not mixed array'];
            return false;
          }
          break;
        default:
          throw new Core\Exception($this, 'ERR_VALIDATOR_ARRAY_3', $this->type);
      }
    }
    if ($this->array !== null)
    {
      if (!is_array($this->array)) throw new Core\Exception($this, 'ERR_VALIDATOR_ARRAY_2');
      if ($this->strict)
      {
        if ($this->array === $entity) return $this->reason = true;
      }
      else
      {
        if ($this->array == $entity) return $this->reason = true;
      }
      $this->reason = ['code' => 0, 'arrays are not equal'];
      return false;
    }
    return $this->reason = true;
  }
}