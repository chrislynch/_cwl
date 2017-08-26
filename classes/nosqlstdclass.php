<?php
namespace cwl;

class noSQLstdClass implements noSQLInterface, \IteratorAggregate {
	
	// protected $_guid = 0;
	protected $_data = array();
	protected $_dataLock = TRUE;
	
	function __construct($template = array()){
		// Set defaults
		$this->guid = '';
		$this->type = '';
		$this->class = get_class($this);
		$this->name = '';
		$this->uri = '';
		$this->status = 0;
		$this->flag = 0;
		$this->rank = 0;
		$this->system = 0;
		// Absorb any template items
		if(sizeof($template) > 0){
			$this->absorb($template);
		}
	}
	
	public function &__get($name) {
		switch(strtolower($name)){
			/*
			case 'class':
				return get_class($this);
				break;
			case 'type':
				if(strlen(trim($this->_data['type'])) == 0){
					return 'unknown';
				}
				break;
			*/
			default:
				$return =& $this->_data[$name];
		}
		return $return;
	}

	public function __set($name, $value) {
		$name = strtolower($name);
		switch($name){
			/*
			case 'guid':
				if(@$this->_data['guid'] == 0){
					$this->_data['guid'] = $value;
				}	else {
					$this->_data['guid'] = $value;
					// throw new Exception("You cannot overwrite the guid of an item");
				}
				break;
			case 'class':
				throw new Exception("You cannot overwrite the class of an item");
				break;
			*/
			default:
				$this->_data[$name] = $value;		
		}
	}
	
	public function __isset( $name ) {
		return isset( $this->_data[$name] );
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->_data);
	}
	
	public function noSQLbeforeSave(){
		$return = TRUE;
		if($this->guid == ''){
			$this->guid = uniqid();
		}
		/*
		if($this->name == ''){
			$this->name = $this->guid;
		}
		
		if($this->system == 0){
			if($this->uri == )
		}
		*/
		return TRUE;
	}
	
	public function noSQLafterSave(){
		return TRUE;
	}
	
	public function noSQLafterLoad(){
		return TRUE;
	}
	
	public function absorb($obj,$overwrite = FALSE){
		$this->_dataLock = FALSE;
		foreach($obj as $property => $value){
			$property = str_ireplace(' ','',$property);
			$this->_data[$property] = $value;
		}
		$this->_dataLock = TRUE;
	}
	
	public function asArray($writeArray = array(),$overwrite = FALSE){
		return $this->_data;
	}
	
	public function asObject($writeObject = FALSE,$overwrite = FALSE){
		$return = new \stdClass();
		foreach($this->_data as $key => $value){
			$return->$key = $value;
		}
		return $return;
	}
	
	public function xml($writeXML = FALSE){
		
	}
	
	public function json($writeJSON = FALSE){
		
	}
	
	public function serial($writeSerial = FALSE){
		
	}
}

?>
