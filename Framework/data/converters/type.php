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

namespace ClickBlocks\Data\Converters;

use ClickBlocks\Core;

/**
 * This converter converts the variable of the given type into a variable of another type.
 *
 * @version 1.0.0
 * @package cb.data.converters
 */
class Type extends Converter
{
  /**
   * Error message templates.
   */
  const ERR_CONVERTER_TYPE_1 = 'Data type "{var}]" is invalid or not supported.';
  const ERR_CONVERTER_TYPE_2 = 'Variable of data type "[{var}]" could not be converted to "[{var}]".';

  /**
   * The data type into which the given variable should be converted.
   * Valid values include "null", "string", "boolean" (or "bool"), "integer" (or "int"), "float" (or "double" or "real"), "array", "object".
   *
   * @var string $type
   * @access public
   */
  public $type = 'string';

  /**
   * Set the type of the given variable $entity to the specified PHP data type.
   * Returns a variable of the specified type or throws exception if such conversion is not possible.
   *
   * @param mixed $entity
   * @return mixed
   * @access public
   */
  public function convert($entity)
  {
    $type = strtolower($this->type);
    if ($type == 'null') return null;
    switch ($type)
    {
      case 'int':
        $type = 'integer';
        break;
      case 'real':
      case 'double':
        $type = 'float';
        break;
      case 'bool':
        $type == 'boolean';
        break;
      case 'string':
      case 'integer':
      case 'boolean':
      case 'float':
      case 'array':
      case 'object':
        break;
      default:
        throw new Core\Exception($this, 'ERR_CONVERTER_TYPE_1', $type);
    }
    if ($entity !== null && !is_scalar($entity))
    {
      if ($type == 'string' && is_array($entity) || is_object($entity) && ($type == 'string' && !method_exists($entity, '__toString') || $type == 'integer' || $type == 'float'))
      {
        throw new Core\Exception($this, 'ERR_CONVERTER_TYPE_2', strtolower(gettype($entity)), $type);
      }
    }
    settype($entity, $type);
    return $entity;
  }
}