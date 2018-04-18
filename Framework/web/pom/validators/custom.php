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

use ClickBlocks\MVC;

/**
 * This validator checks whether the values of the validating controls matches the user defined conditions.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class VCustom extends Validator
{
  /**
   * The validator type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'vcustom';

  /**
   * Constructor. Initializes the validator properties.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->dataAttributes['clientfunction'] = 1;
    $this->dataAttributes['serverfunction'] = 1;
  }
  
  /**
   * Validates controls of the validator.
   * The method returns TRUE if all controls values matches the user defined conditions and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function validate()
  {
    if (!empty($this->attributes['serverfunction'])) $this->attributes['state'] = \CB::delegate($this->attributes['serverfunction'], $this);
    $flag = isset($this->attributes['state']) ? (bool)$this->attributes['state'] : true;
    /** @var View $view */
    $view = MVC\Page::$current->view;
    foreach ($this->getControls() as $id) $this->result[$view->get($id)->attr('id')] = $flag;
    if (!$flag) $this->refresh();
    return $flag;
  }
  
  /**
   * Returns value that passed to the method. 
   *
   * @param mixed $value - the value to validate.
   * @return string
   * @access public
   */
  public function check($value)
  {
    return $value; 
  }
}