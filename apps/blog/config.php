<?php

if(class_exists('toolbox')){
  $toolboxConfig = new stdClass();
  $toolboxConfig->types = array();
  $toolboxConfig->types['post'] = array();
  $toolboxConfig->types['post']['name'] = 'Post';
  $toolboxConfig->types['post']['fields'] = array();
  
  $toolboxConfig->types['post']['fields']['name'] = array();
  $toolboxConfig->types['post']['fields']['name']['title'] = 'Post Name';
  $toolboxConfig->types['post']['fields']['name']['type'] = 'text';
  
  $toolboxConfig->types['post']['fields']['uri'] = array();
  $toolboxConfig->types['post']['fields']['uri']['title'] = 'Post URI';
  $toolboxConfig->types['post']['fields']['uri']['type'] = 'uri';
  
  $toolboxConfig->types['post']['fields']['media'] = array();
  $toolboxConfig->types['post']['fields']['media']['title'] = 'Post Media Link';
  $toolboxConfig->types['post']['fields']['media']['type'] = 'text';
  
  $toolboxConfig->types['post']['fields']['html'] = array();
  $toolboxConfig->types['post']['fields']['html']['title'] = 'Post Content';
  $toolboxConfig->types['post']['fields']['html']['type'] = 'textarea';
  
  $toolboxConfig->types['post']['fields']['status'] = array();
  $toolboxConfig->types['post']['fields']['status']['title'] = 'Post Status';
  $toolboxConfig->types['post']['fields']['status']['type'] = 'select';
  $toolboxConfig->types['post']['fields']['status']['options'] = array(0 => 'Unpublished', 1 => 'Published');
  
  $toolboxConfig->types['post']['fields']['format'] = array();
  $toolboxConfig->types['post']['fields']['format']['title'] = 'Post Format';
  $toolboxConfig->types['post']['fields']['format']['type'] = 'select';
  $toolboxConfig->types['post']['fields']['format']['options'] = array('post' => 'Post', 'image' => 'Image', 'gallery' => 'Gallery', 
                                                                       'link' => 'Link', 'video' => 'Video', 'audio' => 'Audio',
                                                                       'quote' => 'Quote', 'aside' => 'Aside');
  
  $toolboxConfig->types['post']['fields']['promoted'] = array();
  $toolboxConfig->types['post']['fields']['promoted']['title'] = 'Post Promotion';
  $toolboxConfig->types['post']['fields']['promoted']['type'] = 'select';
  $toolboxConfig->types['post']['fields']['promoted']['options'] = array(0 => 'Not Promoted', 1 => 'Promoted');

  $toolboxConfig->types['post']['fields']['image'] = array();
  $toolboxConfig->types['post']['fields']['image']['title'] = 'Image';
  $toolboxConfig->types['post']['fields']['image']['type'] = 'file';  
  
  $toolboxConfig->types['post']['fields']['category'] = array();
  $toolboxConfig->types['post']['fields']['category']['title'] = 'Category';
  $toolboxConfig->types['post']['fields']['category']['type'] = 'select';
  $toolboxConfig->types['post']['fields']['category']['options'] = "SELECT guid as key, name as value FROM _index WHERE type = 'category' ORDER BY name ASC";
  
  $toolboxConfig->types['post']['fields']['tag'] = array();
  $toolboxConfig->types['post']['fields']['tag']['title'] = 'Tags';
  $toolboxConfig->types['post']['fields']['tag']['type'] = 'csv';
  
  $toolboxConfig->users = array();
  $toolboxConfig->users['chris'] = array('password' => '0e2a2f196a0cf8c8df32e65dfd2efd4d');
  
  toolbox::$config = $toolboxConfig;  
}

?>