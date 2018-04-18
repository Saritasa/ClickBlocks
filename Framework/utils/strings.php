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

namespace ClickBlocks\Utils;

/**
 * Contains the set of static methods for simplifying the work with strings.
 *
 * @version 1.0.0
 * @package cb.utils
 */
class Strings
{
  /**
   * Cuts a large text.
   *
   * @param string $string - the large text.
   * @param integer $length - length of the shortened text.
   * @param boolean $word - determines need to reduce the given string to the nearest word to the right.
   * @param boolean $stripTags - determines whether HTML and PHP tags will be deleted or not.
   * @param string $allowableTags - specifies tags which should not be stripped.
   * @return string - the shortened text.
   * @access public
   * @static
   */
  public static function cut($string, $length, $word = true, $stripTags = false, $allowableTags = null)
  {
    if ($stripTags) $string = strip_tags($string, $allowableTags);
    if ($length < 4 || $string == '' || strlen($string) <= $length) return $string;
    $lastSpacePos = strpos($string, ' ', $length - 1);
    if ($word)
    {
      if ($lastSpacePos === false) $shortText = $string;
      else $shortText = substr($string, 0, $lastSpacePos) . '...';
    }
    else
    {
      if ($lastSpacePos > $length || $lastSpacePos === false) $shortText = trim(substr($string, 0, $length - 3)) . '...';
      else $shortText = substr($string, 0, $lastSpacePos) . '...';
    }
    return $shortText;
  }
}