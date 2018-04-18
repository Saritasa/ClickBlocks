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
 * This control represents the Ajax autocomplete combobox that
 * enables users to quickly find and select from a pre-populated list of values as they type. 
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class Autofill extends TextBox
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'autofill';
  
  /**
   * Non-standard control attributes, that should be rendered as "data-" attributes on the web page.
   *
   * @var array $dataAttributes
   * @access protected
   */
  protected $dataAttributes = ['default' => 1, 'callback' => 1, 'timeout' => 1, 'activeitemclass' => 1];

  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $value - the value of the combobox.
   * @access public
   */
  public function __construct($id, $value = null)
  {
    parent::__construct($id, $value);
    $this->attributes['timeout'] = 200;
    $this->properties['tag'] = 'div';
  }
  
  /**
   * Returns HTML of the control.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $html = '<' . $this->properties['tag'] . ' id="' . self::CONTAINER_PREFIX . $this->attributes['id'] . '"' . $this->renderAttributes(false) . '>' . parent::render();
    $html .= '<ul id="list-' . $this->attributes['id'] . '" style="display:none;"></ul></' . $this->properties['tag'] . '>';
    return $html;
  }
}