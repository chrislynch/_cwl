<?php
$_GET['_debug'] = TRUE;
include('cwl.php');
$toolboxLoggedIn = TRUE;

class toolbox {
  
  static function home(){
    $matches = cwl\db::query("SELECT guid,value FROM Name ORDER BY guid DESC LIMIT 30");
    self::table($matches);
  }
  
  static function search(){
    /* Search for the item we looked for */
    $matches = cwl\db::query("SELECT guid,value FROM Name WHERE value LIKE :search",array(':search' => "%{$_GET['search']}%"));
    self::table($matches);
  }
  
  static function table($matches){
    print '<table class="table table-striped table-hover ">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
              </tr>
            </thead><tbody>';
    while($match = $matches->fetch()){
      print "<tr><td><a href='?do=edit&guid=" . $match['guid'] . "'>" . $match['guid'] . "</a></td>
                <td>{$match['value']}</td>";
    }
    print '</tbody></table>';
  }
  
  static function create(){
    $obj = cwl\nosql::blank();
    self::editor($obj);
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
    if(is_object($obj) || is_array($obj)){ $json = json_encode($obj,JSON_PRETTY_PRINT); }
    $rows = count(explode("\n",$json)) + 2;

    print '<form action="toolbox.php?do=save" method="POST" class="form-horizontal"><fieldset>';
    if(strlen(trim($obj->_guid)) == 0){
      print "<h1>New Object</h1>";
    } else {
      print "<h1>Edit Object</h1>";
      print '<fieldset><legend>'  . trim($obj->_guid . " " . $obj->Name) . '</legend>';  
    }
    
    print '<textarea name="json" class="form-control" rows="' . $rows . '">';
    print $json;
    print "</textarea>";
    print '<button name="do" value="save" type="submit" class="btn btn-primary" style="float:right; margin-top: 8px">Save</button>';
    print "</fieldset></form>";  
  }
  
  static function save(){
    print_r($_POST['json']);
    $obj = json_decode($_POST['json'],TRUE);
    if(!is_array($obj)){
      self::error('Input data was not valid JSON');
      self::editor($_POST['json']);
    } else {
      $nosqlobj = cwl\nosql::blank($obj);
      $guid = cwl\nosql::save($nosqlobj);
      self::edit($guid);
    }
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
            <li><a href="?do=new">New</a></li>
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
          <!--
          <ul class="nav navbar-nav navbar-right">
            <li><a href="#">Link</a></li>
          </ul>
          -->
        </div>
      </div>
    </nav>
    <div class="container">
      
      <div class="row">
        <div class="col-xs-12">
          <?php
            if($toolboxLoggedIn){
              switch(@$_GET['do']){
                case '': toolbox::home(); break;
                case 'search': toolbox::search(); break;
                case 'new': toolbox::create(); break;
                case 'edit': toolbox::edit($_GET['guid']); break;
                case 'save': toolbox::save(); break;
                default:
                  print "<h1>Error</h1>";
                  print "<p>Unknown DO operator</p>";
              }  
            } else {
              print "<h1>Log In</h1>";
              print "<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>";
            }

          ?>
        </div>
      </div>
    </div>
  </body>
</html>