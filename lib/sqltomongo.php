<?php

/**
 * SQLtoMongo Class
 *
 * This class allows anyone to migrate an SQL table to a MongoDB collection.
 * Read the documentation and examples to understand how it works.
 *
 * NOTE: Please, make a backup of your collection before running this library.
 *
 * @author		Gontzal Goikoetxea
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
class SQLtoMongo {
	private $sqlcon;
	private $mongo;
	private $mongo_client;

	private $sql_table;
	private $mongo_table;

	private $fields;
	private $sql_query;

	private $mongo1_key;
	private $mongo2_key;

	private $empty_fields = false;
	private $remove_keys;

	private $batch_max = 99;
	private $test_limit = false;

	private $time_start;

	private $sql_query_callback;
	private $utf8 = false;

	private $debug = false;
	private $info = true;

	function __construct($nfo = true) {
		$this->info = $nfo;

		if (PHP_SAPI !== 'cli')
			echo '<pre>';

		$this->time_start = microtime(true);
		if ($this->info)
			echo 'Initializing...', PHP_EOL;

		ini_set('max_execution_time', 0);
		if ($this->info)
			echo 'PHP script timeout set to ' . ini_get('max_execution_time') . ' seconds.', PHP_EOL;

		ini_set('memory_limit', '-1');
		if ($this->info)
			echo 'PHP script memory limit set to ' . ini_get('memory_limit') . '.', PHP_EOL;
	}

	function __destruct() {
		$time_end = microtime(true);
		$time = round($time_end - $this->time_start, 3);

		$minutes = round($time/60);
		$seconds = round($time%60);
		echo 'Finished in ' . (($minutes > 0) ? $minutes . ' minutes and ' : '') . $seconds . ' seconds.', PHP_EOL;

		if (PHP_SAPI !== 'cli')
			echo '</pre>';
	}

	/**
	 * Stablishes connection to a MySQL database.
	 *
	 * @param	string : Hostname:Port
	 * @param 	string : Database Name
	 * @param 	string : User
	 * @param 	string : Password
	 */
	public function set_mysql_connection($host, $dbname, $user, $pass) {
		$driver_available = false;
		foreach (PDO::getAvailableDrivers() as $driver)
			if ($driver == 'mysql')
				$driver_available = true;

		if ($driver_available) {
			try {
				$attribs = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'");
				$this->sqlcon = new PDO('mysql:host=' . $host . ';dbname=' . $dbname, $user, $pass, $attribs);
				if ($this->info)
					echo 'Connected to MySQL database.', PHP_EOL;
			} catch (PDOException $e) {
				echo 'ERROR: Couldn\'t connect to MySQL database.', PHP_EOL;
				if ($this->debug)
					echo 'ERROR: ' . $e->getMessage(), PHP_EOL;
				exit();
			}
		} else {
			echo 'ERROR: You need to add MySQL PDO extension.', PHP_EOL;
			exit();
		}
	}

	/**
	 * Stablishes connection to an Access database.
	 *
	 * @param 	string : Database Filename
	 * @param 	string : User (optional)
	 * @param 	string : Password (optional)
	 */
	public function set_access_connection($file, $user = '', $pass = '') {
		$driver_available = false;
		foreach (PDO::getAvailableDrivers() as $driver)
			if ($driver == 'odbc')
				$driver_available = true;

		if ($driver_available) {
			try {
				$this->sqlcon = new PDO('odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=' . $file .'; Uid=' . $user .'; Pwd=' . $pass .';charset=UTF-8');
				if ($this->info)
					echo 'Connected to Access database.', PHP_EOL;
			} catch (PDOException $e) {
				echo 'ERROR: Couldn\'t connect to Access database.', PHP_EOL;
				if ($this->debug)
					echo 'ERROR: ' . $e->getMessage(), PHP_EOL;
				exit();
			}
		} else {
			echo 'ERROR: You need to add ODBC PDO extension.', PHP_EOL;
			exit();
		}
	}

	/**
	 * Stablishes connection to a MongoDB database.
	 *
	 * @param	string : Hostname:Port
	 * @param 	string : Database Name
	 */
	public function set_mongo_connection($host, $dbname) {
		$driver_available = false;
		foreach (get_loaded_extensions() as $driver)
			if ($driver == 'mongo')
				$driver_available = true;

		if ($driver_available) {

			try {
				$this->mongo_client = new MongoClient("mongodb://$host");
				$this->mongo = $this->mongo_client->$dbname;
				if ($this->info)
					echo 'Connected to MongoDB database.', PHP_EOL;
			} catch (MongoConnectionException $e) {
				echo 'ERROR: Couldn\'t connect to MongoDB database.', PHP_EOL;
				if ($this->debug)
					echo 'ERROR: ' . $e->getMessage(), PHP_EOL;
				exit();
			}

		} else {
			echo 'ERROR: You need to add Mongo extension.', PHP_EOL;
			exit();
		}
	}

	/**
	 * Sets a limit to an SQL query.
	 * Only for testing.
	 *
	 * @param	int : Limit
	 */
	public function set_test_limit($entries = false) {
		if ( is_numeric($entries) && $entries > 0 )
			$this->test_limit = $entries;
		else
			$this->test_limit = false;
	}

	/**
	 * Sets the required fields from the SQL table.
	 * If the parameter is not set, the migration will take
	 * all the fields of the selected table.
	 *
	 * @param	array : SQL-field -> MongoDB-field
	 */
	public function set_fields($fields = null) {
		if ( !is_array($fields) || count($fields) == 0 )
			$this->fields = null;

		$this->fields = $fields;
	}

	/**
	 * Check if empty fields in SQL will be added to MongoDB.
	 *
	 * @param	boolean
	 */
	public function remove_empty_fields($remove = false) {
		$this->empty_fields = $remove;
	}

	/**
	 * Set which fields won't be migrated to MongoDB.
	 *
	 * @param	array : Fields
	 */
	public function remove_keys($keys = null) {
		$this->remove_keys = $keys;
	}

	/**
	 * Stablishes the tables of origin and destination.
	 *
	 * @param	string : SQL table
	 * @param 	string : MongoDB collection
	 */
	public function set_tables($sql = null, $mongo = null) {
		if ( !isset($sql) || !isset($mongo) )
			return false;

		$this->sql_table = $sql;
		$this->mongo_table = $mongo;
	}

	/**
	 * Overrides the standard SQL query.
	 *
	 * @param	string : SQL query
	 */
	public function set_sql_query($sql = null) {
		$this->sql_query = $sql;
	}

	/**
	 * Sets a relationship between the SQL table and the MongoDB collection.
	 *
	 * @param	string : An already available key in MongoDB
	 * @param 	string : SQL key to compare with
	 */
	public function set_mongo_keys($mongo1 = null, $mongo2 = null) {
		if ( !isset($mongo1) || !isset($mongo2) )
			return false;

		$this->mongo1_key = $mongo1;
		$this->mongo2_key = $mongo2;
	}

	/**
	 * Number of inserts / updates per bulk operation in MongoDB.
	 *
	 * @param	integer : Inserts/Updates per bulk
	 */
	public function set_batch_max($max = 100) {
		if (is_numeric($max) && $max > 0)
			$this->batch_max = $max-1;
	}

	private function sql_query($sql) {
		if ($this->debug)
			echo 'SQL Query: ' . $sql, PHP_EOL;

		$dbcall = $this->sqlcon->query($sql);
		if (!$dbcall) {
			echo 'ERROR: There\'s a problem with the SQL query.', PHP_EOL;
			return array();
		}

		$rtrn = $dbcall->fetchAll(PDO::FETCH_ASSOC);

		if ($this->sql_query_callback)
			$rtrn = call_user_func($this->sql_query_callback, $rtrn);

		return $rtrn;
	}

	private function mongo_query($select = array(), $where = array(), $limit = null) {
		if ( is_string($this->mongo_table) )
			$from = $this->mongo_table;
		else
			return;

		$rtrn = $this->mongo->$from->find( $where, $select );

		if ( is_numeric($limit) )
			$rtrn = $rtrn->limit($limit);

		$rtrn = iterator_to_array($rtrn);

		return $rtrn;
	}

	/**
	 * Starts the migration.
	 *
	 * @return	bool : Success
	 */
	public function start() {
		if ( !isset($this->sql_table) || !isset($this->mongo_table) ) {
			echo 'Couldn\'t start migration.', PHP_EOL;
			echo 'Some parameters needed weren\'t set.', PHP_EOL;
			return false;
		}

		echo 'Migration started.', PHP_EOL;
		if (count($this->fields) > 0)
			$select = implode(', ', array_keys($this->fields));
		else
			$select = '*';

		if ($this->sql_query)
			$sql = $this->sql_query;
		else
			$sql = "SELECT $select FROM $this->sql_table";

		if ($this->test_limit > 0)
			$sql = $sql." LIMIT $this->test_limit";

		$rtrn = $this->sql_query($sql);

		if (count($rtrn) == 0)
			return false;

		if (count($this->fields) == 0)
			$this->fields = array_combine(array_keys($rtrn[0]), array_keys($rtrn[0]));

		if ( isset($this->mongo1_key) && isset($this->mongo2_key) ) {
			$batch = new MongoUpdateBatch($this->mongo->selectCollection($this->mongo_table));
			$isUpdate = true;
		} else {
			$batch = new MongoInsertBatch($this->mongo->selectCollection($this->mongo_table));
			$isUpdate = false;
		}

		$count = 0;
		foreach ($rtrn as $result) {
			if ($this->empty_fields) {
				//$result = array_filter($result, 'strlen');
				$result = array_filter($result, function($v) {
					if ( is_array($v) ) {
						$v = array_filter($v, 'strlen');

						if ( count($v) == 0 )
							return false;
						else
							return true;
					}

					if ( $v == null || strlen($v) == 0 || $v == 'null' )
						return false;

					return true;
				});
			}

			$update_values = $this->replace_values($result);

			if ($isUpdate) {
				$mongo2_key_val = array_flip($this->replace_values(array($this->mongo2_key => 0), $result));
				$mongo2_key_val = $update_values[$mongo2_key_val[0]];
			}

			if ($this->remove_keys) {
				$remove_keys = array_flip($this->replace_values(array_flip($this->remove_keys), $result));
				foreach ($remove_keys as $field)
					unset($update_values[$field]);
			}

			if ($isUpdate) {
				$update = array(
					'q' => array($this->mongo1_key => $mongo2_key_val),
					'u' => array('$set' => $update_values),
					'multi' => false,
					'upsert' => true,
				);
				$batch->add($update);
			} else {
				$update_values2 = array();
				foreach ($update_values as $key => $value) {
					$newkey = array();
					$this->set_val($newkey, $key, $value);
					$update_values2 = array_merge_recursive($update_values2, $newkey);
				}
				$batch->add($update_values2);
			}

			unset($update_values);
			if ($count >= $this->batch_max) {
				try {
					$rtrn = $batch->execute();
					if ($this->debug) {
						echo 'Modified: ' . (($rtrn['nModified'])?$rtrn['nModified']:0) . ' documents. Created: ' . ((isset($rtrn['nUpserted']))?$rtrn['nUpserted']:$rtrn['nInserted']) . ' documents. Ok? ' . (($rtrn['ok'])?'Yes.':'No.'), PHP_EOL;
					}
				} catch(MongoException $e) {
					if ($this->info) {
						echo 'Error adding to MongoDB: ' . $e->getMessage() . ' ('. $e->getCode() . ').', PHP_EOL;
					}
				}
				$count = 0;
			} else
				$count++;
		}

		try {
			$rtrn = $batch->execute();
			if ($this->debug) {
				echo 'Modified: ' . (($rtrn['nModified'])?$rtrn['nModified']:0) . ' documents. Created: ' . ((isset($rtrn['nUpserted']))?$rtrn['nUpserted']:$rtrn['nInserted']) . ' documents. Ok? ' . (($rtrn['ok'])?'Yes.':'No.'), PHP_EOL;
			}
		} catch(MongoException $e) {
			if ($this->info) {
				echo 'Error adding to MongoDB: ' . $e->getMessage() . ' ('. $e->getCode() . ').', PHP_EOL;
			}
		}
		return true;
	}

	private $regex = "/{([^}]*)}/i";
	private function replace_values($data, $data2 = null) {
		if ( !isset($data) || !is_array($data) )
			return false;

		foreach ($data as $key => $value) {
			$new_key = $this->fields[$key];
			preg_match_all($this->regex, $new_key, $matches);
			if ( isset($matches[1][0]) )
				foreach ($matches[1] as $replace) {
					if (isset($data2))
						$new_key = str_replace('{'.$replace.'}', $data2[$replace], $new_key);
					else
						$new_key = str_replace('{'.$replace.'}', $data[$replace], $new_key);
				}

			$update_values[$new_key] = $value;
		}
		if ($update_values && $utf8)
			array_walk_recursive($update_values, function(&$item) {
				$item = utf8_encode($item);
			});

		return $update_values;
	}

	private function set_val(array &$arr, $path, $val) {
		$loc = &$arr;
		foreach(explode('.', $path) as $step)
			$loc = &$loc[$step];
		$loc = $val;
		return $arr;
	}

	/**
	 * Shows debug info when running the
	 * method 'start'. Default: false.
	 *
	 * @param 	bool : Activated
	 */
	function set_debug($dbg) {
		$this->debug = ($dbg) ? true : false;

		if ($this->debug)
			$this->info = true;
	}

	/**
	 * Shows debug info when running the
	 * method 'start'. Default: false.
	 *
	 * @param 	bool : Activated
	 */
	function set_info($nfo) {
		$this->info = ($nfo) ? true : false;
	}

	/**
	 * Returns if debug info is set or not.
	 *
	 * @return	bool : Activated
	 */
	function get_debug() {
		return $this->debug;
	}

	/**
	 * Returns if info messages are set or not.
	 *
	 * @return	bool : Activated
	 */
	function get_info() {
		return $this->info;
	}

	/**
	 * Sets a callback function after the SQL
	 * query has returned the results so they
	 * can be modified.
	 *
	 * @param 	function
	 */
	function set_query_callback($callback) {
		$this->sql_query_callback = $callback;
	}

	/**
	 * Converts explicitly any data recovered
	 * from the SQL database to UTF-8.
	 * Default: false.
	 *
	 * @param 	bool : Deactivated
	 */
	function convert_to_utf8($convert) {
		$this->utf8 = ($convert) ? true : false;
	}
}
