<?php
namespace cwl;
  
class config {
	// Configuration object to hold configuration information
	static $db_connectString;
	static $db_user;
	static $db_password;
	
	static private $parameters = array();
	
	static function get($parameterName,$defaultValue = ''){
		if(isset(self::$parameters[$parameterName])){
			return self::$parameters[$parameterName];
		} else {
			return $defaultValue;
		}
	}
	
	static function set($parameterName,$newValue) {
		self::$parameters[$parameterName] = $newValue;
	}
	
}



?>