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
 * Implementation of the control iterator that is used in Panel class.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class Iterator implements \Iterator
{
  /**
   * The control array to iterate.
   *
   * @var array $controls
   * @access private
   */
  private $controls = [];
  
  /**
   * The instance of the view object.
   *
   * @var ClickBlocks\Web\POM\View $view
   * @access private
   */
  private $view = null;

  /**
   * Constructor. Initializes the private class properties.
   *
   * @param array $controls - the controls to iterate.
   * @access public
   */
  public function __construct(array $controls)
  {
    $this->controls = $controls;
    $this->view = MVC\Page::$current->view;
  }

  /**
   * Sets the internal pointer of the controls array to its first element.
   *
   * @access public
   */
  public function rewind()
  {
    reset($this->controls);
  }

  /**
   * Returns the control object that's currently being pointed to by the internal pointer.
   * If the internal pointer points beyond the end of the controls list or the control array is empty, it returns FALSE.
   *
   * @return mixed
   * @access public
   */
  public function current()
  {
    $obj = current($this->controls);
    if ($obj === false) return false;
    return $obj instanceof Control ? $obj : $this->view->get($obj);
  }

  /**
   * Returns the index element of the current array position.
   *
   * @return mixed
   */
  public function key()
  {
    return key($this->controls);
  }

  /**
   * Returns the control in the next place that's pointed to by the internal array pointer, or FALSE if there are no more controls.
   *
   * @return mixed
   */
  public function next()
  {
    return next($this->controls);
  }

  /**
   * Check if the current internal position is valid.
   *
   * @return boolean
   */
  public function valid()
  {
    return key($this->controls) !== null;
  }
}