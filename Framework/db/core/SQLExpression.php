<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core;

/**
 * This class is wrapper of any SQL expressions. An instance of this class won't be processed during SQL building.
 *
 * @version 1.0.0
 * @package cb.db
 */
class SQLExpression
{
    const ERR_1 = 'SQL expression should be a string value.';

    /**
     * SQL expression.
     *
     * @var string $sql
     * @access protected
     */
    protected $sql = null;

    /** Constructor.
     *
     * @param string $sql - SQL expression.
     * @access public
     * @throws Core\Exception
     */
    public function __construct($sql)
    {
        if (!is_scalar($sql)) throw new Core\Exception($this, 'ERR_1');
        $this->sql = $sql;
    }

    /**
     * Converts an object of this class to string.
     *
     * @return string
     * @access public
     */
    public function __toString()
    {
        return $this->sql;
    }
}
