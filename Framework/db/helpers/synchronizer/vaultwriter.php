<?php
/**
 * ClickBlocks.PHP v. 1.0
 *
 * Copyright (C) 2014  SARITASA LLC
 * http://www.saritasa.com
 *
 * This framework is free software. You can redistribute it and/or modify
 * it under the terms of either the current ClickBlocks.PHP License
 * viewable at theclickblocks.com) or the License that was distributed with
 * this file.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the ClickBlocks.PHP License
 * along with this program.
 *
 * Responsibility of this file: vaultwriter.php
 *
 * @category   DB
 * @package    DB
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 * @since      File available since Release 1.0.0
 */
 
namespace ClickBlocks\DB\Sync;

/**
 * Class for recording database structure changes to the vault.
 *
 * @abstract
 */
class VaultWriter implements IWriter
{
  /**
   * Path to the vault file.
   *
   * @var string $file
   * @access protected
   */
  protected $file = null;

  /**
   * Constructor.
   *
   * @params string $file - path to the vault file.
   * @access public
   */ 
  public function __construct($file)
  {
    $this->file = $file;
  }
  
  /**
   * Makes changes in database structure and information tables data.
   * If changes were made in db structure then method returns the array of executed SQL queries.
   *
   * @param array $info - the array returned by method Synchronizer::compare.
   * @return array | NULL
   * @access public
   */
  public function write(array $info)
  {
    if (!is_file($this->file)) $data = array();
    else $data = unserialize(gzuncompress(file_get_contents($this->file)));
    $entities = array('tables' => false, 'columns' => true, 'indexes' => true, 'constraints' => true, 'triggers' => true, 'procedures' => false, 'events' => false, 'views' => false);
    foreach ($info['delete'] as $entity => $dta)
    {
      if ($entities[$entity])
      {
        foreach ($dta as $table => $values)
        {
          foreach ($values as $name => $v) unset($data['tables'][$table][$entity][$name]);
        }
      }
      else
      {
        foreach ($dta as $name => $v) unset($data[$entity][$name]);
      }
    }
    foreach ($info['insert'] as $entity => $dta)
    {
      if ($entities[$entity])
      {
        foreach ($dta as $table => $values)
        {
          foreach ($values as $name => $v) $data['tables'][$table][$entity][$name] = $v;
        }
      }
      else
      {
        foreach ($dta as $name => $v) $data[$entity][$name] = $v;
      }
    }
    foreach ($info['update'] as $entity => $dta)
    {
      if ($entity == 'tables')
      {
        foreach ($dta as $table => $values)
        {
          foreach ($values as $ent => $vls)
          {
            if ($ent == 'meta') $data['tables'][$table]['meta'] = $vls;
            else 
            {
              if (is_array($vls)) 
              {
                if (!isset($data['tables'][$table][$ent])) $data['tables'][$table][$ent] = array();
                foreach ($vls as $name => $v) $data['tables'][$table][$ent][$name] = $v;
              }
              else $data['tables'][$table][$ent] = $vls;
            }
          }
        }
      }
      else
      {
        $data[$entity] = array();
        foreach ($dta as $name => $v) $data[$entity][$name] = $v;
      }
    }
    $data['data'] = isset($info['data']) ? $info['data'] : null;
    file_put_contents($this->file, gzcompress(serialize($data), 9));
  }
}