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
 * This validator validates that the given value matches to the specified regular expression pattern.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class Regex extends Validator
{
  const ERR_VALIDATOR_REGEXP_1 = 'The pattern is empty.';

  /**
   * The regular expression to be matched with.
   *
   * @var string $pattern
   */
  public $pattern = null;

  /**
   * Determines whether to invert the validation logic.
   * If set to TRUE, the regular expression defined via $pattern should not match the given value.
   *
   * @var boolean $inversion
   * @access public
   */
  public $inversion = false;
  
  /**
   * Validates a value.
   * The method returns TRUE if the given value matches the specified regular expression. Otherwise, the method returns FALSE.
   *
   * @param string $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    if (!$this->pattern) throw new Core\Exception($this, 'ERR_VALIDATOR_REGEXP_1');
    $this->reason = ['code' => 0, 'reason' => 'doesn\'t match'];
    if ($this->inversion)
    {
      if (preg_match($this->pattern, $entity)) return false;
    }
    else
    {
      if (!preg_match($this->pattern, $entity)) return false;
    }
    return $this->reason = true;
  }
}