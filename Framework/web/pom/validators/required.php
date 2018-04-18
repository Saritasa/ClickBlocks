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
 * This validator checks whether the validating control has not empty value.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class VRequired extends Validator
{
  /**
   * The validator type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'vrequired';

  /**
   * Constructor. Initializes the validator properties.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->dataAttributes['trim'] = 1;
  }

  /**
   * Returns TRUE if the given value is not empty and FALSE if it is empty.
   * If the given value is not scalar, the method always returns TRUE.
   *
   * @param mixed $value - the value to validate.
   * @return boolean
   * @access public
   */
  public function check($value)
  {
    if (isset($value) && !is_scalar($value)) return true;
    if ($value === false || $value === true) return $value;
    if (!empty($this->attributes['trim'])) return strlen(trim($value)) > 0;
    return strlen($value) > 0;
  }
}