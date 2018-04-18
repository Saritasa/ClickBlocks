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

namespace ClickBlocks\DB;

use ClickBlocks\Core,
    ClickBlocks\Cache,
    ClickBlocks\Net;

/**
 * The class defines the interface for accessing Oracle databases in PHP via PDO extension. 
 *
 * @version 1.0.0
 * @package cb.db
 */
class OCI extends \PDO
{
  /**
   * Returns the last value from a sequence object.
   *
   * @param string $seqname - name of the sequence object from which the ID should be returned.
   * @return string
   * @access public
   */
  public function lastInsertId($seqname = null)
  {
    if (!$seqname) return;
    $st = $this->prepare('SELECT "' . $seqname . '".currval FROM dual');
    $st->execute();
    return $st->fetchColumn();
  }
}
