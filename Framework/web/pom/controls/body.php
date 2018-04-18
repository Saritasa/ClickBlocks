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
 * Represents the main container control that maps to <body> HTML element.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class Body extends Panel
{  
  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $template - the body template.
   * @access public
   */
  public function __construct($id, $template = null)
  {
    parent::__construct($id, $template);
    $this->properties['tag'] = 'body';
  }
}