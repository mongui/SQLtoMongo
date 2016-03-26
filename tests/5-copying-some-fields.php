<?php

/**
 * Example 5.
 *
 * Selecting which fields will be migrated.
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
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample5');

// FIRST TABLE: Only the wanted fields.
$fields = array(
	'emp_no'		=> 'empNo',
	'first_name'	=> 'firstName',
	'last_name'		=> 'lastName'
);
$sqltomongo->set_fields($fields);

$sqltomongo->set_tables('employees', 'employees');
$sqltomongo->start();

// SECOND TABLE.
$fields = array(
	'emp_no'		=> 'empNo',
	'title'			=> 'title'
);
$sqltomongo->set_fields($fields);
$sqltomongo->set_tables('titles', 'employees');
$sqltomongo->set_mongo_keys('empNo', 'emp_no');

// Rows with Null columns won't be added.
$sqltomongo->remove_empty_fields(true);

// List of columns that won't be added.
$sqltomongo->remove_keys(array('empNo'));

$sqltomongo->start();
