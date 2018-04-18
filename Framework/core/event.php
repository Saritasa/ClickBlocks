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
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 */

namespace ClickBlocks\Core;

/**
 * The class provides a simple way of subscribing to events and notifying those subscribers whenever an event occurs.
 * 
 * @version 1.0.0
 * @package cb.core
 */
class Event
{
  /**
   * Array of events.
   *
   * @var array $events;
   * @access private
   * @static
   */   
  private static $events = [];

  /**
   * Subscribes to an event.
   *
   * @param string $event - event name.
   * @param mixed $listener - a delegate to invoke when the event is triggered.
   * @param integer $priority - priority of an event.
   * @param boolean $once - determines whether a listener should be called once.
   * @access public
   * @static
   */
  public static function listen($event, $listener, $priority = null, $once = false)
  {
    $listener = ['listener' => $listener, 'priority' => (int)$priority, 'once' => (bool)$once];
    if (empty(self::$events[$event])) self::$events[$event] = [];
    self::$events[$event][] = $listener;
  }
  
  /**
   * Adds a listener which is guaranteed to only be called once.
   *
   * @param string $event - event name.
   * @param mixed $listener - a delegate to invoke when the event is triggered.
   * @param integer $priority - priority of an event.
   * @access public
   */
  public static function once($event, $listener, $priority = null)
  {
    self::listen($event, $listener, $priority, true);
  }
  
  /**
   * Returns number of listeners of an event or total number of listeners.
   *
   * @param string $event - event name.
   * @return integer
   * @access public
   * @static
   */
  public static function listeners($event = null)
  {
    $count = 0;
    if ($event === null)
    {
      foreach (self::$events as $event => $listeners) $count += count($listeners);
    }
    else if (isset(self::$events[$event]))
    {
      $count = count(self::$events[$event]);
    }
    return $count;
  }
  
  /**
   * Removes a specific listener for a specific event, all listeners of a specific event or all listeners of all events.
   *
   * @param string $event - event name.
   * @param mixed $listener - a delegate which was previously added to an event.
   * @access public
   * @static
   */
  public static function remove($event = null, $listener = null)
  {
    if ($listener === null)
    {
      if ($event === null) self::$events = [];
      else unset(self::$events[$event]);
      return;
    }
    $events = [];
    if ($event !== null)
    {
      if (isset(self::$events[$event])) $events[$event] = self::$events[$event];
    }
    else
    {
      $events = self::$events;
    }
    $linfo = (new Delegate($listener))->getInfo();
    foreach ($events as $event => $listeners)
    {
      foreach ($listeners as $n => $info)
      {
        if ($info['listener'] instanceof \Closure || $listener instanceof \Closure)
        {
          if ($info['listener'] === $listener) unset(self::$events[$event][$n]);
        }
        else if ((new Delegate($info['listener']))->getInfo() === $linfo)
        {
          unset(self::$events[$event][$n]);
        }
      }
    }
  }
  
  /**
   * Triggers an event. Method returns FALSE if a specific event doesn't exist, and TRUE otherwise.
   *
   * @param string $event - event name.
   * @param array $args - arguments to pass to all listeners of an event.
   * @access public
   * @static
   */
  public static function fire($event, array $args = [])
  {
    if (empty(self::$events[$event])) return false;
    $listeners = self::$events[$event];
    uasort($listeners, function(array $a, array $b)
    {
      return $a['priority'] <= $b['priority'] ? 1 : -1;
    });
    foreach ($listeners as $n => $listener)
    {
      $res = (new Delegate($listener['listener']))->call(array_merge([$event], $args));
      if ($listener['once']) unset(self::$events[$event][$n]);
      if ($res === false) return true;
    }
    return true;
  }
}