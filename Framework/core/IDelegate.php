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

use ClickBlocks\MVC;

/**
 * IDelegate interface is intended for building of ClickBlocks framework callbacks.
 * Using such classes we can transform strings in certain format into callback if necessary. 
 *
 * @version 1.0.0
 * @package cb.core
 */
interface IDelegate
{

    /**
     * Constructor. The only argument of it is string in ClickBlocks framework format.
     * The following formats are possible:
     * 'function' - invokes a global function with name 'function'.
     * '->method' - invokes a method 'method' of ClickBlocks\MVC\Page::$current object (if defined) or ClickBlocks object. 
     * '::method' - invokes a static method 'method' of ClickBlocks\MVC\Page::$current object (if defined) or ClickBlocks object.
     * 'class::method' - invokes a static method 'method' of a class 'class'.
     * 'class->method' - invokes a method 'method' of a class 'class' with its constructor without arguments.
     * 'class[]' - creates an object of a class 'class' without sending any arguments in its constructor.
     * 'class[]::method' - the same as 'class::method'.
     * 'class[]->method' - the same as 'class->method'.
     * 'class[n]' - creates an object of a class 'class' with its constructor taking n arguments.
     * 'class[n]::method' - the same as 'class::method'.
     * 'class[n]->method' - invokes a method 'method' of a class 'class' with its constructor taking n arguments.
     * 'class@cid->method' - invokes a method 'method' of web control type of 'class' with unique (or logic) ID equals 'cid'.
     *
     * @param mixed $callback - an ClickBlocks framework callback string or a callable callback.
     * @access public
     */
    public function __construct($callback);

    /**
     * The magic method allowing to invoke an object of this class as a method.
     * The method can take different number of arguments.
     *
     * @return mixed
     * @access public
     */
    public function __invoke();

    /**
     * The magic method allowing to convert an object of this class to a callback string.
     *
     * @return string
     * @access public
     */
    public function __toString();

    /**
     * Invokes a callback's method or creating a class object.
     * For callbacks in format 'class[n]' and 'class[n]->method' first n arguments of the method
     * are arguments of constructor of a class 'class'.
     *
     * @param array $args - array of method arguments.
     * @return mixed
     * @access public
     */
    public function call(array $args = null);

    /**
     * Checks whether or not the delegate can be invoked according to permissions.
     * Permissions array have the following structure:
     * [
     *   'permitted' => ['regexp1', 'regexp2', ... ],
     *   'forbidden' => ['regexp1', 'regexp2', ...]
     * ]
     * If string representation of the delegate matches at least one of 'permitted' regular expressions and none of 'forbidden' regular expressions, the method returns TRUE.
     * Otherwise it returns FALSE.
     *
     * @param array $permissions - permissions to check.
     * @return boolean
     * @access public
     */
    public function isPermitted(array $permissions);

    /**
     * Verifies that the delegate exists and can be invoked.
     *
     * @param boolean $autoload - whether or not to call __autoload by default.
     * @return boolean
     * @access public
     */
    public function isCallable($autoload = true);

    /**
     * Returns array of detail information of the callback.
     * Output array has the format ['class' => ... [string] ..., 
     *                              'method' => ... [string] ..., 
     *                              'static' => ... [boolean] ..., 
     *                              'numargs' => ... [integer] ..., 
     *                              'cid' => ... [string] ...,
     *                              'type' => ... [string] ...]
     *
     * @return array
     * @access public
     */
    public function getInfo();

    /**
     * Returns full class name (with namespace) of the callback.
     *
     * @return string
     * @access public
     */
    public function getClass();

    /**
     * Returns method name of the callback.
     *
     * @return string
     * @access public
     */
    public function getMethod();

    /**
     * Returns callback type. Possible values can be "closure", "function", "class" or "control".
     *
     * @return string
     * @access public
     */
    public function getType();

    /**
     * Returns parameters of a delegate class method, function or closure. 
     * Method returns FALSE if class method doesn't exist.
     * Parameters are returned as an array of \ReflectionParameter class instance.
     *
     * @return array | boolean
     * @access public
     */
    public function getParameters();

    /**
     * Creates and returns object of callback's class.
     *
     * @param array $args - arguments of the class constructor.
     * @return object
     * @access public
     */
    public function getClassObject(array $args = null);
}
