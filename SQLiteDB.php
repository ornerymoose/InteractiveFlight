<?php
/***************************************************************************************************************************
 * USAGE: IMPORTS A DATABASE SCHEMA, INSERTS A ROW INTO THE config TABLE, THEN READS/PRINTS THE CONTENTS OF THE config TABLE
 *
 *  //INSTANTIATE CLASS
 * $sqlite_obj = new SQLiteDB("test_navigator.db");
 *
 * //IMPORT A DATABASE
 * $sqlite_obj->importDB("navigator.sql");
 *
 * //EXECUTE SQL STATEMENTS
 * $sqlite_obj->execDB("INSERT INTO config VALUES('0', '333', '444')");
 *
 * //QUERY DATABASE FOR DATA
 * $result = $sqlite_obj->queryDB("SELECT * FROM config");
 *
 * //LOOP THROUGH QUERY RESULTS
 * foreach($result as $row)
 * {
 *	print_r($row);
 * }
 **************************************************************************************************************************/

if (!extension_loaded('sqlite3'))
{
	dl('sqlite3.so');
}

class SQLiteDB
{
	public $pdo_option;
	public $dbh;
	public $db_location;
		
	function __construct($db_location) 
	{
		$this->db_location = $db_location;
		
		try
		{
			$this->dbh = new PDO("sqlite:$db_location");
			$this->pdo_option = 1;
		}
		catch(PDOException $e)
		{
			$this->dbh = sqlite3_open("$db_location");
			$this->pdo_option = 0;
		}
	}

	function importDB($sql_file)
	{
		if($this->pdo_option)
		{
			try
			{
				//OPEN SQL FILE
				$handle = @fopen($sql_file, "r");
				if ($handle)
				{
					while (($buffer = fgets($handle)) !== false)
					{
						$this->dbh->exec($buffer);
					}
			
					if (!feof($handle))
					{
						return 0;
						//echo "Error: unexpected fgets() fail\n";
					}
					fclose($handle);
					return 1;
				}
			}
			catch(PDOException $e)
			{
				return 0;
				//$e->getMessage();
			}
		}
		else
		{
			return 0;
		}
	}
	
	function execDB($query)
	{
		if($this->pdo_option)
		{
			try
			{
				$this->dbh->exec($query);
				return 1;
			}
			catch(PDOException $e)
			{
				return 0;
				//echo $e->getMessage();
			}
		}
		else
		{
			return sqlite3_exec($this->dbh, $query);
		}
	}
	
	function queryDB($query)
	{
		if($this->pdo_option)
		{
			try
			{
				return $this->dbh->query($query);
			}
			catch(PDOException $e)
			{
				return 0;
				//$e->getMessage();
			}
		}
		else
		{
			$query_result = sqlite3_query($this->dbh, $query);
			return $this->convertDataSet($query_result);
		}
	}
	
	/**************************************************************************
	 * CONVERTS AN SQLITE3 DATASET INTO A MULTIDIMENSIONAL ASSOCIATIVE ARRAY
	 **************************************************************************/
	function convertDataSet($row_set)
	{
		$new_row_set = Array();
		while($row = sqlite3_fetch_array($row_set))
		{
			$new_row_set[] = $row;
		}
		return $new_row_set;
	}
}

?>
