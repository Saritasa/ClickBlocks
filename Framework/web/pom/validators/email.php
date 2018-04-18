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
 * This validator checks whether the value of the validating control matches valid email format.
 *
 * @version 1.0.0
 * @package cb.web.pom
 */
class VEmail extends VRegExp
{
  const EMAIL_REG_EXP = '/^[-a-z0-9!#$%&\'*+\/=?^_`{|}~]+(\.[-a-z0-9!#$%&\'*+\/=?^_`{|}~]+)*@([a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?\.)+(aero|arpa|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|[a-z][a-z])$/i';

  /**
   * The validator type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'vemail';

  /**
   * Constructor. Initializes the validator properties.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->attributes['expression'] = self::EMAIL_REG_EXP;
  }
}