<?php if(isset($_GET['_ui'])){ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>API Debugging Harness</title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/css-spinning-spinners/1.1.0/load2.css" />
</head>
<body>
<div class='container'>
  <div class='row'>
    <div class='col-xs-12'>
      <h1>API Test Harness</h1>      
		</div>
	</div>
	<div class='row'>
		<div class='col-xs-12 col-sm-8'>
			URL: <input type="text" id="url" name="url" value="http://cto.poweredbygravit-e.co.uk/blog/api.php" style="width:80%"><br/>
		</div>
		<div class='col-xs-12 col-sm-4'>
			Method: 
			<select id="method" name="method">
				<option value="POST">POST</option>
				<option value="GET">GET</option>
			</select>
		</div>
	</div>
	<div class='row'>
		<div class='col-xs-12 '>
			<p>
				<br/>
				POST Body / GET Params: 
				<br/><textarea id="textinput" rows=10 style="width:100%; font-family:monospace"></textarea><br/>
				<br/><input type="submit" value="Go" id="submitbtn"  onclick="poststuff();"/><br/>	
			</p>
    </div>
	</div>
	<div class='row'>
    <div class='col-xs-12'>
      <h2>
        Response
      </h2>
      <div id="response"></div>      
    </div>
  </div>
</div>

<script type="text/javascript">  
    var poststuff= function(){
      var data = {}
      var temp
			var url
			var method
  
      $("#response").html('');
      $('#response').addClass('loading')
      
      /*
      data.text = $("#textinput").val()
      data = JSON.stringify(data)
      */
      data = $("#textinput").val()
			url = $('#url').val()
			method = $('#method').val()
			if(method == 'GET'){
				url = url + '?' + data
				data = ''
			}
			
      // you need contentType in order for the POST to succeed
      var promise = $.ajax({
        type: method,
        url: url,
        data: data,
        contentType: "application/json; charset=utf-8",
        dataType: "text"
      })
      $.when(promise).then(function(json){
        $("#response").html("<pre>" + json + "</pre>")
        $('#response').removeClass('loading')
      })
    }
</script>
</body>
</html>
  <?php
  exit();
}

/*
* Here beginneth the API proper
*/

include('cwl.php');

$method = $_SERVER['REQUEST_METHOD'];

switch(strtolower(trim($method))){
  case 'post':
    api_post();
    break;
  case 'patch':
  case 'put':
  case 'get':
		switch($_GET['_format']){
			case 'RSS':
				api_get_rss();
				break;
			case 'JSON':
			default:
				api_get();		
		}
		break;
  case 'delete':
    break;
  case 'options':
    break;
  default:
    print "Me no understand";
}

function api_post(){

  $post = file_get_contents('php://input'); 
  $post = urldecode($post);
  $postobj = FALSE;
  $objFormat = FALSE;

  // Sort out ` formatting in JSON posts.
  $post = str_ireplace('"','\"',$post);
  $post = str_ireplace('`','"',$post);
  
  // Try to work out what type of object we have
  $postobj = json_decode($post);
  if(is_object($postobj)) { $objFormat = 'JSON'; }
  
  print_r($postobj);
  
  // If we got to an object, save the object
  if(is_object($postobj)){
    $guid = cwl\nosql::save($postobj);
  }
  
  print_r($postobj);

  if($objFormat === FALSE){
    print "Error: Invalid object format";
  } else {
    switch($objFormat){
      case 'JSON': $return = json_encode($postobj); break;
    }
    print $return;
  }  
}

function api_get(){
	$results = cwl\nosql::search($_GET);
	while($result = $results->fetch()){
		print_r($result);
	}
}

function api_get_rss(){
	$results = cwl\nosql::search($_GET);
	
	//header("Content-Type: text/xml; charset=UTF-8");
	print '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">

<channel>
  <title>RSS Feed</title>
  <link>' . cwl\engine::url()  . '</link>
  <description>RSS Feed for ' . cwl\engine::url() . '</description>';
	
	foreach($results as $result){
		print '<item>
			<guid>' . cwl\engine::domain() . $result['uri'] . '</guid>		
			<title>' . $result['name'] . '</title>
			<link>' . cwl\engine::domain() . $result['uri'] . '</link>
  	</item>';
	}
  	
	print '
	</channel>
</rss>';
}

?>