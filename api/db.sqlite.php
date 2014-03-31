<?php 

function database_sql_operator($a, $c, $b) {
  if ($c == '=') return $a.' = '.$b;
  if ($c == 'equals') return $a.' = '.$b;
  if ($c == 'contains') {
  	if (strpos("'", $b) == 0) {
  		return $a." like '%".substr($b, 1, strlen($b)-2)."%'";
  	} else {
	  	return $a.' like '.$b;
	  }
  }
  if ($c == '>') return $a.' > '.$b;
  if ($c == '<') return $a.' < '.$b;
  if ($c == '>=') return $a.' >= '.$b;
  if ($c == '<=') return $a.' <= '.$b;
}

function schema_datatype_to_nativetype($type, $meta = null) {
	// http://www.sqlite.org/datatype3.html
  $r = (object)array( // must convert to: TEXT, INTEGER, NONE, REAL, NUMERIC
 		"text" => "TEXT",
 		"string" => "TEXT",
 		"varchar" => "TEXT",
 		"integer" => "INTEGER",
 		"int" => "INTEGER",
 		"smallint" => "INTEGER",
 		"bigint" => "INTEGER",
 		"tinyint" => "INTEGER",
 		"blob" => "NONE",
 		"binary" => "NONE",
 		"real" => "REAL",
 		"double" => "REAL",
 		"float" => "REAL",
 		"numeric" => "NUMERIC",
 		"boolean" => "NUMERIC",
 		"decimal" => "NUMERIC",
 		"date" => "NUMERIC",
 		"datetime" => "NUMERIC"
	);
	return $r->{strtolower($type)};
}

function schema_escape_value($value, $field) {
	$st = schema_datatype_to_nativetype($field->type);
	$r = null;
	if ($st == 'TEXT') $r = '\''.SQLite3::escapeString(/*stripslashes(*/$value).'\'';
	if ($st == 'INTEGER') $r = intval($value);
	if ($st == 'BLOB') $r = ''.$value.'';
	if ($st == 'REAL') $r = ''.forcefloat($value, array('single_dot_as_decimal'=> TRUE)).'';
	if ($st == 'NUMERIC') $r = '\''.SQLite3::escapeString($value).'\''; // validation of input is done before here (or should be), so for dates we will just shove it in as a string

	if (strtolower($field->type) == 'boolean') $r = (boolval($value) ? 1 : 0);

	return $r;
}

/**
* Connection
*
* Maintains a connection to the database. Contains functions to interact, query, and manipulate the database.
* This class should be able to act as a singleton (by default).
*
* @author		Pat Cullen
*/
class Connection {

	private $connection;
	
	/**
	* @about	wadda
	* @return 	Connection
	*/
	public function Connection() {
		global $schema;
		$this->connection = new SQLite3($schema->settings->database->filename);
	}

	/**
	* @about	Executes a sql statement and returns a Resultset object.
	* @param1	The SQL statement to execute.
	* @return	Resultset
	*/
	function query($sql) {
		//echo $sql."<br />";
		return new Resultset($this->connection->query($sql));
	}

	/**
	* @about	Executes a sql statement.
	* @param1	The SQL statement to execute. 
	* @return	void
	*/
	function execute($sql) {
//		echo $sql."===<br />";
		$this->connection->query($sql);
	}
	
	/**
	* @about	Executes a sql statement.
	* @param1	The SQL statement to execute. 
	* @return	void
	*/
	function exec($sql) {
		$this->execute($sql);
	}
	
	/**
	* @about	Executes a sql statement.
	* @param1	The SQL statement to execute. 
	* @return	void
	*/
	function update($sql) {
		$this->execute($sql);
	}
	
