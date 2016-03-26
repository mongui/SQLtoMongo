<?php

/**
 * Example 1.
 *
 * Simple migration of data from an Access/MySQL table to MongoDB.
 *
 * NOTE: Please, make a backup of your collection before running this library.
 *
 * @author		Gontzal Goikoetxea
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
require_once(dirname(__FILE__) . '/../lib/sqltomongo.php');

$sqltomongo = new SQLtoMongo();

// Database connections.
//$sqltomongo->set_mysql_connection('localhost', 'dbname', 'user', 'pass');
$sqltomongo->set_access_connection(dirname(__FILE__) . '/dbexamples/Employees.accdb');
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample1');

// Source (SQL) -> Destination (Mongo)
$sqltomongo->set_tables('employees', 'employees');

$sqltomongo->start();

