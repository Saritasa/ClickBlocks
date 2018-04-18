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
 * This validator compares the specified value with another value(s) according to the given mode and comparison operator.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Compare extends Validator
{
  /**
   * Error message templates.
   */
  const ERR_VALIDATOR_COMPARE_1 = 'The mode "[{var}]" is invalid. You can only use the following mode values: "and", "or", "xor".';
  const ERR_VALIDATOR_COMPARE_2 = 'The operator "[{var}]" is invalid. You can only use the following operators: "==", "===", "!=", "!==", ">", "<", ">=", "<=".';

  /**
   * The value or values to be compared with.
   *
   * @var mixed $value
   * @access public
   */
  public $value = null;
  
  /**
   * Determines whether $value is an array of values or a single value for comparison.
   *
   * @var boolean $hasMultipleValues
   * @access public
   */
  public $hasMultipleValues = false;
  
  /**
   * Determines the way of the calculation of the comparison result with multiple values.
   * If $mode equals "and" then the result of the comparison with all values should be TRUE.
   * If $mode is "or" then at least one comparison result should be TRUE.
   * If $mode equals "xor" then the only one comparison result should get TRUE.
   *
   * @var string $mode
   * @access public
   */   
  public $mode = 'and';
  
  /**
   * The operator for comparison.
   * The following operators are valid: "==", "!=", "===", "!==", "<", ">", "<=", ">=".
   *
   * @var string $operator
   * @access public
   */
  public $operator = '==';

  /**
   * Validates a value.
   * The method returns TRUE if the validating value matches the validation conditions and FALSE otherwise.
   *
   * @param mixed $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    $values = $this->hasMultipleValues && is_array($this->value) ? $this->value : array($this->value);
    switch ($this->operator)
    {
      case '===':
        $func = function($value) use($entity) {return $value === $entity;};
        break;
      case '!==':
        $func = function($value) use($entity) {return $value !== $entity;};
        break;
      case '==':
        $func = function($value) use($entity) {return $value == $entity;};
        break;
      case '!=':
        $func = function($value) use($entity) {return $value != $entity;};
        break;
      case '<':
        $func = function($value) use($entity) {return $value < $entity;};
        break;
      case '>':
        $func = function($value) use($entity) {return $value > $entity;};
        break;
      case '<=':
        $func = function($value) use($entity) {return $value <= $entity;};
        break;
      case '>=':
        $func = function($value) use($entity) {return $value >= $entity;};
        break;
      default:
        throw new Core\Exception($this, 'ERR_VALIDATOR_COMPARE_2', $this->operator);
    }
    $this->reason = ['code' => 0, 'reason' => 'doesn\'t meet condition'];
    switch (strtolower($this->mode))
    {
      case 'and':
        foreach ($values as $value) if (!$func($value)) return false;
        break;
      case 'or':
        $flag = false;
        foreach ($values as $value)
        {
          if ($func($value))
          {
            $flag = true;
            break;
          }
        }
        if (!$flag) return false;
        break;
      case 'xor':
        $flag = false;
        foreach ($values as $value)
        {
          if ($func($value))
          {
            if ($flag) return false;
            $flag = true;
            break;
          }
        }
        if (!$flag) return false;
        break;
      default:
        throw new Core\Exception($this, 'ERR_VALIDATOR_COMPARE_1', $this->mode);
    }
    return $this->reason = true;
  }
}