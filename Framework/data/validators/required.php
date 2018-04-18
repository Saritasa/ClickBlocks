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
 * This validator validates that the specified value is not empty value.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Required extends Validator
{
  /**
   * Validates a value.
   * For scalar values the method returns TRUE if the given value is not empty value and FALSE otherwise.
   * If the given value is an array the methods return TRUE if this array has at least one element and FALSE otherwise.
   * If the given value is an object the methods return TRUE if this object has at least one public property and FALSE otherwise.
   *
   * @param mixed $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->isEmpty($entity))
    {
      $this->reason = ['code' => 0, 'reason' => 'empty'];
      return false;
    }
    return $this->reason = true;
  }
}