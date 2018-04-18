<?php

require_once(__DIR__ . '/../clickblocks.php');

/**
 * Test for \CB;
 */
function test_cb()
{
  $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
  $cb = \CB::init();
  if (($res = test_registry()) !== true) return $res;
  if (($res = test_config($cb)) !== true) return $res;
  return true;
}

/**
 * Test for registry functionality.
 */
function test_registry()
{
  $res = \CB::get('foo') === null;
  $res &= \CB::has('foo') === false;
  \CB::set('foo', 'test');
  $res &= \CB::get('foo') === 'test';
  $res &= \CB::has('foo') === true;
  \CB::remove('foo');
  $res &= \CB::get('foo') === null;
  \CB::set('foo', null);
  $res &= \CB::has('foo') === true;
  return !$res ? 'Registry functionality does not work.' : true; 
}

/**
 * Test for config functionality.
 */
function test_config(\CB $cb)
{
  // Checks ini config loading.
  $cb->setConfig(__DIR__ . '/_resources/config.ini');
  if ($cb->getConfig() !== ['var1' => '1', 'var2' => 'a', 'var3' => [1, 2, 3], 'section1' => ['var1' => 'test'], 'section2' => ['var1' => [1, 2, 3]]]) return 'Loading of ini config is failed.';
  // Checks php config loading.
  $cbrr = ['var1' => 1, 'var2' => '2', 'var4' => [1, 2, 3 => ['var1' => 'test']], 'section1' => ['var1' => '[1,2,3]']];
  $cb->setConfig($cbrr, null, true);
  if ($cb->getConfig() !== $cbrr) return 'Loading of php config is failed.';
  // Checks merging config data.
  $cb->setConfig(__DIR__ . '/_resources/config.ini');
  if ($cb->getConfig() !== ['var1' => '1', 'var2' => 'a', 'var4' => [1, 2, 3 => ['var1' => 'test']], 'section1' => ['var1' => 'test'], 'var3' => [1, 2, 3], 'section2' => ['var1' => [1, 2, 3]]]) return 'Merging config data is failed.';
  // Checks array access to the config data.
  $res = $cb['foo'] === null;
  $res &= $cb['var1'] === '1';
  $res &= $cb['var4'][3]['var1'] === 'test';
  $res &= $cb['section2']['var1'][2] === 3;
  $cb['foo'] = [];
  $res &= $cb['foo'] === [];
  unset($cb['var4'][3]);
  $res &= empty($cb['var4'][3]);
  $res &= $cb['a']['b'] === null;
  return !$res ? 'Implementation of array access to config data is incorrect.' : true;
}

return test_cb();