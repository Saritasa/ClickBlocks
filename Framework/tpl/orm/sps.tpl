namespace <?php echo $namespace; ?>;

use ClickBlocks\Core,
    ClickBlocks\Cache<?php if ($namespace != 'ClickBlocks\DB') echo ',' . PHP_EOL . '    ClickBlocks\DB'; echo ';'; ?>


class SPS
{
<?=$methods;?>
}