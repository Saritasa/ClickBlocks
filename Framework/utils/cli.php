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
 * This class contains some helpful methods to work with command line.
 *
 * @version 1.0.0
 * @package cb.utils
 */
class CLI
{
  /**
   * Returns values of command line options. Method returns FALSE if the script is not run from command line.
   *
   * @param array $options - names of the options. Each option can be an array of different names (aliases) of the same option.
   * @return array
   * @access public
   * @static
   */
  public static function getArguments(array $options)
  {
    if (PHP_SAPI !== 'cli') return false;
    $argv = $_SERVER['argv'];
    $argc = $_SERVER['argc'];
    $res = $opts = array();
    foreach ($options as $opt)
    {
      if (!is_array($opt)) $opts[$opt] = $opt;
      else foreach ($opt as $o) $opts[$o] = $o; 
    }
    for ($i = 1; $i < $argc; $i++)
    {
      if (isset($opts[$argv[$i]]))
      {
        $v = isset($argv[$i + 1]) && empty($opts[$argv[$i + 1]]) ? $argv[$i + 1] : '';
        foreach ((array)$opts[$argv[$i]] as $opt) $res[$opt] = $v;
      }
    }
    return $res;
  }
}