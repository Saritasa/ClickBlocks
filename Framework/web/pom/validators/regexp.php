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

namespace ClickBlocks\Web\POM;

/**
 * This validator checks whether the value of the validating control matches the given regular expression.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class VRegExp extends Validator
{
  /**
   * The validator type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'vregexp';

  /**
   * Constructor. Initializes the validator properties.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->dataAttributes['empty'] = 1;
    $this->dataAttributes['expression'] = 1;
  }

  /**
   * Returns TRUE if the given value matches the specified regular expression and FALSE otherwise.
   * If the given value is not scalar, the method always returns TRUE.
   *
   * @param mixed $value - the value to validate.
   * @return boolean
   * @access public
   */
  public function check($value)
  {
    if (isset($value) && !is_scalar($value)) return true;
    if (empty($this->attributes['expression']) || !empty($this->attributes['empty']) && strlen($value) == 0) return true;
    $exp = $this->attributes['expression'];
    if ($exp[0] == 'i') return preg_match(substr($exp, 1), $value) == 0; 
    return preg_match($exp, $value) > 0;
  }
}