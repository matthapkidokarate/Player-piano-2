<?php
class DB{

	# set varibles

	# set self instance for copying
	private static $_instance = null;

	# setting private variables for use in returning results
	private $_pdo,
		$_query,
		$_error = false,
		$_results,
		$_count = 0;

	# connect to db when class is instanced and store in $this->_pdo
	/**
		Takes in a database value and outputs a dataobject with a logged in user
	*/
	private function __construct($database = null)
	{
		#try loop to start the pdo that catches PDOEXexption
		try
		{

			# creats a new pdo object
			$this->_pdo = new PDO('mysql:host=' . Config::get('mysql/host') . ';dbname=' . $database,
						Config::get('mysql/username'),
						Config::get('mysql/password')
					);
		//echo 'connected';
		}
		# catch the PDO exception
		catch(PDOException $e)
		{
			die($e->getMessage());
		}
	}

	/**
		Returns the current PHP object stored in memory
	*/
	# init instance for the database and store the __construct here
	public static function getInstance($dbase = null)
	{

		# test if the self instance exists and if not, create its self and return itsself
		if(!isset(self::$_instance) && isset($dbase))
		{
			self::$_instance = new DB($dbase);
		}

		return self::$_instance;
	}

	public function query($sql, $params = array())
	{
		#reset error variable
		$this->_error = false;

		#prepare sql for pdo
		if($this->_query = $this->_pdo->prepare($sql))
		{
			//echo 1;

			# build sql additions
			if(count($params))
			{
				$x = 1;
				foreach($params as $param)
				{
					//echo $x;
					$this->_query->bindValue($x, $param);
					$x++;

				}

			}

			# execute query and test for it
			if($this->_query->execute())
			{

				# Successful Query
				$this->_results	= $this->_query->fetchAll(PDO::FETCH_OBJ);
				$this->_count	= $this->_query->rowCount();

			}else{
				$this->_count = 0;
				$this->_error = true;

			}

		}else{
			$this->_error = true;
			return $this;
		}

		return $this;

	}

	# call the query and return a nice result set
	public function action($action, $table, $where = array())
	{

		if(count($where) == 3)
		{

			$operators = array('=', '>', '<', '>=', '<=');

			$field		= $where[0];
			$operator	= $where[1];
			$value		= $where[2];

			if(in_array($operator, $operators))
			{

				$sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
				if(!$this->query($sql, array($value))->error())
				{
					# echo "worked";
					return $this;
				}

			}else{

				echo "<h2>SQL Query Operator \"{$operator}\" is not within the list:</h2><ul>";

				foreach ($operators as $key) {
					echo "<li>{$key}</li>";
				}

				echo "</ul>";

				$this->error = true;
				return null;

			}

		}
		# Query failed
		return $this;

	}

	# get from db
	public function get($table, $where)
	{
		return $this->action('SELECT *', $table, $where);
	}

	# delete from db
	public function delete($table, $where)
	{
		return $this->action('DELETE', $table, $where);
	}

	# get results from last query
	public function results()
	{
		return $this->_results;
	}

	# insert into db
	public function insert($table, $fields = array())
	{

		$keys 	= array_keys($fields);
		$values = '';
		$counter = 1;
		foreach ($fields as $field)
		{
			$values .= "?";
			if ($counter<count($fields))
			{
				$values .= ', ';
			}
			$counter++;
		}
		$sql = "INSERT INTO {$table} (`" . implode('`,`', $keys) . "`) VALUES ({$values})";
		# die($sql);
		if (!$this->query($sql, $fields)->error())
		{
			return true;# code...
		}

		return false;
	}

	public function update($table, $id, $fields)
	{
		$set = '';
		$x=1;

		foreach ($fields as $name => $value) {
			$set .= "{$name} = ?";

			if ($x < count($fields))
			{
				$set .= ', ';
			}

			$x++;
		}

		$sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";

		if (!$this->query($sql, $fields)->error())
		{
			return true;# code...
		}

		return false;
	}

	# get first record in db
	public function first()
	{
		return $this->_results[0];
	}

	# get errors
	public function error()
	{
		return $this->_error;
	}

	# count entries
	public function count()
	{
		return $this->_count;
	}
}
