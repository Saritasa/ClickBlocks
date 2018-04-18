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

/**
 * This validator validates that the given numeric value is in the specified limits.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Number extends Validator
{
  /**
   * The maximum value of the validating number.
   * Null value means no maximum value.
   *
   * @var float $max
   * @access public
   */
  public $max = null;

  /**
   * The minimum value of the validating number.
   * Null value means no minimum value.
   *
   * @var float $min
   * @access public
   */
  public $min = null;
  
  /**
   * Determines whether the strict comparison is used.
   *
   * @var boolean $strict
   * @access public
   */
  public $strict = false;
  
  /**
   * Determines valid format (PCRE) of the given number.
   * Null value means no format checking.
   *
   * @var string $format
   * @access public
   */
  public $format = null;
  
  /**
   * Validates a number value. If the given value is not number, it will be converted to number.
   * The method returns TRUE if value of the given number is in the required limits. Otherwise, the method returns FALSE.
   *
   * @param float $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    if ($this->format && !preg_match($this->format, $entity)) 
    {
      $this->reason = ['code' => 0, 'reason' => 'invalid format'];
      return false;
    }
    $entity = (float)$entity;
    if ($this->min !== null) 
    {
      if (!$this->strict && $entity < (float)$this->min || $this->strict && $entity <= (float)$this->min)
      {
        $this->reason = ['code' => 1, 'reason' => 'too small'];
        return false;
      }
    }
    if ($this->max !== null)
    {
      if (!$this->strict && $entity > (float)$this->max || $this->strict && $entity >= (float)$this->max)
      {
        $this->reason = ['code' => 2, 'reason' => 'too large'];
        return false;
      }
    }
    return $this->reason = true;
  }
}