<?php

class DB_Model extends FHC_Model
{
	const PGSQL_ARRAY_TYPE = '_';
	const PGSQL_BOOLEAN_TYPE = 'bool';
	const PGSQL_BOOLEAN_ARRAY_TYPE = '_bool';
	const PGSQL_BOOLEAN_TRUE = 't';
	const PGSQL_BOOLEAN_FALSE = 'f';
	const MODEL_POSTFIX = '_model';
	
	protected $dbTable;  	// Name of the DB-Table for CI-Insert, -Update, ...
	protected $pk;  		// Name of the PrimaryKey for DB-Update, Load, ...
	protected $hasSequence;	// False if this table has a composite primary key that is not using a sequence
							// True if this table has a primary key that uses a sequence
	
	function __construct($dbTable = null, $pk = null, $hasSequence = true)
	{
		parent::__construct();
		$this->dbTable = $dbTable;
		$this->pk = $pk;
		$this->hasSequence = $hasSequence;
		$this->load->database();
	}
	
	/** ---------------------------------------------------------------
	 * Insert Data into DB-Table
	 *
	 * @param   array $data  DataArray for Insert
	 * @return  array
	 */
	public function insert($data)
	{
		// Check Class-Attributes
		if (is_null($this->dbTable))
			return error(FHC_MODEL_ERROR, FHC_NODBTABLE);
		
		// Checks rights
		if ($isEntitled = $this->_isEntitled(PermissionLib::INSERT_RIGHT)) return $isEntitled;
		
		// DB-INSERT
		if ($this->db->insert($this->dbTable, $data))
		{
			// If the table has a primary key that uses a sequence
			if ($this->hasSequence === true)
			{
				return success($this->db->insert_id());
			}
			// Avoid to use method insert_id() from CI because it forces to have a sequence
			// and doesn't return the primary key when it's composed by more columns
			else
			{
				$primaryKeysArray = array();

				foreach ($this->pk as $key => $value)
				{
					if (isset($data[$value]))
					{
						$primaryKeysArray[$value] = $data[$value];
					}
				}

				return success($primaryKeysArray);
			}
		}
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}

	/** ---------------------------------------------------------------
	 * Replace Data in DB-Table
	 *
	 * @param   array $data  DataArray for Replacement
	 * @return  array
	 */
	public function replace($data)
	{
		// Check Class-Attributes
		if (is_null($this->dbTable))
			return error(FHC_MODEL_ERROR, FHC_NODBTABLE);
		
		// Checks rights
		if ($isEntitled = $this->_isEntitled(PermissionLib::REPLACE_RIGHT)) return $isEntitled;

		// DB-REPLACE
		if ($this->db->replace($this->dbTable, $data))
			return success($this->db->insert_id());
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}

	/** ---------------------------------------------------------------
	 * Update Data in DB-Table
	 *
	 * @param   string $id  PK for DB-Table
	 * @param   array $data  DataArray for Insert
	 * @return  array
	 */
	public function update($id, $data)
	{
		// Check Class-Attributes
		if (is_null($this->dbTable))
			return error(FHC_MODEL_ERROR, FHC_NODBTABLE);
		if (is_null($this->pk))
			return error(FHC_MODEL_ERROR, FHC_NOPK);
		
		// Checks rights
		if ($isEntitled = $this->_isEntitled(PermissionLib::UPDATE_RIGHT)) return $isEntitled;

		// DB-UPDATE
		// Check for composite Primary Key
		if (is_array($id))
		{
			if (isset($id[0]))
				$this->db->where($this->_arrayMergeIndex($this->pk, $id));
			else
				$this->db->where($id);
		}
		else
			$this->db->where($this->pk, $id);
		if ($this->db->update($this->dbTable, $data))
			return success($id);
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}
	
	/** ---------------------------------------------------------------
	 * Delete data from DB-Table
	 *
	 * @param   string $id  Primary Key for DELETE
	 * @return  array
	 */
	public function delete($id)
	{
		// Check Class-Attributes
		if (is_null($this->dbTable))
			return error(FHC_MODEL_ERROR, FHC_NODBTABLE);
		if (is_null($this->pk))
			return error(FHC_MODEL_ERROR, FHC_NOPK);
		
		// Checks rights
		if ($isEntitled = $this->_isEntitled(PermissionLib::DELETE_RIGHT)) return $isEntitled;

		// DB-DELETE
		// Check for composite Primary Key
		if (is_array($id))
		{
			if (isset($id[0]))
				$result = $this->db->delete($this->dbTable, $this->_arrayMergeIndex($this->pk, $id));
			else
				$result = $this->db->delete($this->dbTable, $id);
		}
		else
			$result = $this->db->delete($this->dbTable, array($this->pk => $id));
		if ($result)
			return success($id);
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}

