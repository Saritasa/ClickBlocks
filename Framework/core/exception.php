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

namespace ClickBlocks\Core;
 
/**
 * Exception allows to generate exceptions with parameterized error messages which possible to get by their tokens.
 *
 * @version 1.0.0
 * @package cb.core
 */
class Exception extends \Exception
{
  /**
   * Name of a error message constant.
   * 
   * @var string $token
   * @access protected
   */
  protected $token = null;
  
  /**
   * Class name in which there is a constant of exception message.
   *
   * @var string $class
   * @access protected
   */
  protected $class = null;

  /**
   * Constructor. Constructor takes as arguments a class name with the error constant,
   * the error token (constant name) and values of parameters ​​of the error message.
   *
   * @param string $class
   * @param string $token
   * @params parameters of the error message.
   * @access public
   */
  public function __construct(/* $class, $token, $var1, $var2, ... */)
  {
    $class = func_get_arg(0);
    if (is_object($class))
    {
      $this->class = get_class($class);
      $this->token = func_get_arg(1);
    }
    else
    {
      $class = explode('::', $class);
      $this->class = ltrim($class[0], '\\');
      $this->token = isset($class[1]) ? $class[1] : func_get_arg(1);
    }
    parent::__construct(call_user_func_array(array('CB', 'error'), func_get_args()));
  }
  
  /**
   * Returns class name in which there is a error message constant.
   *
   * @return string
   * @access public
   */
  public function getClass()
  {
    return $this->class;
  }
  
  /**
   * Returns token of the error message.
   *
   * @return string
   * @access public
   */
  public function getToken()
  {
    return $this->token;
  }
  
  /**
   * Returns full information about the current exception.
   *
   * @return array
   * @access public
   */
  public function getInfo()
  {
    return \CB::analyzeException($this);
  }
}