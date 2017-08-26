<?php
namespace cwl;

class db {
    
    private static $db;
 
    static public function connect(){
        try {
            self::$db = new \PDO(config::$db_connectString,config::$db_user,config::$db_password);
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
	
		static public function row($SQL,$Values = array()){
			$rows = self::query($SQL,$Values);
			if($row = $rows->fetch()){
				return $row;
			} else {
				return array();
			}
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
    
    static public function result($SQL,$Values = array(),$default){
       $data = self::select($SQL,$Values);
       if(!($data === FALSE)){
            return $data->fetchColumn();         
       } else {
            return $default;
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


?>