	/** ---------------------------------------------------------------
	 * Load single data from DB-Table
	 *
	 * @param   string $id  ID (Primary Key) for SELECT ... WHERE
	 * @return  array
	 */
	public function load($id = null)
	{
		// Check Class-Attributes
		if (is_null($this->dbTable))
			return error(FHC_MODEL_ERROR, FHC_NODBTABLE);
		if (is_null($this->pk))
			return error(FHC_MODEL_ERROR, FHC_NOPK);
		
		// Checks rights
		if ($isEntitled = $this->_isEntitled(PermissionLib::SELECT_RIGHT)) return $isEntitled;
		
		// DB-SELECT
		// Check for composite Primary Key
		if (is_array($id))
		{
			if (isset($id[0]))
				$result = $this->db->get_where($this->dbTable, $this->_arrayMergeIndex($this->pk, $id));
			else
				$result = $this->db->get_where($this->dbTable, $id);
		}
		elseif (empty($id))
			$result = $this->db->get($this->dbTable);
		else
			$result = $this->db->get_where($this->dbTable, array($this->pk => $id));
		
		if ($result)
			return success($this->toPhp($result));
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}

	/** ---------------------------------------------------------------
	 * Load data from DB-Table with a where clause
	 *
	 * @return  array
	 */
	public function loadWhere($where = null)
	{
		// Check Class-Attributes
		if (is_null($this->dbTable))
			return error(FHC_MODEL_ERROR, FHC_NODBTABLE);
		
		// Checks rights
		if ($isEntitled = $this->_isEntitled(PermissionLib::SELECT_RIGHT)) return $isEntitled;
		
		// Execute query
		$result = $this->db->get_where($this->dbTable, $where);
		
		if ($result)
			return success($this->toPhp($result));
		else
			return error($this->db->error(), FHC_DB_ERROR);
	}
	
