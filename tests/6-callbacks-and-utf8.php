<?php

/**
 * Example 6.
 *
 * Modifying the results of an SQL query before adding to MongoDB and forcing
 * encoding conversion to UTF-8.
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
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample6');

// FIRST TABLE.
$fields = array(
	'emp_no'		=> 'empNo',
	'first_name'	=> 'firstName',
	'last_name'		=> 'lastName'
);
$sqltomongo->set_fields($fields);

$sqltomongo->set_tables('employees', 'employees');
$sqltomongo->start();

echo '------------------------------', PHP_EOL;

// SECOND TABLE.
//emp_no 	salary 	from_date 	to_date
$sqltomongo = new SQLtoMongo();
$sqltomongo->set_access_connection(dirname(__FILE__) . '/dbexamples/Employees.accdb');
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample6');

$sqltomongo->set_tables('salaries', 'employees');
$sqltomongo->set_mongo_keys('empNo', 'emp_no');

// To UTF-8.
$sqltomongo->convert_to_utf8(true);

// Editing the SQL results before adding to MongoDB.
$sqltomongo->set_query_callback(function($data) {
	var_dump($data[0]);
	$newData = array();
	$salaries = array();
	for ($i = 0; $i < count($data); $i++) {
		if (
			$i < count($data)-1 &&
			$data[$i]['emp_no'] == $data[$i+1]['emp_no']
		) {
			$salaries[] = array(
				'amount'=> $data[$i]['salary'],
				'from'	=> $data[$i]['from_date'],
				'to'	=> $data[$i]['to_date']
			);
		} else {
			$newData[] = array(
				'emp_no'	=> $data[$i]['emp_no'],
				'salaries'	=> $salaries
			);
			$salaries = array();
		}
	}

	$newData[] = array(
		'emp_no'	=> $data[$i]['emp_no'],
		'salaries'	=> $salaries
	);
	return $newData;
});

$sqltomongo->start();
