<?php

/**
 * Example 7.
 *
 * Additional methods for testing.
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
$sqltomongo->set_mongo_connection('localhost:27017', 'SqlToMongoExample7');
$sqltomongo->set_tables('employees', 'employees');

// This library inserts data in bulks of 100 documents by default.
// A different number can be set with the following method:
$sqltomongo->set_batch_max(50);

// This method sets a LIMIT to the SQL query so migrations can be tested with smaller amount of data.
// Default: No limit.
$sqltomongo->set_test_limit(2);

// Show or hide some info messages.
// Default: true.
$sqltomongo->set_info(false); // Optional. Default: true.

// Show or hide some debug messages. Info messages will also be shown.
// Default: false.
$sqltomongo->set_debug(true);

$sqltomongo->start();

