<?php

include('cwl.php');

if(isset($_GET['_debug'])){
	error_reporting(-1);
	ini_set('display_errors', 1);	
	cwl\engine::$debug = TRUE;
} else {
	error_reporting(0);
	ini_set('display_errors', 0);	
	cwl\engine::$debug = FALSE;
}

cwl\engine::go(@$_GET['q']);

?>