<?php

switch (@$_GET['_format']){
	case 'raw':
		print "<h1>RAW Output</h1>";
		print "<pre>";
		print_r(self::$directories);
		print "</pre>";		
		print "<pre>";
		print_r(self::$output);
		print "</pre>";		
		die();

	default:
		// See below	
}


if(!function_exists('contentHead')){
	function contentHead(){
		if(!(cwl\engine::theme_include('head.inc'))){
			if(isset(cwl\engine::$output['content'])){
				foreach(cwl\engine::$output['content'] as $key => $values){
					print $values['head'];
				}	
			}
		}
	}
}

if(!function_exists('contentBody')){
	function contentBody(){
		if(!(cwl\engine::theme_include('body.inc'))){
			if(isset(cwl\engine::$output['content'])){
				foreach(cwl\engine::$output['content'] as $key => $values){
					print $values['html'];
				}
			}
		}
	}
}

?>

<html>

	<head>
		<!-- Character set stuff -->
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<!-- Standard inclusion of Bootstrap, JQuery, etc. -->
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
		<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet" />
		<!-- Include a site specific CSS -->
		<?php
			$siteCSSFiles = glob(cwl\engine::domain() . "/*.css");
			foreach($siteCSSFiles as $siteCSS){
				$siteCSS = cwl\engine::basehref() . $siteCSS;
				print "<link href='{$siteCSS}?reload=" . uniqid() . "' rel='stylesheet' crossorigin='anonymous'>";
			}
		?>
		<?php
			contentHead();
		?>
	</head>

	<body>
		<?php
			contentBody();
		?>

	</body>

</html>