	/**
	* @about	inserts some data into a table
	* @param1	the table name
	* @param2	an array of field names
	* @param2	an array of field data
	* @param3	flag to return the new id
	* @return	void
	*/
	function insert($table, $field, $data, $flag = true, $returnSQL = false, $id = null) {
		$sqlfield = ($returnSQL && $id != null ? "id" : "");
		$sqldata = ($returnSQL && $id != null ? $id : "");
		foreach($field as $f)
			$sqlfield .= ($sqlfield == "" ? "" : ", ") . "".$f."";
		foreach($data as $f)
			$sqldata .= ($sqldata == "" ? "" : ", ") . $f;
		$sql = "INSERT INTO $table ($sqlfield) VALUES ($sqldata);";
		if ($returnSQL) {
			return $sql;
		} else {
			$this->execute($sql);
			if ($flag) { // 
				$sqlwhere = "";
				for ($i = 0; $i < sizeof($field); $i++) {
					$e = "(" . $field[$i]. "=" . $data[$i] . ")";
					if ($data[$i] == "null")
						$e = "(" . $field[$i]. " IS NULL)";
					$sqlwhere .= ($sqlwhere == "" ? "" : " AND ") . $e;
				}
				$t = $this->getValue("SELECT MAX(id) AS id FROM $table", "id");
				return $t;
			}
			return null;
		}
	}
	
	function updatef($table, $field, $data, $id, $returnSQL = false) {
		$sqlfield = "";
		for($i = 0; $i < sizeof($field); $i++)
			$sqlfield .= ($sqlfield == "" ? "" : ", ") . "".$field[$i]." = ".$data[$i] ;
		$sql = "UPDATE $table SET $sqlfield WHERE id = $id;";
		if ($returnSQL)
			return $sql;
		else {
			$this->execute($sql);
			return $id;
		}
	}
	
	function push($table, $field, $data, $id, $returnSQL = false) {
		if ((!isset($id)) || ($id == "") || ($id == "-1") || $returnSQL) {
			return $this->insert($table, $field, $data, true, $returnSQL, $id);
		} else {
			return $this->updatef($table, $field, $data, $id, $returnSQL);
		}
	}
	
	function hasTable($table) {
    $result = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
		if ($result->numColumns() && $result->columnType(0) != SQLITE3_NULL) { 
			return true; 
		} else { 
			return false; 
		}     
	}	
	
	function dropTable($table) {
    $result = $this->execute("DROP TABLE $table;");
	}	
	
	/**
	* @about	Cleans up after using this connection.
	* @return	void
	*/
	function close() {
		sqlite_close($connection);
	}
	
	
	/**
	* @about	runs a sql query and returns the result as a object or array, or an array of either.
	* @param1	The SQL statement to execute. 
	* @return	void
	*/
	function get($sql, $type = 'object') {
		$r = array();
		$rs = $this->query($sql);
		foreach ($rs->result($type) as $temp) {
			$r[] = $temp;
		}
		if (sizeof($r) == 1) {
			return $r[0];
		}
		if (sizeof($r) == 0) {
			return null;
		}
		return $r;
	}

	/**
	* @about	runs a sql query and returns the result as a object or array, or an array of either.
	* @param1	The SQL statement to execute. 
	* @param1	The column to copy value from. 
	* @return	void
	*/
	function getValue($sql, $column = null) {
		if (isset($column)) {
			return $this->query($sql)->getValue($column);
		} else {
			return $this->query($sql)->getValue(0);
		}
	}

	/**
	* @about	runs a sql query and returns the result as an array of objects
	* @param1	The SQL statement to execute. 
	* @return	array
	*/
	function getObjects($sql) {
		$r = array();
		$rs = $this->query($sql);
		foreach ($rs->result('object') as $temp) {
			$r[] = $temp;
		}
		return $r; 
	}

	/**
	* @about	runs a sql query and returns the result as a object. ie: only returns first row.
	* @param1	The SQL statement to execute. 
	* @return	object
	*/
	function getObject($sql) {
		$r = $this->getObjects($sql);
		if (sizeof($r) > 0) {
			return $r[0];
		}
		return null;
	}

	/**
	* @about	runs a sql query and returns the result as an array of arrays.
	* @param1	The SQL statement to execute. 
	* @return	array
	*/
	function getArrays($sql) {
		$r = array();
		$rs = $this->query($sql);
		foreach ($rs->result('array') as $temp) {
			$r[] = $temp;
		}
		return $r; 
	}

	/**
	* @about	runs a sql query and returns the result as an array. ie: only returns first row.
	* @param1	The SQL statement to execute. 
	* @return	array
	*/
	function getArray($sql) {
		$r = $this->getArrays($sql);
		if (sizeof($r) > 0) {
			return $r[0];
		}
		return null;
	}

/*	FUNTIONS TO IMPLEMENT IN THE FUTURE::
	function insert($table, $data)
	function update($table, $data, $where)

	function trans_start($test_mode = FALSE)
	function trans_complete()
	function trans_status()

	function list_databases()
	function optimize_table($table_name)
	function optimize_database()
	function repair_table($table_name)
	function backup($params = array())

	*/
}



