<?php
$_GET['_debug'] = TRUE;
include('cwl.php');

class toolbox {
  
  static $config;
   
  static function purge(){
    cwl\nosql::purge();
  }
	
	static function unlock($username,$password){
		// Unlock the toolbox for this user
		if(md5($password) == @self::$config->users[$username]['password']){
			setcookie('cwlToolboxID',$username,0,'/');
			$_SESSION['cwlToolboxID'] = $username;		
			return TRUE;
		} else {
			self::lock();
			return FALSE;
		}
	}
	
	static function lock(){
		// Lock the toolbox
		setcookie('cwlToolboxID','',(time() - 1),'/');
		unset($_SESSION['cwlToolboxID']);
	}
	
	static function isOpen(){
		// Check if the toolbox is open or not
		return(strlen(@$_COOKIE['cwlToolboxID']) > 0 || strlen(@$_SESSION['cwlToolboxID']) > 0 );
	}
  
  static function home(){ 
		if(cwl\nosql::table_exists('_index')){
			$matches = cwl\db::query("SELECT * FROM _index ORDER BY timestamp DESC");
			self::table($matches);	
		} else {
			print "<h1>Welcome</h1>";
			print "<p>Database is currently empty. Please add or import data.</p>";
		}
  }
  
  static function search(){
    /* Search for the item we looked for */
    $matches = cwl\db::query("
			SELECT * 
			FROM _index i
			WHERE i.name LIKE :search OR i.guid = :guid",
			array(':search' => "%{$_GET['search']}%",
						':guid' => $_GET['search'])
			);
    
		self::table($matches);
  }
  
  static function table($matches){
		/*
		print "<form action='?do=table'>
			<label>SELECT:</label><input name='select' type='text'><br>
			<label>WHERE:</label><input name='where' type='text'><br>
			<button type='submit'>Search</button>
		";
		*/
    print '<table class="table table-striped table-hover ">';
		$firstRow = TRUE;
    while($match = $matches->fetch()){
			if($firstRow){
				print '<thead><tr>';
				foreach($match as $key => $value){
					print "<th>$key</th>";
				}
				print '</tr></thead><tbody>';
				$firstRow = FALSE;
			}
			print '<tr>';
			foreach($match as $key => $value){
				print "<td>";
				switch($key){
					case 'guid': 
						print "<a href='?do=edit&guid=" . $value . "'>" . $value . "</a>";
						break;
					case 'uri':
						print "<a href='" . $value . "'>" . $value . "</a>";
						break;
					default:
						print $value;
				}
				print "</td>";
			}
			print '</tr>';
    }
    print '</tbody></table>';
  }
  
  static function create($type = ''){
    if($type == '') { $type = @$_GET['type']; }
    if(strlen(trim($type)) == 0){
      // Type has not been set.
      print "<h1>Select new object type</h1><ul>";
      foreach(self::$config->types as $typeKey => $type){
        //print_r($type);
        print "<li><a href='?do=create&type={$typeKey}'>{$type['name']}</a></li>";
      }
      print "</ul>";
    } else {
      $obj = cwl\nosql::blank(array('type' => $type));
      self::editor($obj);
    }
  }
  
  static function edit($guid){
    if(strlen(trim($guid)) == 0){
      toolbox::error('No item GUID specified');
    } else {
      $obj = cwl\nosql::load($guid);
      if($obj->guid = $guid){
        self::editor($obj);
      } else {
        toolbox::error('Item not found');
      }  
    }
  }
  
  static private function editor($obj){
      		
    // Load up fields for this type
    if(isset(self::$config->types[$obj->type])){
			print '<form action="toolbox.php?do=save&_debug" method="POST" enctype="multipart/form-data" class="form-horizontal"><fieldset>';
			if(strlen(trim(@$obj->guid)) == 0){
				print "<h1>New {$obj->type}</h1>";
			} else {
				print "<h1>Edit Object</h1>";
				print '<fieldset><legend>'  . trim($obj->guid . " " . @$obj->Name) . '</legend>';
				print '<input type="hidden" name="guid" value="' . $obj->guid . '">';
			}
			// Hidden, mandatory, fields
			print '<input type="hidden" name="type" value="' . $obj->type . '">';
			print '<input type="hidden" name="class" value="' . $obj->class . '">';
      // Configured fields
			foreach(self::$config->types[$obj->type]['fields'] as $property => $field){
        print '<label>' . $field['title'] . '</label><br>' ;
        switch($field['type']){
          case 'select':
            if(isset($field['options'])){
              print '<select name="' . $property . '">';
							if(is_array($field['options'])){
								foreach($field['options'] as $key => $value){
									if(is_array(@$obj->$property)){
										if(in_array($key,@$obj->$property)){ $selected = 'selected="SELECTED"'; } else { $selected = ''; }
									} else {
										if($key == @$obj->$property){ $selected = 'selected="SELECTED"'; } else { $selected = ''; }	
									}
									print '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
								}	
							} else {
								$options = cwl\db::query($field['options']);
								while($option = $options->fetch()){
									if(is_array(@$obj->$property)){
										if(in_array($option['key'],@$obj->$property)){ $selected = 'selected="SELECTED"'; } else { $selected = ''; }
									} else {
										if($option['key'] == @$obj->$property){ $selected = 'selected="SELECTED"'; } else { $selected = ''; }	
									}
									print '<option value="' . $option['key'] . '" ' . $selected . '>' . $option['value'] . '</option>';
								}
							}
              print '</select><br>';
            } else {
              print '<input name="' . $property . '" type="text" value="' . @$obj->$property . '"><br><br>';  
            }
            break;
					case 'file':
          case 'upload':
						if(is_array(@$obj->$property)){
							foreach($obj->$property as $value){
								print '<input type="hidden" name="' . $property . '[]" value="' . $value . '">';
								print "<em>{$value}</em><br>";
							}
						} else {
							if(strlen(trim(@$obj->$property)) > 0){
								print '<input type="hidden" name="' . $property . '[]" value="' . $obj->$property . '">';
								print "<em>{$obj->$property}</em><br>";
							}	
						}
            
            print '<input type="file" name="' . $property . '[]">';
						
            break;
          case 'textarea':
            $rows = count(explode("\n",@$obj->$property)) + 2;
            print '<textarea name="' . $property . '" class="form-control" rows="' . $rows . '">';
            print @$obj->$property;
            print "</textarea>";
            break;
          case 'text':
					case 'uri':
					case 'csv':
          default:
						if($field['type'] == 'csv'){
							if(is_array(@$obj->$property)){
								$value = implode(', ',@$obj->$property);	
							} else {
								$value = '';	
							}
						} else {
							$value = @$obj->$property;
						}
            print '<input name="' . $property . '" type="text" value="' . $value . '"><br>';
						if($field['type'] == 'uri'){
							if(strlen(@$obj->$property) > 0){
								print "<small><a href='{$obj->$property}'>Click here to visit this URI</a></small><br>";
							} 
						}
						print '<br>';
            break;
        }
      }
			print '<button name="do" value="save" type="submit" class="btn btn-primary" style="margin-left:auto; margin-top: 8px">Save</button><hr>';  
    	print "</fieldset></form>";  
    }
    
		/*
		$printr = print_r($obj,TRUE);
		$rows = substr_count( $printr, "\n" ) + 2;
    print "<textarea rows='$rows'>" . print_r($obj,TRUE) . "</textarea>";
		*/
		print "<pre>" . print_r($obj,TRUE) . "</pre>";
  }
  
