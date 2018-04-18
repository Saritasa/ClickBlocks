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
 * By using this control you can manage any HTML element on the server side.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class Any extends Control
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'any';
  
  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $tag - the HTML tag name.
   * @param string $text - the inner HTML of the given element.
   * @access public
   */
  public function __construct($id, $tag = 'div', $text = null)
  {
    parent::__construct($id);
    $this->properties['tag'] = $tag;
    $this->properties['text'] = $text;
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
    return '<' . $this->properties['tag'] . $this->renderAttributes() . '>' . $this->properties['text'] . '</' . $this->properties['tag'] . '>';
  }
}