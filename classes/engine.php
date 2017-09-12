<?php
namespace cwl;

class engine {

	static $q;
	static $p;
	static $directories = array();
	static $plugins = array();
	static $output = array();
	static $debug = FALSE;
	static $on = FALSE;

	static function init(){
		// Load global app, domain app, global config, app/plugin config, domain config in tHAT order!
		
		// Global app file
		if(file_exists('app.php')){ require_once('app.php'); }
		// Domain specific app file
		if(file_exists(engine::domain() . '/app.config')){ require_once(engine::domain() . '/app.config'); }
		// Global config
		if(file_exists('config.php')){ require_once('config.php'); }
		// App configs
		foreach(self::$plugins as $plugin){
			if(file_exists("$plugin/config.php")) { require_once "$plugin/config.php"; }
		}
		// Site specific config
		if(file_exists(engine::domain() . '/app.config')){ require_once(engine::domain() . '/app.config'); }
		
		// Record that we are switched on
		self::$on = TRUE;
	}
	
	static function go($path = ''){
		if(!self::$on){
			self::init();
		}
		// Scan and find a list of directories to process
		self::$directories = self::findBaseDirectories();
		if(isset($_GET['_debug'])){ print "<pre><code>" . print_r(self::$directories,TRUE) . "</code></pre>";}
		// Process each of these directories
		foreach(self::$directories as $key => $directory){
			self::$q = $path; self::$p = '';
			$processDirectoryReturn = self::processDirectory($directory);
			$key = self::popoff('.',$key);
			if(!isset(self::$output[$key])){
				self::$output[$key] = $processDirectoryReturn;
			} else {
				self::$output[$key] = array_merge(self::$output[$key],$processDirectoryReturn);
			}
		}
	}

	private static function findBaseDirectories(){

		$return = array();
		$scandir = scandir('.');

		// Find the base directories based on our path
		foreach($scandir as $dir){
			if(self::isValidBaseDirectory($dir)){
				$key = self::popoff('/',$dir);
				$return[$key] = $dir;
			}
		}

		// Now check for domain specific directories
		// collect them up, and load any
		$domainReturn = array();
		if(file_exists(self::domain())){
			if(is_dir(self::domain())){
				$scandir = scandir(self::domain());
				foreach($scandir as $dir){
					$dir = self::domain() . "/" . $dir;
					if(self::isValidBaseDirectory($dir)){
						$key = self::popoff('/',$dir);
						// Put the array here in case a plugin gets loaded that we want to override
						$domainReturn[$key] = $dir;
					} else {
						$filetype = pathinfo($dir, PATHINFO_EXTENSION);
						switch($filetype){
							case 'php':
								include($dir);
								break;
							default:
								// Ignore this file.
						}
					}
				}
			}
		}	

		// Now merge in the domain specific overrides
		foreach($domainReturn as $key => $path){
			// $key = self::popoff('/',$key);
			$return[$key] = $path;
		}

		// Get array into order.
		uasort($return,'self::dirSort');
		
		return $return;
	}

	private static function dirSort($a,$b){
		$a = self::popoff("/",$a);
		$b = self::popoff("/",$b);
				
		if ($a == $b) {
      return 0;
		} else {
			return ($a < $b) ? -1 : 1;
		}
	}

	private static function processDirectory($directory){
		// Process this directory, looking for the most specific match
		// Glob the directory directly 
		$pattern = $directory . "/" . self::$q;
		// print "Dir = $directory, Pattern = $pattern, q = " . self::$q . ", p = " . self::$p . "<br>";
		$dirMatches = glob($pattern,GLOB_ONLYDIR);
		switch(sizeof($dirMatches)){
			case 0:
				// No matches. Pop up if possible.
				if(strlen(self::$q) > 0){
					self::popup(self::$q,self::$p);
					return self::processDirectory($directory);	
				}
				break;
			case 1:
				// Match
				return self::openDirectory($pattern);	
				break;
			default:
				// Multiple matches should not be possible here as there is no wildcard.
				// TODO: What do we do about this error - if anything?
		}
	}