	/** ---------------------------------------------------------------
	 * Load data and convert a record into a list of data from the main table,
	 * and linked to every element, the data from the side tables
	 *
	 * TODO:
	 * - Adding support for composed primary key
	 * - Adding support for cascading side tables (useful?)
	 *
	 * @return  array
	 */
	public function loadTree($mainTable, $sideTables, $where = null, $sideTablesAliases = null)
	{
		// Check Class-Attributes
		if (is_null($this->dbTable))
			return error(FHC_MODEL_ERROR, FHC_NODBTABLE);
		
		// Checks rights
		if ($isEntitled = $this->_isEntitled(PermissionLib::SELECT_RIGHT)) return $isEntitled;
		
		// List of tables on which it will work
		$tables = array_merge(array($mainTable), $sideTables);
		// Array that will contain the number of columns of each table
		$tableColumnsCountArray = array();
		
		// Generates the select clause based on the columns of each table
		$select = '';
		for ($t = 0; $t < count($tables); $t++)
		{
			$fields = $this->db->list_fields($tables[$t]); // list of the columns of the current table
			for ($f = 0; $f < count($fields); $f++)
			{
				// To avoid overwriting of the properties within the object returned by CI
				// will be given an alias to every column, that will be composed with the following schema
				// <table name>.<column name> AS <table_name>_<column name>
				$select .= $tables[$t] . '.' . $fields[$f] . ' AS ' . $tables[$t] . '_' . $fields[$f];
				if ($f < count($fields) - 1) $select .= ', ';
			}
			
			if ($t < count($tables) - 1) $select .= ', ';
			
			$tableColumnsCountArray[$t] = count($fields);
		}
		
		// Adds the select clause
		$this->addSelect($select);
		
		// Execute the query
		$resultDB = $this->db->get_where($this->dbTable, $where);
		// If everything went ok...
		if ($resultDB)
		{
			// Converts the object that contains data, from the returned CI's object to an array
			// with the postgresql array and boolean types converterd
			$resultArray = $this->toPhp($resultDB);//var_dump($resultArray);
			// Array that will contain all the mainTable records, and to each record the linked data
			// of a side table
			$returnArray = array();
			$returnArrayCounter = 0;	// Array counter
			
			// Iterates the array that contains data from DB
			for ($i = 0; $i < count($resultArray); $i++)
			{
				// Converts an object properties to an associative array
				$objectVars = get_object_vars($resultArray[$i]);
				// Temporary array that will contain a representation of every records returned from DB
				// every element is an associative array that contains all the data of each table
				$objTmpArray = array();
				$tableColumnsCountArrayOffset = 0; // Columns offset
				// Gets all the data of a single table from the returned record, and creates an object filled with these data
				for ($f = 0; $f < count($tableColumnsCountArray); $f++)
				{
					$objTmpArray[$f] = new stdClass(); // Object that will represent a data set of a table
					
					foreach (array_slice($objectVars, $tableColumnsCountArrayOffset, $tableColumnsCountArray[$f]) as $key => $value)
					{
						$objTmpArray[$f]->{str_replace($tables[$f] . '_', '', $key)} = $value;
					}
					
					$tableColumnsCountArrayOffset += $tableColumnsCountArray[$f]; // Increasing the offset
				}
				
				// Object that represents data of the main table
				$mainTableObj = $objTmpArray[0];
				// Fill $returnArray with all data from mainTable, and for each element will link the data from the side tables
				for ($t = 1; $t < count($tables); $t++)
				{
					// Object that represents data of the side table
					$sideTableObj = $objTmpArray[$t];
					$sideTableProperty = $tables[$t];
					if (is_array($sideTablesAliases))
					{
						$sideTableProperty = $sideTablesAliases[$t - 1];
					}
					
					// If the side table has data. If it was used a left join all the properties could be null
					// NOTE: Keep this way to be compatible with a php version older than 5.5
					$tmpFilteredArray = array_filter(get_object_vars($sideTableObj));
					if (isset($tmpFilteredArray) && count($tmpFilteredArray) > 0)
					{
						if (($k = $this->findMainTable($mainTableObj, $returnArray)) === false)
						{
							$mainTableObj->{$sideTableProperty} = array($sideTableObj);
							$returnArray[$returnArrayCounter++] = $mainTableObj;
						}
						else
						{
							if (!isset($returnArray[$k]->{$sideTableProperty}))
							{
								$returnArray[$k]->{$sideTableProperty} = array($sideTableObj);
							}
							else if (array_search($sideTableObj, $returnArray[$k]->{$sideTableProperty}) === false)
							{
								array_push($returnArray[$k]->{$sideTableProperty}, $sideTableObj);
							}
						}
					}
				}
			}
			
			// Sets result with the standard success object that contains all the studiengang
			$result = success($returnArray);
		}
		else
		{
			$result = error($resultDB);
		}
		
		return $result;
	}
	
	/** ---------------------------------------------------------------
	 * Add a table to join with
	 *
	 * @return  void
	 */
	public function addJoin($joinTable = null, $cond = null, $type = '')
	{
		// Check parameters
		if (is_null($joinTable) || is_null($cond) || !in_array($type, array('', 'LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER')))
			return error(FHC_MODEL_ERROR, FHC_MODEL_ERROR);
		
		$this->db->join($joinTable, $cond, $type);
		
		return success(true);
	}
	
	/** ---------------------------------------------------------------
	 * Add order clause
	 *
	 * @return  void
	 */
	public function addOrder($field = null, $type = 'ASC')
	{
		// Check Class-Attributes and parameters
		if (is_null($field) || !in_array($type, array('ASC', 'DESC')))
			return error(FHC_MODEL_ERROR, FHC_MODEL_ERROR);
		
		$this->db->order_by($field, $type);
		
		return success(true);
	}
	
	/** ---------------------------------------------------------------
	 * Add select clause
	 *
	 * @return  void
	 */
	public function addSelect($select, $escape = true)
	{
		// Check Class-Attributes and parameters
		if (is_null($select) || $select == '')
			return error(FHC_MODEL_ERROR, FHC_MODEL_ERROR);
		
		$this->db->select($select, $escape);
		
		return success(true);
	}
	
	/** ---------------------------------------------------------------
	 * Add distinct clause
	 *
	 * @return  void
	 */
	public function addDistinct()
	{
		$this->db->distinct();
	}
	
	/** ---------------------------------------------------------------
	 * Add limit clause
	 *
	 * @return  void
	 */
	public function addLimit($start = null, $end = null)
	{
		// Check Class-Attributes and parameters
		if (!is_numeric($start) || (is_numeric($start) && $start <= 0))
			return error(FHC_MODEL_ERROR, FHC_MODEL_ERROR);
		
		if (is_numeric($end) && $end > $start)
		{
			$this->db->limit($start, $end);
		}
		else
		{
			$this->db->limit($start);
		}
		
		return success(true);
	}
	
