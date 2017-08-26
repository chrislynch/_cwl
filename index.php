<?php

include('_cwl/cwl.php');

header("Content-Type: text/html; charset=UTF-8");

cwl\engine::go(@$_GET['q']);

?>