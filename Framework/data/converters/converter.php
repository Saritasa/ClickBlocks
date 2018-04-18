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
 * This class is the base class for all converters.
 *
 * @version 1.0.0
 * @package cb.data.converters
 */
abstract class Converter
{
  /**
   * Error message templates.
   */
  const ERR_CONVERTER_1 = 'Invalid converter type "[{var}]". The only following types are valid: "type", "text", "collection".';

  /**
   * Creates and returns a converter object of the required type.
   * Converter type can be one of the following values: "type", "text", "collection".
   *
	  * @param string $type - the type of the converter object.
   * @param array $params - initial values to be applied to the converter properties.
   * @return ClickBlocks\Data\Converters\Converter
   * @access public
   */
  public static function getInstance($type, array $params = [])
  {
    $class = 'ClickBlocks\Data\Converters\\' . $type;
    if (!\CB::getInstance()->loadClass($class)) throw new Core\Exception('ClickBlocks\Data\Converters\Converter::ERR_CONVERTER_1', $type);
    $converter = new $class;
    foreach ($params as $k => $v) $converter->{$k} = $v;
    return $converter;
  }
  
  /**
   * Converts the entity from one data format to another according to the specified options.
   *
   * @param mixed $entity - the entity to convert.
   * @return mixed - the converted data.
   * @access public
   */
  abstract public function convert($entity);
}