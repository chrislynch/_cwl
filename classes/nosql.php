<?php
namespace cwl;

class noSQL {
	
	static function blank($template = array()){
		return new noSQLstdClass($template);
	}
	
	static function save(&$obj,$childObject = FALSE){
		if((!is_object($obj)) && (!is_array($obj))) { throw new \Exception('Cannot save a scalar value in noSQL'); }
		// Ensure that the item or object has a guid
		// $guid = self::setGUID($obj,$guid,$parent);
		
		// Set and save the type
		$beforeSaveOK = TRUE;
		if(is_object($obj)){ 
			self::setClass($obj); 
			if(method_exists($obj,'noSQLbeforeSave')){
				$beforeSaveOK = $obj->noSQLbeforeSave();
			} else {
				$beforeSaveOK = FALSE;
			}
		}
		$guid = $obj->guid;
		
		if(!$childObject && !$beforeSaveOK) { return FALSE; } // Bomb out if we can't validate the object
		
		// Purge this guid from the system to destroy old data
		self::delete($guid);
		
		// Save this item to the database
		foreach($obj as $key => $value){
			if(is_object($value)){
				// Create another object and link to it, if it contains elements/items
				$objectTest = get_object_vars($value);
				if(sizeof($objectTest) > 0){
					$returnedGUID = self::save($value,TRUE);
					self::saveField($key,$guid,$returnedGUID,'',1,$obj);	
				} else {
					self::saveField($key,$guid,$value,'',0,$obj->type);	
				}
			} elseif(is_array($value)){
				// Iterate through the values, saving accordingly.
				foreach($value as $arraykey => $arrayvalue){
					if(is_object($arrayvalue)){
						$returnedGUID = self::save($arrayvalue,TRUE);
						self::saveField($key,$guid,$returnedGUID,$arraykey,1,$obj);
					} elseif(is_array($arrayvalue)) {
						$returnedGUID = self::save($arrayvalue);
						self::saveField($key,$guid,$returnedGUID,$arraykey,2,$obj);
					} else {
						self::saveField($key,$guid,$arrayvalue,$arraykey,0,$obj);
					}
				}
			} else {
				// Normal, scalar value. Save.
				self::saveField($key,$guid,$value,'',0,$obj);
			}
		}
		
		// Populate the index
		if(!$childObject){
			if(!(self::table_exists('_index'))){
				db::query("
					CREATE TABLE '_index' ('guid' TEXT PRIMARY KEY NOT NULL, 'type' TEXT, 'class' TEXT, 
					'name' TEXT, 'uri' TEXT, 
					'status' INTEGER NOT NULL DEFAULT 0, 'flag' INTEGER NOT NULL DEFAULT 0, 'rank' INTEGER NOT NULL DEFAULT 0, 'system' INTEGER NOT NULL DEFAULT 0, 
					'timestamp' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
			}
			db::insert("REPLACE INTO _index(guid,type,class,name,uri,status,flag,rank,system)
								 VALUES(:guid,:type,:class,:name,:uri,:status,:flag,:rank,:system)", 
								 array($obj->guid,$obj->type,$obj->class,$obj->name,$obj->uri,$obj->status,$obj->flag,$obj->rank,$obj->system));
		}
		
		if(method_exists($obj,'noSQLafterSave')){
			$obj->noSQLafterSave();
		}
		return $guid;
	}

	/*
	private static function setGUID(&$obj,$guid,$parent){
		// Read the guid from the item if we have not been told what it is (or should be)
		if(strlen(trim($guid)) == 0){
			if(is_object($obj)){
				if(strlen(trim(@$obj->guid)) > 0){
					$guid = $obj->guid;
				}
			} elseif(is_array($obj)){
				if(strlen(trim(@$obj['guid'])) > 0){
					$guid = $obj['guid'];
				}
			}	
		}
		// Create a guid if we need one		
		if(strlen(trim($guid)) == 0){
			$guid = uniqid();
		}
		// Ensure the guid is written to the object or array
		if(is_object($obj)){
			$obj->guid = $guid;
		} elseif(is_array($obj)){
			$obj['guid'] = $guid;
		}
		// Return the guid
		return $guid;
	}
	*/
	
	private static function setClass(&$obj){
		if(!$obj instanceof noSQLInterface){
			$newobj = new noSQLstdClass();
			$newobj->absorb($obj);
			$obj = $newobj;
			unset($newobj);
		}
		
		if(!isset($obj->class)){
			$obj->class = get_class($obj);
		}
		if(!isset($obj->type)){
			$obj->type = $obj->class;
		}
		
		return $obj->class;
	}
	
	static function load($guid){
		// Load up the guid that we have supplied.
		
		$indexData = db::row("SELECT type,class FROM _index WHERE guid = :guid",array(':guid' => $guid));
		
		$return = new $indexData['class'];
		$tables = self::tables();

		$tableprefix = $indexData['type'] . '_';
		
		foreach($tables as $table){
			// Decide if we are loading this table
			if(stripos($table,'_') === 0) {
				$loadTable = FALSE;
			} elseif (stripos($table,$tableprefix) === 0) {
				$loadTable = TRUE;
			} else {
				$loadTable = FALSE;
			}
			
			if($loadTable){
				$query = db::query("SELECT * FROM '$table' WHERE guid = :guid", array(':guid' => $guid));
				$property = substr($table,strlen($tableprefix));
				$rows = array();
				while($row = $query->fetch()){ $rows[] = $row; }
					switch(sizeof($rows)){
						case 0:
							// Do nothing, there is no data in this table for us.
							break;
						case 1:
							foreach($rows as $row){
								switch($row['type']){
									case 0:
									case 1:
										$return->$property = self::rowToValue($row,FALSE);		
										break;
									case 2:
										$array = array();
										$array[$row['key']] = self::rowToValue($row,FALSE);
										$return->$property = $array;	
								}
							}
							break;
						default:
							// There are multiple values. Therefore, we need an array
							$array = array();
							foreach($rows as $row){
								$key = $row['key'];
								$value = self::rowToValue($row,TRUE);
								$array[$key] = $value;
							}
							$return->$property = $array;
							break;
					}	
				}
		}

		return $return;
	}

	private static function rowToValue($row,$forceScalar = FALSE){
		switch($row['type']){
			case 0:
				// Scalar value
				if(strlen(trim($row['key'])) == 0 || $forceScalar){
					return $row['value']; 
				} else {
					return array($row['key'] => $row['value']);
				}
				break;
			case 1:
				// Object
				return self::load($row['value']);	
			case 2:
				// Array
				$object = self::load($row['value']);
				$return = array();
				foreach($object as $key => $value){
					$return[$key] = $value;
				}
				return $return;
				break;
		}
	}

	static function delete($guid){
		$tables = self::tables();
		foreach($tables as $table){
			db::delete("DELETE FROM $table WHERE guid = :guid",array(':guid' => $guid));
		}
		db::delete("DELETE FROM _index WHERE guid = :guid",array(':guid' => $guid));
	}

	static function purge(){
		$tables = self::tables();
		foreach($tables as $table){
			db::delete("DROP TABLE '$table'");
		}	
	}

	private static function fixTableName($table){
		/*
		$prefix = config::$nosql_tablePrefix;
		$prefixLength = strlen($prefix);
		if($prefixLength > 0){
			if(substr($table,0,$prefixLength) == $prefix){
				// The prefix is there. Remove the prefix
				$table = substr($table,$prefixLength);
			} else {
				// The prefix is not there. Add the prefix
				$table = $prefix . $table;
			}
		} else {
			// Do nothing with the table name, it is fine as it is
		}
		*/
		// Ensure table names are lower case to avoid problems with case sensitive variations
		$table = strtolower($table);
		
		return $table;
	}
	
	private static function saveField($table,$guid,$value,$key,$type,$obj){
		// Adjust for table prefixing
		$table = self::fixTableName($table);
		$table = $obj->type . '_' . $table;
		if(!(self::table_exists($table))){ 
			$SQL = "
				CREATE TABLE '$table' (
				    'guid' TEXT NOT NULL,
						'objecttype' TEXT NOT NULL,
				    'key' TEXT,
				    'value' TEXT,
				    'type' INTEGER DEFAULT (0),
						'timestamp' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
				);
				CREATE UNIQUE INDEX '{$table}_PK' on $table (guid ASC, key ASC);";
			db::query($SQL);
		}
		$SQL = "
			REPLACE INTO '$table'(guid,objecttype,value,key,type)
			VALUES(:guid,:objecttype,:value,:key,:type);";
		
		db::insert($SQL, array(':guid' => $guid, ':objecttype' => $obj->type, ':value' => $value, ':key' => $key, ':type' => $type));
	}

	static function tables($filterOff = FALSE){
		$tables = db::query("SELECT name FROM sqlite_master WHERE type='table';");
		$return = array();
		while($table = $tables->fetch()){
			if(stripos($table['name'],'_') === 0 && !$filterOff) {
				// Ignore tables prefixed with _	
			} else {
				$return[strtoupper($table['name'])] = $table['name'];	
			}
		}
		return $return;
	}

	static function table_exists($table){
		$tables = self::tables(TRUE);
		return (array_key_exists(strtoupper($table), $tables));
	}
	
	static function search($params, $pager = FALSE){
		// Test pager
		if(!$pager === FALSE){ if(class_name($pager) !== 'noSQLpager'){	throw new \Exception("Invalid pager object"); } }
		
		$indexFields = array('guid','type','class','name','uri','status','flag','rank','system');
		
		$sqlParams = array();
		$JOIN = '';
		$WHERE = '';
		$j = 1;
		
		foreach($params as $param => $value){
			if(stripos($param,'_') === 0){
				// Param starts with _ - this is an escape parameter and does a special job.
				// We need to pick these up before we build our main query
				switch(strtolower($param)){
					case '_type': $sqlParams[':type'] = $value; break;
				}
			}
		}
		
		foreach($params as $param => $value) {
			// Type is a special parameter that unlocks deeper joining, so go look for it first.
			if($param == 'type'){
				$WHERE .= " AND i.type = :type";
				$sqlParams[':type'] = $value;
				unset($params['type']);
			}
		}
		
		foreach($params as $param => $value) {
			if(!(stripos($param,'_') === 0)){
				if(in_array($param,$indexFields)){
					$WHERE .= " AND $param = :{$param} ";
					$sqlParams[$param] = $value;
				} else {
					if(isset($sqlParams[':type'])){
						$JOIN .= " JOIN {$sqlParams[':type']}_{$param} j{$j} ON j{$j}.guid = i.guid ";
						if(is_array($value)){
							// Should be array of op and value
						} else {
							// Default operator is = 
							$WHERE .= " AND j{$j}.value = :j{$j} ";
							$sqlParams["j{$j}"] = $value;
						}
						$j++;		
					}
				}	
			}
		}
		
		if(sizeof($sqlParams) == 0){
			throw new \Exception("No search criteria supplied");
		}
		
		$SELECT = "SELECT i.* FROM _index i \n";
		$SELECT .= $JOIN;
		$SELECT .= "\n WHERE 1 = 1 \n";
		$SELECT .= $WHERE;
		
		return \cwl\db::query($SELECT,$sqlParams);
	}

}

?>