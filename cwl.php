<?php 

namespace cwl;

class engine {

	static $q;
	static $p;
	static $directories = array();
	static $output = array();
	static $plugins = array();
	static $settings = array();
	static $debug = FALSE;

	static function go($path = ''){
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

	static function plugin($pluginName,$path = ''){
		if($path == ''){ $path = "_cwl/plugins/$pluginName"; }
		self::$plugins[$pluginName] = $path;
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

		// Add the plugins
		foreach(self::$plugins as $plugin => $path){
			$scandir = glob("$path/*");
			foreach($scandir as $dir){
				if(is_dir($dir)){
					$key = self::popoff('/',$dir);
					$return[$key] = $dir;
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

	static function openDirectory($path,$allowedDepth = 0){
		$returnArray = array();
		$files = scandir($path);
		$subdirectories = array();
		
		$return = array();
		$return['_path'] = $path;
		$return['_url'] = $path; //TODO: De-globulate path
		
		foreach($files as $file){
			if($file !== '.' && $file !== '..'){
				$file = $path . '/' . $file;
				$file = str_ireplace('//', '/', $file);

				if(is_dir($file)){
					if($allowedDepth > 0){
						$subdirectories[] = $file;	
					}
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
						case 'yaml':
							require_once('_cwl/3rdparty/spyc/spyc.php');
							$yaml = spyc_load_file($file);
							$return = array_merge($return,$yaml);
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

		$returnArray[0] = $return;
		
		// SUBDIRECTORIES NEXT
		foreach($subdirectories as $subdirectory){
			$subdirectoryReturn = self::openDirectory($subdirectory,($allowedDepth - 1));
			$returnArray[] = $subdirectoryReturn;	
		}
		return $returnArray;
	}

	/* CONTENT FUNCTIONS */

	static function globDirectory($contentDirectory,$allowedDepth = 0){
		$contentDirectories = array();
		$contentDirectories[] = $contentDirectory;
		$contentDirectory = explode('/',$contentDirectory);
		$lastDirectory = array_pop($contentDirectory);
		$contentDirectory[] = '*.' . $lastDirectory;
		$contentDirectory = implode('/',$contentDirectory);
		$contentDirectories[] = $contentDirectory;

		$content = array();
		foreach($contentDirectories as $contentDirectory){
			$glob = glob($contentDirectory,GLOB_ONLYDIR);
			if(sizeof($glob) == 1){
				$content = self::openDirectory($glob[0],$allowedDepth);
				break;
			}
		}

		return $content;
	}

	static function theme_include($file){
		$files = array();
		$files [] = self::domain() . "/_theme/" . $file;
		foreach($files as $file){
			if (file_exists($file)){
				include($file);
				return TRUE;
			}
		}
		return FALSE;
	}


	static function error($errorCode){
		die($errorCode);
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
		return @$return[$i];
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

	static function redirect($url,$status = 303){
		header("Location: " . "$url",TRUE,$status);
		die();
	}

	static function basehref(){
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

    return $http . self::domain() . $indexphp;
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

	static function eMail($to,$from,$subject,$content,$attachments = array()){

		//mail($to,$subject,print_r($content,TRUE));

		require_once('_lib/PHPMailer/class.phpmailer.php');
		$mail = new \PHPMailer();

		if(is_array($from)){
			$mail->From	= $from[0];
			$mail->FromName = $from[1];
		} else {
			if(strlen($from) > 0){
				$mail->From = $from;
			} else {
				$mail->From = 'cms@' . self::domain();
			}
		}

		if(is_array($to)){
			$mail->AddAddress($to[0],$to[1]);	
		} else {
			if(strlen($to) > 0){
				$mail->AddAddress($to);
			} else {
				$mail->AddAddress('cms@' . self::domain());
			}
		}
		// $mail->AddReplyTo("info@example.com", "Information");

		// $mail->WordWrap = 50;                                 // set word wrap to 50 characters
		
		$mail->IsHTML(true);                                  // set email format to HTML

		if(strlen($subject) > 0){
			$mail->Subject = $subject;	
		} else {
			$subject = self::qp();
			$mail->Subject = $subject;
		}
		
		if(is_array($content)){
			$body = '';
			$body = "<h1>$subject</h1>";
			$body = '<table><tr><th>Field</th><th>Value</th></tr>';
			foreach($content as $key => $data){
				$body = "<tr><td>$key</td><td>$data</td></tr>";
			}
			$body = '</table>';
			$mail->Body = $body;
		} else {
			$mail->Body = $content;
		}
		// $mail->AltBody = "This is the body in plain text for non-HTML mail clients";

		if(is_array($attachments)){
			foreach($attachments as $key => $attachment){
				if(is_numeric($key)){
					$mail->AddAttachment($attachment);	
				} else {
					// $mail->AddAttachment("/tmp/image.jpg", "new.jpg");    // optional name
					$mail->AddAttachment($attachment,$key);	
				}
			}
		}

		// FINALLY! Send the mail!
		$return = $mail->Send();

		if(!$return)
		{
		   echo "Message could not be sent. <p>";
		   echo "Mailer Error: " . $mail->ErrorInfo;
		   exit;
		}

	}
}

class db {
    
    private static $db;

    static $connectString;
    static $user;
    static $password;
    
    static public function connect(){
        try {
            self::$db = new \PDO(self::$connectString,self::$user,self::$password);
            // PDO::ERRMODE_SILENT
            // PDO::ERRMODE_WARNING
            // PDO::ERRMODE_EXCEPTION
            if(engine::$debug){
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);    
            } else {
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);    
            }

            self::$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);    
            return TRUE;
        } catch (PDOException $e){
            return FALSE;
        }
    }
    
    static public function disconnect(){
        self::$db = NULL;
    }
    
    static private function OK(){
    	// Check that we have a database by connecting and disconnecting
    	$return = FALSE;
        try{
            if(self::connect()){
                self::disconnect();
                $return = TRUE;
            } else {
                $return = FALSE;
            }
        } catch (PDOException $e) {
            $return = FALSE;
        }
    	return $return;
    }
    
    static public function select($SQL,$Values = array()){
        // Run a select statement
        if (self::connect()){
            $select = self::$db->prepare($SQL);
            $select->execute($Values);
            $return = $select;
            self::disconnect();
            /*
            $return = self::$db->query($SQL);
	        self::disconnect();
            */
        } else {
            die("Could not connect to database");
            $return = FALSE;
        }
        return $return;
    }
    
    static public function query($SQL,$Values = array()){
        return self::select($SQL,$Values);
    }
    
    static public function pageselect($SQL,$Values = array(),&$pagecount = FALSE,$pagelength = 10,$pagenumber = -1){
        // Safety checks
        if(!(is_numeric($pagenumber))) { $pagenumber = 1;}
        if(!is_numeric($pagelength)) { $pagelength = 10;}

        // Cache original SQL
        $oSQL = $SQL;
        // Work out page we are on, if we have not been told

        if($pagenumber == -1){
            if(isset($_GET['page']) && is_numeric(@$_GET['page'])){
                $pagenumber = strval($_GET['page']);
            }    
            if($pagenumber <= 0){ $pagenumber = 1;}
        }

        // Work out the SQL for a paged version of this query
        $startat = ($pagenumber -1) * $pagelength;
        $SQL .= " LIMIT $startat,$pagelength";

        // Prepare the return
        $return = self::select($SQL,$Values);

        // Also calculate the number of available pages
        $SQL = $oSQL;
        $SQL = "SELECT COUNT(0)/$pagelength FROM ($SQL) r";
        $pagecount = ceil(self::result($SQL,$Values));

        // And return the result
        return $return;
    }

    static public function pagequery($SQL,$Values = array(), &$pagecount = FALSE,$pagelength = 10,$pagenumber = -1){
        return self::pageselect($SQL,$Values,$pagecount,$pagelength,$pagenumber);
    }

    static public function insert($SQL,$Values = array()){
        if (self::connect()){
           $cmd = self::$db->prepare($SQL);
           $r = $cmd->execute($Values);
           $return = self::$db->lastInsertId();
           self::disconnect();
        } else {
            $return = FALSE;
        }
        
        return $return;
    }
    
    static public function update($SQL,$Values = array()){
        // Run the update (or delete) command and return how many rows were affected
        if (self::connect()){
            $update = self::$db->prepare($SQL);
            $update->execute($Values);
            $return = $update->rowCount();
            self::disconnect();
        } else {
            $return = FALSE;
        }
        return $return;
    }
    
    static public function delete($SQL,$Values = array()){
        // Run the delete command and return how many rows were affected
        return self::update($SQL,$Values);
    }
    
    static public function result($SQL,$Values = array()){
       $data = self::select($SQL,$Values);
       if(!($data === FALSE)){
            return $data->fetchColumn();         
       } else {
            return FALSE;
       }
	   
    }

    static public function escape($string){
	   self::connect();
	   $return = self::$db->quote($string);
       self::disconnect();
       return $return;
    }
    
    static public function table_exists($tablename){
        $table_exists = self::select("SHOW TABLES LIKE :tablename",array(':tablename' => $tablename));
        if($table_exists->rowCount() > 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }        
    
}

class noSQL {

	static function save($obj,$guid = '',$parent = ''){
		if((!is_object($obj)) && (!is_array($obj))) { throw new \Exception('Cannot save a scalar value in noSQL'); }
		// Ensure that the item or object has a guid
		$guid = self::setGUID($obj,$guid);
		// Purge this guid from the system to destroy old data
		self::delete($guid);
		// Save this item to the database
		foreach($obj as $key => $value){
			if(is_object($value)){
				// Create another object and link to it
				$returnedGUID = self::save($value);
				self::saveField($key,$guid,$returnedGUID,0,1);
			} elseif(is_array($value)){
				// Iterate through the values, saving accordingly.
				foreach($value as $arraykey => $arrayvalue){
					if(is_object($arrayvalue)){
						$returnedGUID = self::save($arrayvalue);
						self::saveField($key,$guid,$returnedGUID,$arraykey,1);
					} elseif(is_array($arrayvalue)) {
						$returnedGUID = self::save($arrayvalue);
						self::saveField($key,$guid,$returnedGUID,$arraykey,2);
					} else {
						self::saveField($key,$guid,$arrayvalue,$arraykey,0);
					}
				}
			} else {
				// Normal, scalar value. Save.
				self::saveField($key,$guid,$value);
			}
		}
		return $guid;
	}

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

	static function load($guid){
		// Load up the guid that we have supplied.
		$return = new \stdClass();
		$tables = self::tables();

		foreach($tables as $table){
			$query = db::query("SELECT * FROM '$table' WHERE guid = :guid", array(':guid' => $guid));
			
			$rows = array();
			while($row = $query->fetch()){ $rows[] = $row; }

			switch(sizeof($rows)){
				case 0:
					// Do nothing, there is no data in this table for us.
					break;
				case 1:
					// There is either a value or a pointer to a value
					foreach($rows as $row){
						$return->$table = self::rowToValue($row);
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
					$return->$table = $array;
					break;
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
	}

	static function purge(){
		$tables = self::tables();
		foreach($tables as $table){
			db::delete("DROP TABLE '$table'");
		}	
	}

	private static function saveField($table,$guid,$value,$key = '',$type = 0){
		if(!(self::table_exists($table))){ 
			/*
			$SQL = "
				CREATE TABLE '$table' (
				    'guid' TEXT NOT NULL,
				    'key' TEXT,
				    'value' TEXT,
				    'type' INTEGER DEFAULT (0),
				    'timestamp' DATETIME DEFAULT CURRENT_TIMESTAMP
				);
				CREATE UNIQUE INDEX '{$table}_PK' on $table (guid ASC, key ASC);";
			*/
			$SQL = "
				CREATE TABLE '$table' (
				    'guid' TEXT NOT NULL,
				    'key' TEXT,
				    'value' TEXT,
				    'type' INTEGER DEFAULT (0)
				);
				CREATE UNIQUE INDEX '{$table}_PK' on $table (guid ASC, key ASC);";
			db::query($SQL);
		}
		$SQL = "
			REPLACE INTO '$table'(guid,value,key,type)
			VALUES(:guid,:value,:key,:type);";
		db::insert($SQL, array(':guid' => $guid, ':value' => $value, ':key' => $key, ':type' => $type));
	}

	static function tables($pattern = ''){
		$tables = db::query("SELECT name FROM sqlite_master WHERE type='table';");
		$return = array();
		while($table = $tables->fetch()){
			$return[$table['name']] = $table['name'];
		}
		return $return;
	}

	static function table_exists($table){
		$tables = self::tables();
		return (array_key_exists($table, $tables));
	}

}

?>