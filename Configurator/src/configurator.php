<?php

namespace ClickBlocks\Configurator;

require_once(__DIR__ . '/module.php');

final class Configurator
{
  private static $instance = null;
  private static $configs = [];
  private static $modules = [];
  private static $cb = null;
  private static $clickBlocks;

  private function __construct(){}

  public static function isAjaxRequest()
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }

  public static function isFirstRequest()
  {
    return self::isCLI() || $_SERVER['REQUEST_METHOD'] == 'GET' && !self::isAjaxRequest();
  }

  public static function isCLI()
  {
    return PHP_SAPI === 'cli';
  }

  public static function getCB()
  {
    return self::$cb;
  }

  public static function getConfigs()
  {
    return self::$configs;
  }

  public static function setConfigs(array $configs, $merge = true)
  {
    if (!$merge) self::$configs = [];
    foreach ($configs as $file => $editable) self::$configs[$file] = $editable;
  }

  public static function init()
  {
    set_time_limit(0);
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../..');
    static::$clickBlocks = realpath(__DIR__ . '/../../Framework/clickblocks.php');
    $list = __DIR__ . '/../modules/list.txt';
    if (file_exists($list))
    {
      $list = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      $base = __DIR__ . '/../modules/';
      foreach ($list as $name)
      {
        if (file_exists($base . $name))
        {
          $php = $base . $name . '/' . $name . '.php';
          if (file_exists($php))
          {
            require_once($php);
            $class = 'ClickBlocks\Configurator\\' . $name;
            if (class_exists($class)) self::$modules[$name] = new $class();
          }
        }
      }
    }
    foreach (self::$modules as $name => $module) $module->init();
    if (self::isFirstRequest())
    {
      $errors = [];
      if (!file_exists(static::$clickBlocks)) $errors[] = 'File ' . static::$clickBlocks . ' is not found.';
      if (count($errors)) self::show(['errors' => $errors]);
      if (!self::isCLI())
      {
        self::connect();
        $data = [];
        foreach (self::$modules as $name => $module) $data[$name] = $module->getData();
        self::show(['errors' => [], 'modules' => $data]);
      }
    }
    if (!self::$instance) self::$instance = new self();
    return self::$instance;
  }

  public function process()
  {
    if (!self::isAjaxRequest() && !self::isCLI()) return;
    list($module, $command, $args) = self::parseParameters();
    self::connect();
    if (empty(self::$modules[$module]))
    {
      if (Configurator::isCLI()) echo self::getCommandHelp();
      return;
    }
    self::$modules[$module]->process($command, $args);
  }

  private static function getCommandHelp()
  {
    $help = <<<'HELP'

Utility to configure a web application and perform the common developer tasks.

General usage: cfg [MODULE] [COMMAND] [OPTIONS]...
    MODULE    name of the configurator module.
    COMMAND   some action of the module to be performed. The list of command is defined by the particular module.   
    OPTIONS   some additional parameters that defined by the given command and module.

The module list:

HELP;
    foreach (self::$modules as $name => $module) $help .= PHP_EOL . 'Module: [' . $name . ']' . PHP_EOL . $module->getcommandHelp();
    return $help;
  }

  private static function parseParameters()
  {
    if (self::isCLI())
    {
      $argv = $_SERVER['argv'];
      $argc = $_SERVER['argc'];
      $args = [];
      $command = null;
      $module = isset($argv[1]) ? strtolower($argv[1]) : '';
      if ($module && substr($module, 0, 2) != '--')
      {
        $command = isset($argv[2]) ? strtolower($argv[2]) : '';
        if ($command && substr($command, 0, 2) != '--')
        {
          $args = [];
          for ($i = 3, $key = ''; $i < $argc; $i++)
          {
            if (substr($argv[$i], 0, 2) == '--')
            {
              $key = substr($argv[$i], 2);
              $args[$key] = '';
            }
            else if ($key)
            {
              $args[$key][] = $argv[$i];
            }
          }
          foreach ($args as &$arg) if (count($arg) == 1) $arg = $arg[0];
        }
      }
    }
    else
    {
      $args = $_REQUEST;
      $module = strtolower($args['module']);
      $command = strtolower($args['command']);
      $args = isset($args['args']) ? $args['args'] : [];
    }
    return [$module, $command, $args];
  }

  private static function connect()
  {
    require_once(static::$clickBlocks);
    self::$cb = \CB::init();
    \CB::errorHandling(false);
    foreach (self::$configs as $file => $editable) self::$cb->setConfig($file);
  }

  private static function show(array $vars)
  {
    extract($vars);
    require(__DIR__ . '/../html/configurator.html');
    exit;
  }
}
