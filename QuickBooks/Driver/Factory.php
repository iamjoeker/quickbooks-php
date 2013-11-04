<?php

/**
 *
 *
 * Copyright (c) 2010 Keith Palmer / ConsoliBYTE, LLC.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.opensource.org/licenses/eclipse-1.0.php
 *
 * @author     Keith Palmer <keith@consolibyte.com>
 * @license    LICENSE.txt
 *
 * @package    QuickBooks
 * @subpackage Driver
 */

QuickBooks_Loader::load('/QuickBooks/Utilities.php');

class QuickBooks_Driver_Factory
{
    /**
     * Create an instance of a driver class from a DSN connection string *or* a connection resource
     *
     * You can actually pass in *either* a DSN-style connection string OR an already connected database resource
     *    - mysql://user:pass@localhost:port/database
     *    - $var (Resource ID #XYZ, valid MySQL connection resource)
     *
     * @param mixed   $dsn_or_conn A DSN-style connection string or a PHP resource
     * @param array   $config      An array of configuration options for the driver
     * @param array   $hooks       An array mapping hooks to user-defined hook functions to call
     * @param integer $log_level
     *
     * @return object                A class instance, a child class of QuickBooks_Driver
     */
    public static function create($dsn_or_conn, $config = array(), $hooks = array(), $log_level = QUICKBOOKS_LOG_NORMAL)
    {
        static $instances = array();

        if (!is_array($hooks)) {
            $hooks = array();
        }

        // Do not serialize the $hooks because they might contain non-serializeable objects
        $key = (string)$dsn_or_conn . serialize($config) . $log_level;

        if (!isset($instances[$key])) {
            if (is_resource($dsn_or_conn)) {
                $scheme = current(explode(' ', get_resource_type($dsn_or_conn)));
            } else {
                $scheme = QuickBooks_Utilities::parseDSN($dsn_or_conn, array(), 'scheme');
            }

            if (false !== strpos($scheme, 'sql')) // SQL drivers are subclassed... change class/scheme name
            {
                $scheme = 'Sql_' . ucfirst(strtolower($scheme));
            } else {
                $scheme = ucfirst(strtolower($scheme));
            }

            $class = 'QuickBooks_Driver_' . $scheme;
            $file  = '/QuickBooks/Driver/' . str_replace(' ', '/', ucwords(str_replace('_', ' ', strtolower($scheme)))) . '.php';

            QuickBooks_Loader::load($file);

            if (class_exists($class)) {
                $Driver = new $class($dsn_or_conn, $config);
                $Driver->registerHooks($hooks);
                $Driver->setLogLevel($log_level);

                $instances[$key] = $Driver;
            } else {
                $instances[$key] = null;
            }
        }

        return $instances[$key];
    }
}
