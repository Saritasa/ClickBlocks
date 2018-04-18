namespace <?php echo $namespace; ?>;

use ClickBlocks\Core,
    ClickBlocks\Cache<?php if ($namespace != 'ClickBlocks\DB') echo ',' . PHP_EOL . '    ClickBlocks\DB'; echo ';'; ?>


class <?php echo $class; ?> extends \ClickBlocks\DB\RowCollection
{
    public function __construct($where = null, $order = null, $limit = null)
    {
        parent::__construct('<?php echo addslashes($bllClass); ?>', $where, $order, $limit);
    }
}