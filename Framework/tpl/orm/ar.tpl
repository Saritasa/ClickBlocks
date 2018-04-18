namespace <?=$namespace;?>;

/**
 * Active Record class for interaction with <?=$table;?> table.
 *
<?=$properties;?>
 */
class <?=$class;?> extends \ClickBlocks\DB\AR
{
    public function __construct($where = null, $order = null, $metaInfoExpire = null)
    {
        parent::__construct('<?=$table;?>');
        $a = \CB::getInstance();
        $a = $a['<?=$dbalias;?>'];
        $this->init(\CB::get('db') ?: new DB($a['dsn'], isset($a['username']) ? $a['username'] : null, isset($a['password']) ? $a['password'] : null, isset($a['options']) ? $a['options'] : null), $metaInfoExpire);
        if ($where != '') $this->assign($where, $order);
    }
}