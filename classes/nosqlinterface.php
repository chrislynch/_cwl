<?php
namespace cwl;

interface noSQLInterface{
	
	public function noSQLbeforeSave();
	public function noSQLafterSave();
	public function noSQLafterLoad();
	
	public function absorb($obj);
	public function asArray($writeArray = array(),$overwrite = FALSE);
}

?>