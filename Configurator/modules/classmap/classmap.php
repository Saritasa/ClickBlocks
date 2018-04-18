<?php

namespace ClickBlocks\Configurator;

class Classmap extends Module
{ 
  public function init(){}
  
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'create':
        Configurator::getCB()->createClassMap();
        if (Configurator::isCLI()) echo PHP_EOL . 'The class map has been successfully created.' . PHP_EOL;
        else echo self::render(__DIR__ . '/html/details.html', ['classes' => Configurator::getCB()->getClassMap()]);
        break;
      case 'clean':
        Configurator::getCB()->setClassMap([]);
        if (Configurator::isCLI()) echo PHP_EOL . 'The class map has been successfully cleaned.' . PHP_EOL;
        else echo self::render(__DIR__ . '/html/details.html', ['classes' => []]);
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['js' => 'classmap/js/classmap.js', 'html' => 'classmap/html/classmap.html', 'data' => ['classes' => Configurator::getCB()->getClassMap()]];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to clean or regenerate classmap of the web application.

The use cases:

    1. cfg classmap create
    
       Regenerates the classmap file according to the settings of the configuration file(s).

    2. cfg classmap clean

       Cleans the classmap.

HELP;
  }
}