	/** ---------------------------------------------------------------
	 * Reset the query builder state
	 *
	 * @return  void
	 */
	public function resetQuery()
	{
		$this->db->reset_query();
	}
	
	/** ---------------------------------------------------------------
	 * This method call the method escape from class CI_DB_driver, therefore:
	 * this method determines the data type so that it can escape only string data.
	 * It also automatically adds single quotes around the data so you don’t have to
	 *
	 * @return  void
	 */
	public function escape($value)
	{
		return $this->db->escape($value);
	}

	/** ---------------------------------------------------------------
	 * Convert PG-Boolean to PHP-Boolean
	 *
	 * @param   char	$b	PG-Char to convert
	 * @return  bool
	 */
	public function pgBoolPhp($val)
	{
		// If true
		if ($val == DB_Model::PGSQL_BOOLEAN_TRUE)
		{
			return true;
		}
		// If false
		else if ($val == DB_Model::PGSQL_BOOLEAN_FALSE)
		{
			return false;
		}
		
		// If it is null, let it be null
		return $val;
	}

	/** ---------------------------------------------------------------
	 * Convert PG-Array to PHP-Array
	 *
	 * @param   string	$s		PG-String to convert
	 * @param   string	$start	start-point for recursive iterations
	 * @param   string	$end	end-point for recursive iterations
	 * @return  array
	 */
	public function pgArrayPhp($s, $start=0, &$end=NULL)
	{
		if (empty($s) || $s[0]!='{') return NULL;
		$return = array();
		$br = 0;
		$string = false;
		$quote='';
		$len = strlen($s);
		$v = '';
		for ($i=$start+1; $i<$len;$i++)
		{
		    $ch = $s[$i];
		    if (!$string && $ch=='}')
			{
		        if ($v!=='' || !empty($return))
					$return[] = $v;
		        $end = $i;
		        break;
		    }
			else
				if (!$string && $ch=='{')
				    $v = $this->pgArrayPhp($s,$i,$i);
				else
					if (!$string && $ch==',')
					{
				    	$return[] = $v;
				    	$v = '';
					}
					else
						if (!$string && ($ch=='\'' || $ch=='\''))
						{
							$string = true;
							$quote = $ch;
						}
						else
							if ($string && $ch==$quote && $s[$i-1]=='\\')
								$v = substr($v,0,-1).$ch;
							else
								if ($string && $ch==$quote && $s[$i-1]!='\\')
									$string = FALSE;
								else
									$v .= $ch;
		}
		return $return;
	}
	
	/**
	* Converts from PostgreSQL array to php array
	* It also takes care about array of booleans
	*/
	public function pgsqlArrayToPhpArray($string, $booleans = false)
	{
		// At least returns an empty array
		$result = array();
		
		// String that represents the pgsql array, better if not empty
		if (!empty($string))
		{
			// Magic convertion
			preg_match_all(
				'/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|[0-9]+|x[0-9a-f]+))*)"\s*)(,|(?<!^\{)(?=\}$))/i',
				$string,
				$matches,
				PREG_SET_ORDER
			);
			foreach ($matches as $match)
			{
				// Single element of the array
				$tmp = $match[3] != '' ? stripcslashes($match[3]) : (strtolower($match[2]) == 'null' ? null : $match[2]);
				// If it is an array of booleans, then converts the single element
				if ($booleans === true)
				{
					$tmp = $this->pgBoolPhp($tmp);
				}
				// Adds it to the result array
				$result[] = $tmp;
			}
		}
		
		return $result;
	}
	
	/** ---------------------------------------------------------------
	 * Invalid ID
	 *
	 * @param   array $i	Array with indexes.
	 * @param   array $v	Array with values.
	 * @return  array
	 */
	protected function _arrayMergeIndex($i,$v)
	{
		if (count($i) != count($v))
			return false;
		for ($j=0; $j < count($i); $j++)
			$a[$i[$j]] = $v[$j];
		return $a;
	}
	