  static function validate(&$obj){
		foreach(self::$config->types[$obj->type]['fields'] as $property => $field){
			if($field['type'] == 'csv'){
				$obj->$property = explode(',',@$obj->$property);
				foreach($obj->$property as $key => $value){
					$obj->$property[$key] = trim($value);
				}
			}	
		}
    return TRUE;
  }
  
  static function save(){
		// Load the affected object
		if(isset($_POST['guid'])){
			$obj = cwl\nosql::load($_POST['guid']);	
		} else {
			$obj = cwl\nosql::blank();
		}
		// Save any posted files
		cwl\files::saveFiles('_uploads',$_POST);
		
		// Absorb the complete posted data
    $obj->absorb($_POST);
				
    if(self::validate($obj)){
			cwl\nosql::save($obj);  
    }
		
		self::editor($obj);
  }
  
  static function error($errorTitle){
    print '<div class="alert alert-dismissible alert-danger">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Error:</strong> <a href="#" class="alert-link">' . $errorTitle . '</a></div>';
  }
}

?>

<html>
  <head>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g=" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <!-- https://bootswatch.com/paper/ -->
    <link href="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/paper/bootstrap.min.css" rel="stylesheet" integrity="sha384-awusxf8AUojygHf2+joICySzB780jVvQaVCAt1clU3QsyAitLGul28Qxb2r1e5g+" crossorigin="anonymous">
    <style>
      input,textarea { width:100%; max-height: 90% }
      textarea { font-family: monospace; }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-inverse">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="toolbox.php">toolbox</a>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-2">
          <ul class="nav navbar-nav">
            <li><a href="?do=create">New</a></li>
            <!-- 
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Dropdown <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#">Action</a></li>
                <li><a href="#">Another action</a></li>
                <li><a href="#">Something else here</a></li>
                <li class="divider"></li>
                <li><a href="#">Separated link</a></li>
                <li class="divider"></li>
                <li><a href="#">One more separated link</a></li>
              </ul>
            </li>
            -->
          </ul>
          <form method="GET" action="toolbox.php" class="navbar-form navbar-right" role="search">
            <div class="form-group">
              <input name="search" type="text" class="form-control" placeholder="Search">
            </div>
            <button name="do" value="search" type="submit" class="btn btn-default">Search</button>
          </form>
          
          <ul class="nav navbar-nav navbar-right">
            <li><a href="?do=logout">Log Out</a></li>
          </ul>
          
        </div>
      </div>
    </nav>
    <div class="container">
      <div class="row">
        <div class="col-xs-12">
          <?php
            if(toolbox::isOpen()){
              switch(@$_GET['do']){
								case '': 
								case 'home': 
								case 'table': 
									toolbox::home(); break;
                case 'install': toolbox::install(); break;
                case 'search': toolbox::search(); break;
                case 'create': toolbox::create(); break;
                case 'edit': toolbox::edit($_GET['guid']); break;
                case 'save': toolbox::save(); break;
								case 'purge': toolbox::purge(); break;
								case 'logout': toolbox::lock(); cwl\engine::redirect('toolbox.php'); break;
                default:
                  print "<h1>Error</h1>";
                  print "<p>Unknown DO operator</p>";
              }  
            } else {
							switch(@$_GET['do']){
								case 'login':
									toolbox::unlock($_POST['username'],$_POST['password']);
									cwl\engine::redirect('toolbox.php'); 
									break;
								default:
									print "<h1>Log In</h1>";
									print "<form action='?do=login' method='POST'>";
									print "<label>Username</label><br><input type='text' name='username'><br><br>";
									print "<label>Password</label><br><input type='password' name='password'><br><br>";
									print "<button type='submit'>Log In</button>";
									print "</form>";
							}
            }
          ?>
        </div>
        <!--
        <div class="col-xs-12">
          <pre><?php print_r(toolbox::$config) ?></pre>
        </div>
        -->
      </div>
    </div>
  </body>
</html>