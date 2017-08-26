<?php 

namespace cwl;

/***********************************/

function cwl_autoloader($class) {
	$class = explode('\\',$class);
	if($class[0] == 'cwl'){
		$class[1] = strtolower($class[1]);
		require_once('_cwl/classes/' . $class[1] . '.php');	
	}
}

spl_autoload_register('cwl\cwl_autoloader');

/***********************************/

function cwl_shutdown(){
	try {
		$error = error_get_last(); 
		if($error['type'] == E_ERROR){
			session_destroy();
			session_start();
			$_SESSION['error'] = $error;
			header('HTTP/1.1 501 Internal Server Error');
    	header('Status: 501 Internal Server Error');
    	header('Location: _cwl/error.php',501);
    	die();
		}
	} catch (Exception $e) {
		// Do nothing if shutdown fails
	}
}

register_shutdown_function("cwl\cwl_shutdown");

/***********************************/

if(isset($_GET['_debug'])){
	error_reporting(-1);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors',1);
	\cwl\engine::$debug = TRUE;
} else {
	error_reporting(0);
	ini_set('display_errors', 0);	
	ini_set('display_startup_errors',0);
	\cwl\engine::$debug = FALSE;
}

if(file_exists('config.php')){
	include('config.php');
}





?>