<?php

include('cwl.php');

$post = file_get_contents('php://input'); 

$postobj = json_decode($post);

if(is_array($postobj) || is_object($postobj)){
  $postobj = cwl\nosql::save($postobj);
}

$return = json_encode($postobj);

print $return;

?>