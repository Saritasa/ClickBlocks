<?php

set_time_limit(0);

$classes = ['CB',
            'ClickBlocks\Core\Exception',
            'ClickBlocks\Core\Delegate',
            'ClickBlocks\Core\Event',
            'ClickBlocks\Cache\Cache',
            'ClickBlocks\Core\Template',
            'ClickBlocks\DB\MySQLBuilder',
            'ClickBlocks\Net\URL',
            'ClickBlocks\Net\Router',
            'ClickBlocks\Utils\PHP\Tokenizer'];

foreach ($classes as $class)
{
  $res = require_once(__DIR__ . '/' . str_replace('\\', '.', strtolower($class)) . '.php');
  $msg = $res === true ? '+' : ($res ? $res : '-');
  if (PHP_SAPI === 'cli') echo $class . ': ' . $msg . PHP_EOL;
  else echo '<div style="' . ($res !== true ? 'color:#B22222;' : '') . '"><b>' . $class . ':</b> ' . $msg . '</div>';
  ob_flush();
}