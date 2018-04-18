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

use ClickBlocks\Core,
    ClickBlocks\MVC;

/**
 * This validator checks whether the values of the validating controls are equal to each other.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class VCompare extends Validator
{
  // Error message template.
  const ERR_VCOMPARE_1 = 'The validating value should be scalar.';

  /**
   * The validator type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'vcompare';

  /**
   * Constructor. Initializes the validator properties.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->dataAttributes['caseinsensitive'] = 1;
  }
  
  /**
   * Validates controls of the validator.
   * The method returns TRUE if all controls values are equal to each other and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function validate()
  {
    $this->result = [];
    $ids = $this->getControls(); 
    $len = count($ids);
    if ($len == 0) 
    {
      $this->attributes['state'] = true;
      return true;
    }
    $view = MVC\Page::$current->view;
    switch (isset($this->attributes['mode']) ? strtoupper($this->attributes['mode']) : 'AND')
    {
      default:
      case 'AND':
        $flag = true;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2) $this->result[$ids[$i]] = $this->result[$ids[$j]] = true;
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = $flag = false;
          }
        }
        break;
      case 'OR':
        $flag = false;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2) $this->result[$ids[$i]] = $this->result[$ids[$j]] = $flag = true;
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = false;
          }
        }
        break;
      case 'XOR':
        $n = 0;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2)
            {
              $this->result[$ids[$i]] = $this->result[$ids[$j]] = $n < 1;
              $n++;
            }
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = false;
          }
        }
        $flag = $n == 1;
        break;
    }
    return $this->attributes['state'] = $flag;
  }
  
  /**
   * Returns the given value for comparison.
   * If the given value is not scalar, the method throws exception.
   *
   * @param mixed $value - the value to validate.
   * @return string
   * @access public
   */
  public function check($value)
  {
    if (isset($value) && !is_scalar($value)) throw new Core\Exception($this, 'ERR_VCOMPARE_1');
    return empty($this->attributes['caseinsensitive']) ? (string)$value : strtolower($value);
  }
}