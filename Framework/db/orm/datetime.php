<?php

namespace ClickBlocks\DB;

/**
 * \DateTime to use with DALTable datetime fields
 *
 * @author KolosovskyV
 */
class DateTime extends \DateTime
{
  public function __construct($time = 'now', \DateTimeZone $object = null)
  {
    if ($time == 'CURRENT_TIMESTAMP' || $time == 'NOW()') $time = 'now';
    parent::__construct($time, $object);
  }

  public function __toString()
  {
    return $this->formatSQL();
  }
  
  /**
   * @return \self
   */
  public static function fromDateTime(\DateTime $dt)
  {
    return new self($dt->format('c'));
  }

    /**
   * Returns date string formatted as Y-m-d H:i:s
   * @param bool $shortFormat use Y-m-d instead
   * @return string
   */
  public function formatSQL($shortFormat = false)
  {
    return $this->format($shortFormat ? DALTable::DATETIME_FORMAT_SHORT : DALTable::DATETIME_FORMAT);
  }
}
