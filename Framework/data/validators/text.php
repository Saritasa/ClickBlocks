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
 * This validator validates that the given string value is of certain length.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Text extends Validator
{
  /**
   * The maximum length of the validating string.
   * Null value means no maximum limit.
   *
   * @var integer $max
   * @access public
   */
  public $max = null;

  /**
   * The minimum length of the validating string.
   * Null value means no minimum limit.
   *
   * @var integer $min
   * @access public
   */
  public $min = null;
  
  /**
   * The encoding of the string value to be validated.
   * This property is used only when mbstring PHP extension is enabled.
   * The value of this property will be used as the 2nd parameter of the mb_strlen() function.
   * If this property is not set, the internal character encoding value will be used (see function mb_internal_encoding()).
   * If this property is FALSE, then strlen() will be used even if mbstring is enabled.
   *
   * @var string $charset
   * @access public
   */
  public $charset = false;
  
  /**
   * Validates a string value. If the given value is not string, it will be converted to string.
   * The method returns TRUE if length of the given string is in the required limits. Otherwise, the method returns FALSE.
   *
   * @param string $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    $len = $this->charset !== false && function_exists('mb_strlen') ? mb_strlen($entity, $this->charset) : strlen($entity);
    if ($this->min !== null)
    {
      if ($len < $this->min)
      {
        $this->reason = ['code' => 1, 'reason' => 'too short'];
        return false;
      }
    }
    if ($this->max !== null)
    {
      if ($len > $this->max)
      {
        $this->reason = ['code' => 2, 'reason' => 'too '];
        return false;
      }
    }
    return $this->reason = true;
  }
}