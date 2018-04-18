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
 * This validator verifies if the given value is of the specified data type.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Type extends Validator
{
  /**
   * The PHP data type that the validating value should be.
   * Valid values include "null", "string", "boolean" (or "bool"), "integer" (or "int"), "float" (or "double" or "real"), "array", "object", "resource".
   *
   * @var string $type
   * @access public
   */
  public $type = 'string';
  
  /**
   * Validates a value.
   * The method returns TRUE if the given value has the required data type. Otherwise, the method returns FALSE.
   *
   * @param mixed $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    $type = strtolower($this->type);
    if ($type == 'float' || $type == 'real') $type == 'double';
    else if ($type == 'bool') $type = 'boolean';
    else if ($type == 'int') $type = 'integer';
    else if ($type == 'null') $type = 'NULL';
    if (gettype($entity) == $type) return $this->reason = true;
    $this->reason = ['code' => 0, 'reason' => 'type mismatch'];
    return false;
  }
}