	/**
	 * Executes a query and converts array and boolean data types from PgSql to php
	 * @return: boolean false on failure
	 *			boolean if the query is of the write type (INSERT, UPDATE, DELETE...)
	 *			array that represents DB data
	 */
	protected function execQuery($query, $parametersArray = null)
	{
		$result = null;
		
		// If the query is empty don't lose time
		if (!empty($query))
		{
			// If there are parameters to bind to the query
			if (is_array($parametersArray) && count($parametersArray) > 0)
			{
				$resultDB = $this->db->query($query, $parametersArray);
			}
			else
			{
				$resultDB = $this->db->query($query);
			}
			
			// If no errors occurred
			if ($resultDB)
			{
				$result = success($this->toPhp($resultDB));
			}
			else
			{
				$result = error($this->db->error(), FHC_DB_ERROR);
			}
		}
		
		return $result;
	}
	
	/**
	 * Checks if the caller is entitled to perform this operation with this right
	 */
	private function _isEntitled($permission)
	{
		// If the caller is _not_ a model _and_ tries to read data, then avoids to check permissions
		// Otherwise checks always the permissions
		if (($permission == PermissionLib::SELECT_RIGHT &&
			substr(get_called_class(), -6) == DB_Model::MODEL_POSTFIX) ||
			$permission != PermissionLib::SELECT_RIGHT)
		{
			if (($isEntitled = $this->isEntitled($this->dbTable, $permission, FHC_NORIGHT, FHC_MODEL_ERROR)) !== true)
			{
				return $isEntitled;
			}
		}
	}
	
	/**
	 * Converts array and boolean data types from PgSql to php
	 * NOTE: PostgreSQL php drivers returns:
	 * - A boolean value if the query is of the write type (INSERT, UPDATE, DELETE...)
	 * - A FALSE value on failure
	 * - Otherwise an object filled with data on success
	 */
	private function toPhp($result)
	{
		$toPhp = $result; // if there is nothing to convert then return the result from DB
		
		// If it's an object its fields will be parsed to find booleans and arrays types
		if (is_object($result))
		{
			$toBeConverterdArray = array(); // Fields to be converted
			$metaDataArray = $result->field_data(); // Fields information
			for($i = 0; $i < count($metaDataArray); $i++) // Looking for booleans and arrays
			{
				// If array type or boolean type
				if (strpos($metaDataArray[$i]->type, DB_Model::PGSQL_ARRAY_TYPE) !== false ||
					$metaDataArray[$i]->type == DB_Model::PGSQL_BOOLEAN_TYPE)
				{
					// Name and type of the field to be converted
					$toBeConverted = new stdClass();
					// Set the type of the field to be converted
					$toBeConverted->type = $metaDataArray[$i]->type;
					// Set the name of the field to be converted
					$toBeConverted->name = $metaDataArray[$i]->name;
					// Add the field to be converted to $toBeConverterdArray
					array_push($toBeConverterdArray, $toBeConverted);
				}
			}
			
			// If there is something to convert, otherwhise don't lose time
			if (count($toBeConverterdArray) > 0)
			{
				// Returns the array of objects, each of them represents a DB record
				$resultsArray = $result->result();
				// Looping on results
				for($i = 0; $i < count($resultsArray); $i++)
				{
					// Single element
					$tmpResult = $resultsArray[$i];
					// Looping on fields to be converted
					for($j = 0; $j < count($toBeConverterdArray); $j++)
					{
						// Single element
						$toBeConverted = $toBeConverterdArray[$j];
						
						// Array type
						if (strpos($toBeConverted->type, DB_Model::PGSQL_ARRAY_TYPE) !== false)
						{
							$tmpResult->{$toBeConverted->name} = $this->pgsqlArrayToPhpArray(
								$tmpResult->{$toBeConverted->name},
								$toBeConverted->type == DB_Model::PGSQL_BOOLEAN_ARRAY_TYPE
							);
						}
						// Boolean type
						else if ($toBeConverted->type == DB_Model::PGSQL_BOOLEAN_TYPE)
						{
							$tmpResult->{$toBeConverted->name} = $this->pgBoolPhp($tmpResult->{$toBeConverted->name});
						}
					}
				}
				// Returns DB data as an array
				$toPhp = $resultsArray;
			}
			// And returns DB data as an array
			else
			{
				$toPhp = $result->result();
			}
		}
		
		return $toPhp;
	}
	
	/**
	 * Used in loadTree to find the main tables
	 */
	private function findMainTable($mainTableObj, $mainTableArray)
	{
		for ($i = 0; $i < count($mainTableArray); $i++)
		{
			if ($mainTableObj->{$this->pk} == $mainTableArray[$i]->{$this->pk})
			{
				return $i;
			}
		}
		
		return false;
	}
}