/**
* Resultset
*
* A resultset is produced from a connection object. Abstracts the usual stuff in a resultset.
*
* @author		Pat Cullen
*/
class Resultset {

	private $resultset;

	/**
	* @about	Wrapper for a resultset object.
	* @return 	Resultset
	*/
	function Resultset($rs) {
		$this->resultset = $rs;
		//print_r($this->resultset);
	}

	/**
	* @about	Returns the resultset in form of an array of objects or arrays.
	* @param1	'object' or 'array'
	* @return	Object or Array
	*/
	function result($type = 'object') {
		$r = array();
		//$this->goto_row(0);
		while ($res = $this->resultset->fetchArray(SQLITE3_ASSOC)) {
			if ($type == 'object')
				$r[] = (object)$res;
			else
				$r[] = $res;
		}
		return $r; 
	}

	/**
	* @about	returns the current row as a object or array.
	* @param1	The SQL statement to execute. 
	* @return	void
	*/
	function get($type = 'object') {
		if ($type == 'object')
			return $this->getObject();
		else
			return $this->getArray();
	}

	/**
	* @about	returns the value of a certain field.
	* @param1	The SQL statement to execute. 
	* @param1	The column to copy value from. 
	* @return	void
	*/
	function getValue($column) {
		$r = $this->getArray();
		if (isset($r)) {
			return $r[$column];
		}
		return null; 
	}

	/**
	* @about	returns the current row as a object
	* @return	object
	*/
	function getObject() {
		return (object)$this->getArray();
	}

	/**
	* @about	returns the current row as an array
	* @return	array
	*/
	function getArray() {
		return $this->resultset->fetchArray(SQLITE3_ASSOC);
	}

	/**
	* @about	Cleans up.
	* @return	void
	*/
	function close() {

	}

}

function _bool($d) {
	if (isset($d)) {
		if (is_bool($d)) {
			return $d;
		} else {
			return "".$d == "true";
		}
	} else {
		return false;
	}
}

// general escape function for writing to sql 
function _sql($d) {
	if (isset($d)) {
		$d = stripslashes($d);
		if (is_string($d))
			return "'".sqlite_escape_string($d)."'";
		if (is_bool($d))
			return $d ? "true" : "false";
		return "".$d;
	} else {
		return "null";
	}
}

// escape function for writing numbers to sql
function _sqln($d) {
	if (isset($d)) {
		if ($d == 0)
			return "0";
		if ($d == "")
			return "null";
		else 
			return "".$d;
	} else {
		return "null";
	}
}

    function forcefloat($str, $set=FALSE) {            
        if(preg_match("/([0-9\.,-]+)/", $str, $match)) {
            // Found number in $str, so set $str that number
            $str = $match[0];
            
            if(strstr($str, ',')) {
                // A comma exists, that makes it easy, cos we assume it separates the decimal part.
                $str = str_replace('.', '', $str);    // Erase thousand seps
                $str = str_replace(',', '.', $str);    // Convert , to . for floatval command
                
                return floatval($str);
            } else {
                // No comma exists, so we have to decide, how a single dot shall be treated
                if(preg_match("/^[0-9]*[\.]{1}[0-9-]+$/", $str) == TRUE && $set['single_dot_as_decimal'] == TRUE){
                    // Treat single dot as decimal separator
                    return floatval($str);
                } else {
                    // Else, treat all dots as thousand seps
                    $str = str_replace('.', '', $str);    // Erase thousand seps
                    return floatval($str);
                }                
            }
        } else {
            // No number found, return zero
            return 0;
        }
    }
    
    
function boolval($in, $strict=false) {
    $out = null;
    $in = (is_string($in)?strtolower($in):$in);
    // if not strict, we only have to check if something is false
    if (in_array($in,array('false','no', 'n','0','off',false,0), true) || !$in) {
        $out = false;
    } else if ($strict) {
        // if strict, check the equivalent true values
        if (in_array($in,array('true','yes','y','1','on',true,1), true)) {
            $out = true;
        }
    } else {
        // not strict? let the regular php bool check figure it out (will
        //     largely default to true)
        $out = ($in?true:false);
    }
    return $out;
}
    
    

$db = new Connection();

?>