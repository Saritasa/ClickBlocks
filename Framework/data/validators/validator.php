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

use ClickBlocks\Core;

/**
 * This class is the base class for all validators.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
abstract class Validator
{
  /**
   * Error message templates.
   */
  const ERR_VALIDATOR_1 = 'Invalid validator type "[{var}]". The only following types are valid: "type", "required", "compare", "regexp", "string", "number", "array", "xml" and "custom".';
  const ERR_VALIDATOR_2 = 'The third parameter of "verify" method is invalid. You can only use the following mode values: "and", "or", "xor".';
  const ERR_VALIDATOR_3 = 'The second parameter should only contain objects of ClickBlocks\Validators\Validator type.';
  
  /**
   * Determines whether the value can be null or empty.
   * If $empty is TRUE then validating empty value will be considered valid.
   *
   * @var boolean $empty
   * @access public
   */
  public $empty = true;
  
  /**
   * The reason of validator failure. The reason equals TRUE if the validator returns TRUE.
   * The structure of the reason array as follows:
   * [
      'code'    => ... // the reson code.
   *  'reason'  => ... // the short reason message.
   *  'details' => ... // the reason details. It can be omitted for some validators.
   * ]
   *
   * @var array $reason
   * @access protected
   */
  protected $reason = null;

  /**
   * Creates and returns a validator object of the required type.
   * Validator type can be one of the following values: "type", "required", "compare", "regexp", "text", "number", "collection", "xml" and "custom".
   *
	  * @param string $type - the type of the validator object.
   * @param array $params - initial values to be applied to the validator properties.
   * @return ClickBlocks\Data\Validators\Validator
   * @access public
   */
  public static function getInstance($type, array $params = [])
  {
    $class = 'ClickBlocks\Data\Validators\\' . $type;
    if (!\CB::getInstance()->loadClass($class)) throw new Core\Exception('ClickBlocks\Data\Validators\Validator::ERR_VALIDATOR_1', $type);
    $validator = new $class;
    foreach ($params as $k => $v) $validator->{$k} = $v;
    return $validator;
  }
  
  /**
   * Verifies the value for matching with one or more validators.
   *
   * @param array $value - the value to be verified.
   * @param array $validators - the validators to be matched with the given value.
   * @param string $mode - determines the way of the calculation of the validation result for multiple validators.
   * @param array $result - array of the results of the validators work. Each array element contains TRUE if the appropriate validator is valid, otherwise the element contains the reason of validator failure.
   * @return boolean
   * @access public
   */
  public static function verify($value, array $validators, $mode = 'and', &$result = null)
  {
    $n = 0; $result = [];
    foreach ($validators as $key => $validator) 
    {
      if (!($validator instanceof Validator)) throw new Core\Exception('ClickBlocks\Data\Validators\Validator::ERR_VALIDATOR_3');   
      if ($validator->validate($value)) $n++;
      $result[$key] = $validator->getReason();
    }
    switch (strtolower($mode))
    {
      case 'and': return $n == count($validators);
      case 'or': return $n > 0;
      case 'xor': return $n == 1;
    }
    throw new Core\Exception('ClickBlocks\Data\Validators\Validator::ERR_VALIDATOR_2', $mode);
  }
  
  /**
   * Returns the reason of validator failure.
   * If the validator returns TRUE the reason will be TRUE as well.
   *
   * @return array
   * @access public
   */
  public function getReason()
  {
    return $this->reason;
  }

  /**
   * Validates a single value.
   * The method returns TRUE if the validating value matches the validation conditions and FALSE otherwise.
   *
   * @param mixed $entity - the value for validation.
   * @return boolean
   * @access public
   */
  abstract public function validate($entity);
  
  /**
   * Checks whether the given value is empty.
   * For scalar values the method returns TRUE if the given value is empty and FALSE otherwise.
   * If the given value is an array the methods return TRUE if this array has not any elements and FALSE otherwise.
   * If the given value is an object the methods return TRUE if this object has not any public property and FALSE otherwise.
   *
   * @param mixed $entity - the value to check.
   * @return boolean
   * @access protected
   */
  protected function isEmpty($entity)
  {
    if (is_array($entity)) return count($entity) == 0;
    if (is_object($entity)) return count(get_object_vars($entity)) == 0;
    return strlen($entity) == 0;
  }
}