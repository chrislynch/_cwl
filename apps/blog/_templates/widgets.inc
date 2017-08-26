<?php

$sidebars = array();
$footers = array();

$sidebars['Twitter'] = '<a class="twitter-timeline" href="https://twitter.com/cwlynch_mwm">Tweets by cwlynch_mwm</a> <script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';  

/*
$sidebars['Social'] = '';
$query = cwl\db::query("SELECT i.guid,i.name
                      from _index i
                      join post_format pf on pf.guid = i.guid
                      where i.type = 'post' AND i.status = 1 and pf.value = 3 order by i.timestamp DESC");
while($result = $query->fetch()){
  $sidebars['Social'] .= "<p><i class='fa fa-quote-left'></i>&nbsp;{$result['name']}&nbsp;<i class='fa fa-quote-right'></i>";
  if($result['media'] !== ''){
    $sidebars['Social'] .= "<br><small><a href='{$result['media']}'>{$result['media']}</a></small>";
  }
  $sidebars['Social'] .= "</p>";
}
*/

$footers['Projects'] = '';
$query = cwl\db::query("SELECT name,uri from _index where type = 'category' order by name asc");
while($result = $query->fetch()){
  $footers['Projects'] .= "<a href='category/{$result['name']}'>{$result['name']}</a><br>";
}

$footers['Tags'] = '';
$query = cwl\db::query("SELECT value,count(0) FROM post_tag where value <> '' group by value having count(0) > 1 order by count(0) desc");
while($result = $query->fetch()){
  $footers['Tags'] .= "<a href='tag/{$result['value']}'>{$result['value']}</a> | ";
}

?>