	static function openDirectory($path){
		
		$files = scandir($path);
		
		$return = array();
		$return['_path'] = $path;
		$return['_url'] = $path; //TODO: De-globulate path
		
		foreach($files as $file){
			if($file !== '.' && $file !== '..'){
				$file = $path . '/' . $file;
				$file = str_ireplace('//', '/', $file);

				if(is_dir($file)){
					// Ignore subdirectories
				} else {
					// This is a file
					$filetype = pathinfo($file, PATHINFO_EXTENSION);
					switch($filetype){
						case 'php':
							include($file);
							break;
						case 'inc':
							ob_start();
							include($file);
							$return['html'] = @$return['html'] .= ob_get_contents();
							ob_end_clean();
							break;
						case 'htm':
						case 'html':
						case 'txt':
						case 'md':
							$return['html'] = @$return['html'] .= file_get_contents($file);
					}
				}
			}
		}
		
		return $return;
	}

	static function dump($data,$die = FALSE){
		$output = "<hr><pre>" . print_r($data,TRUE) . "</pre><hr>";
		if($die){
			die($output);
		} else {
			print $output;
		}
	} 

	/* HELPER FUNCTIONS */

	static function qp(){
		$return = self::$q . '/' . self::$p;
		if(substr($return,0,1) == '/'){
			$return = substr($return,1);
		}
		$return = strrev($return);
		if(substr($return,0,1) == '/'){
			$return = substr($return,1);	
		}
		$return = strrev($return);
		
		return $return;
	}

	static function parray($i){
		$return = explode("/",self::$p);
		if($i >= 0){
			return @$return[$i];	
		} else {
			$return = array_reverse($return);
			$i++;
			return @$return[$i];
		}
	}

	static function isValidBaseDirectory($directory,$allowProtected = FALSE){
		$return = TRUE;
		$return = is_dir($directory);
		if ($return){
            if ($directory == '.') { $return = FALSE; }
            if ($directory == '..') { $return = FALSE; }
			if(!$allowProtected) {
				if (substr($directory,0,1) == '_') { 
					$return = FALSE; 
				}	
			}
            if (!is_numeric(self::substringIndex(self::popoff('/',$directory),'.',0))) { $return = FALSE; }
        }
        return $return;
	}

	static function substringIndex($string,$substring,$i){
		$return = FALSE;
		$explosion = explode($substring,$string);
		if(isset($explosion[$i])){
			$return = $explosion[$i];
		}
		return $return;
	}

	static private function popup(&$q,&$p,$divider = '/'){
        $q = explode($divider,$q);
        $newp = array_pop($q);
        $q = implode($divider,$q);
        $p = $newp . $divider . $p;
        $p = trim($p,'/');
    }

  static private function popoff($divider,$value){
    $value = explode($divider,$value);
    $value = array_pop($value);
    return $value;
  }

	static function domain(){
		return $_SERVER['SERVER_NAME'];
	}
	
	static function url($withParameters = FALSE){
		return ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];
	}

	static function redirect($url,$status = 303){
		if(stripos($url,'http') === 0){ $url = self::domain() . "/$url"; }
		header("Location: $url",TRUE,$status);
		print "Location $url";
		exit;
	}

	static function basehref($HTML = FALSE){
    $indexphp = $_SERVER['PHP_SELF'];
    $indexphp = explode('/',$indexphp);
    array_pop($indexphp);
    $indexphp = implode('/',$indexphp);
    $indexphp .= '/';

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $http = 'https://';
    } else {
        $http = 'http://';
    }
	
		if($HTML){
			return '<base href="' . $http . self::domain() . $indexphp . '" />' . chr(13);
		} else {
			return $http . self::domain() . $indexphp;	
		}
    
  }

  static function cleanURL($string){
    $allowed = "abcdefghijklmnopqrstuvwxyz0123456789";
    $string = str_split(strtolower($string));
    $return = '';
    foreach($string as $char){
        if(strstr($allowed, $char)){
          $return .= $char;
        } else {
          switch($char){
              case ' ': $return .= '-'; break;
              default: break;
          }
        }
    }
    while(strstr($return, '--')){
        $return = str_ireplace('--', '-', $return);
    }
    return $return;
	}

	
}

?>