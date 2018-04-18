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
 * This validator validates the given value according to the user defined rules.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Custom extends Validator
{
  /**
   * The callback that will be automatically invoked as the custom validation method.
   * This callback takes a value for validation as the first parameter and the validator object as the second one.
   *
   * @var mixed $callback
   * @access public
   */
  public $callback = null;
  
  /**
   * Validate a value via the custom validation method.
   *
   * @param string $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    return \CB::delegate($this->callback, $entity, $this);
  }
}