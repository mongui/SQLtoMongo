<?php

/**
 * Example 4.
 *
 * Migration of two SQL tables one inside the other: 1-1 relationship.
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
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample4');

// FIRST TABLE.
$fields = array(
	'emp_no'		=> 'empNo',
	'birth_date'	=> 'personal.birthDate',
	'first_name'	=> 'firstName',
	'last_name'		=> 'lastName',
	'gender'		=> 'personal.gender',
	'hire_date'		=> 'hireDate'
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

// Existing Mongo field -> SQL column of the table.
$sqltomongo->set_mongo_keys('empNo', 'emp_no');

$sqltomongo->start();
