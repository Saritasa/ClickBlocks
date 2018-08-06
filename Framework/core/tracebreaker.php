<?php
/**
 * ClickBlocks.PHP v. 1.0
 *
 * Copyright (C) 2010  SARITASA LLC
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
 * Responsibility of this file: tracebreaker.php
 *
 * @category   Core
 * @package    Core
 * @copyright  2007-2010 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 * @since      File available since Release 1.0.0
 */

namespace ClickBlocks\Core;

class TraceBreaker implements \Serializable
{
   private $obj = null;

   public function __construct($obj)
   {
      $this->obj = $obj;
   }

   public function __set($param, $value)
   {
      $this->obj->{$param} = $value;
   }

   public function __get($param)
   {
      return $this->obj->{$param};
   }

   public function __isset($param)
   {
      return isset($this->obj->{$param});
   }

   public function __unset($param)
   {
      unset($this->obj->{$param});
   }

   public function __call($method, $params)
   {
      return call_user_func_array(array($this->obj, $method), $params);
   }

   public static function __callStatic($method, $params)
   {
      return call_user_func_array($this->obj, $params);
   }

   public function __invoke()
   {
      return call_user_func_array(array($this->obj, '__invoke'), func_get_args());
   }

   public function __toString()
   {
      return (string)$this->obj;
   }

   public function serialize()
   {
      return serialize($this->obj);
   }

   public function unserialize($data)
   {
      $this->obj = unserialize($data);
   }

   public function __clone()
   {
      $this->obj = clone $this->obj;
   }

   public function __destruct()
   {
      $this->obj = null;
   }

   public static function isCalledFrom($class, $method = null, $level = 2)
   {
      $trace = debug_backtrace();
      return (strcasecmp($trace[$level]['class'], $class) == 0 && (!$function || strcasecmp($method, $trace[$level]['function']) == 0));
   }
}

?>