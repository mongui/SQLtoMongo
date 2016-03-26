<?php

/**
 * Example 2.
 *
 * Migration of an SQL table renaming field names.
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
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample2');

// Source (SQL) -> Destination (Mongo)
$fields = array(
	'emp_no'		=> 'empNo',
	'birth_date'	=> 'birthDate',
	'first_name'	=> 'firstName',
	'last_name'		=> 'lastName',
	'gender'		=> 'gender',
	'hire_date'		=> 'hireDate'
);
$sqltomongo->set_fields($fields);

$sqltomongo->set_tables('employees', 'employees');
$sqltomongo->start();

