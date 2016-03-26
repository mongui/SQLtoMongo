<?php

/**
 * Example 3.
 *
 * Migration using a custom SQL query.
 *
 * NOTE: Please, make a backup of your collection before running this library.
 *
 * @author		Gontzal Goikoetxea
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
require_once(dirname(__FILE__) . '/../lib/sqltomongo.php');

$sqltomongo = new SQLtoMongo();

// Database connections.
$sqltomongo->set_access_connection(dirname(__FILE__) . '/dbexamples/Employees.accdb');
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample3');

$sqltomongo->set_sql_query('SELECT dept_no AS deptId, dept_name AS name FROM departments');

$sqltomongo->set_tables('departments', 'departments');
$sqltomongo->start();
