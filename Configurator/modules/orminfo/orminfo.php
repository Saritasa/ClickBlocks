<?php

namespace ClickBlocks\Configurator;

use ClickBlocks\DB;

class OrmInfo extends Module
{ 
  public function init()
  {
    $this->process('get');
  }
  
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'get':
          //new \ClickBlocks\Utils\DT;
        print_r(\ClickBlocks\DB\ORM::getInstance()->getORMInfo());
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['html' => 'orminfo/html/info.html'];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to view ORM Info contents.

Usage: cfg orminfo get

HELP;
  }
}