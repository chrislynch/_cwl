<?php
/*
 Blank config file. Replace when building an application
*/

/* 
   Using SQLite?
   =============
   
   14.04 = apt-get install php5-sqlite
   16.04 = sudo apt-get install php-sqlite3
   
   http://php.net/manual/en/ref.pdo-sqlite.connection.php
   
   \cwl\db::$connectString = 'sqlite:/opt/databases/mydb.sq3'
   
   Don't forget to protect the directory the file is in.
   
   \cwl\db::$connectString = 'sqlite::memory:'
   
   Creates a temporary database in memory. Will NOT persist in session with special parameters
   
?>