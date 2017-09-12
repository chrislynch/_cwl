<?php
$results = array();

$seo = new stdClass();
$seo->h1 = $seo->title = 'CW Lynch: Writer, Technologist, Hypnotist, Film Maker';
$seo->description= 'CW Lynch: Cardiff based Writer, Technologist, Hypnotist, and Film Maker';

$outputmode = 2;

if(strlen(trim(@$_GET['q'])) > 0){
  
  switch(cwl\engine::parray(0)){
    case 'tag':
      $query = cwl\db::query("SELECT tag.guid
                              FROM post_tag tag
                              JOIN post_status status ON status.guid = tag.guid
                              WHERE tag.value = '" . cwl\engine::parray(1) . "' and status.value = 1 ORDER BY tag.guid DESC");
      $seo->title = cwl\engine::parray(1) . " | " . $seo->title;
      $seo->description = 'Posts about ' . cwl\engine::parray(1) . ' from ' . $seo->description;
      $seo->canonical = 'tag/' . cwl\engine::parray(1);
      $seo->h1 = "Posts about " . cwl\engine::parray(1);
      break;
      
    case 'category':
      $category = cwl\db::row("SELECT guid FROM _index WHERE name = '" . cwl\engine::parray(-1) . "'"); 
      $query = cwl\db::query("SELECT category.guid
                              FROM post_category category
                              JOIN post_status status ON status.guid = category.guid
                              WHERE category.value = '" . $category['guid'] . "' and status.value = 1 ORDER BY category.timestamp DESC");
      $seo->title = cwl\engine::parray(1) . " | " . $seo->title;
      $seo->description = 'Posts about ' . cwl\engine::parray(1) . ' from ' . @$seo->description;
      $seo->canonical = 'category/' . cwl\engine::parray(1);
      $seo->h1 = "Posts about " . cwl\engine::parray(1);
      break;
      
    default:
      $query = cwl\db::query("SELECT guid FROM _index WHERE uri = '" . cwl\engine::parray(-1) . "'");
      $seo->canonical = cwl\engine::parray(-1);
      $outputmode = 1;
  }
} else {
  /*
  $query = cwl\db::query("
    SELECT * from (
      select guid
      FROM _index
      WHERE type='post' and status = 1 AND flag = 1
      order by timestamp DESC
      LIMIT 3)
    UNION
    select * from (
      select guid
      FROM _index
      WHERE type='post' and status = 1 AND flag = 0
      order by timestamp DESC LIMIT 20)");
  */
  $query = cwl\db::query("
    select guid
    FROM _index
    WHERE type='post' and status = 1 AND flag = 0
    order by timestamp DESC LIMIT 20");
  $outputmode = 3;
}

$hasResults = FALSE;

while($result = $query->fetch()){
  // Raise item flag
  $hasResults = TRUE;
  
  // Load result
  $result = cwl\nosql::load($result['guid']);
  
  // Sort out line breaks (precursor to supporting Markdown)
  $result->html = explode("\n",$result->html);
  foreach($result->html as $key=>$html){
    $result->html[$key] = "<p>$html</p>";
  }
  
  // Convert to content to single item or multi item mode
  switch($outputmode){
    case 1:
      // Single item
      $seo->title = $result->name . " | " . $seo->title;
      $seo->description = strip_tags(str_ireplace('"','',$result->html[0]));
      $seo->h1 = $result->name;
      $result->html = implode("",$result->html);
      $toolboxLink = 'toolbox.php?do=edit&guid=' . $result->guid;
      break;
      
    case 2:
    case 3:
    default:
      // Multi item
      $result->html = $result->html[0];
      $toolboxLink = 'toolbox.php';
  }
    
  $result->html .= '&nbsp;';
  
  $result->meta = 'Category: ';
  if(is_array($result->category)){
    foreach($result->category as $category){
      $categorydata = cwl\db::row('SELECT name FROM _index WHERE guid = "' . $category . '"');
      $result->meta .= "<a href='category/{$categorydata['name']}'>{$categorydata['name']}</a> ";
    }  
  } else {
    $categorydata = cwl\db::row('SELECT name FROM _index WHERE guid = "' . $result->category . '"');
    $result->meta .= "<a href='category/{$categorydata['name']}'>{$categorydata['name']}</a> ";
  }
  
  $result->meta .= '<br>Tags: ';
  if(is_array($result->tag)){
    foreach($result->tag as $tag){
      $result->meta .= "<a href='tag/{$tag}'>{$tag}</a> ";
    }  
  }
  
  $results[] = $result;
}

if(!$hasResults){
  http_response_code(404);
  $results = array();
  $results[0] = new cwl\noSQLstdClass();
  $results[0]->name = 'Page not found';
  $results[0]->html = "<p>Sorry, looks like we couldn't find the page you were looking for. You probably want to <a href=''>go to the homepage</a></p>";
}

include('_templates/widgets.inc');

?>

<html>
  <head>
    <?php print cwl\engine::basehref(TRUE); ?>
    <title><?= $seo->title ?></title>
    <meta name="description" value="<?= $seo->description ?>">
    <link rel="canonical" href="<?php print cwl\engine::basehref(FALSE); ?><?= $seo->canonical ?>">
    <!-- JQuery -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g=" crossorigin="anonymous"></script>
    <!-- Default Bootstrap -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <!-- Bootswatch -->
    <link href="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/yeti/bootstrap.min.css" rel="stylesheet" integrity="sha384-HzUaiJdCTIY/RL2vDPRGdEQHHahjzwoJJzGUkYjHVzTwXFQ2QN/nVgX7tzoMW3Ov" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <!-- Special fonts -->
    <link href="https://fonts.googleapis.com/css?family=Special+Elite" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Cutive+Mono" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nixie+One|Space+Mono" rel="stylesheet">
    <style>
      img { max-width: 100%}
      hr { width: 50%; }
      /*
      html { font-size: 26px;}
      @media only screen and (min-width : 768px) { html { font-size: 22px; } }
      @media only screen and (min-width : 992px) { html { font-size: 18px; } }
      @media only screen and (min-width : 1200px) { html { font-size: 14px; } }
      */
      
      /* html,body,p { font-family: 'Space Mono', monospace; font-size: 18px; line-height: 22px; color: #555} */
      html,body,p { font-size: 16px; line-height: 20px; color: #555} 
      p { text-align: justify; margin-bottom: 1rem }
      h1,h2,h3,h4,h5 { font-family: 'Special Elite', monospace; color: black}
      h1 { font-size: 2.2rem; line-height: 2.4rem; }
      h2 { font-size: 2rem;  line-height: 2.2rem;}
      h3 { font-size: 1.8rem;  line-height: 2rem;}
      h4 { font-size: 1.6rem;  line-height: 1.8rem;}
      h5 { font-size: 1.2rem;  line-height: 1.4rem;}
      .stripe { width:100%; padding-top:10px; padding-bottom:10px; background-color:gainsboro}
      
      .navbar .fa {
        font-size: 20px;
      }
      
      #maincontent p:first-of-type {
        font-size: 1.6rem;
        line-height: 2.2rem;
        color: darkgray;
        font-weight: 700;
        font-family: 'Special Elite';
        text-align: left;
      }
            
      #content {
        padding-right: 40px;
        overflow: hidden;
      }
      
      #sidebar {
        padding-left: 20px;
        border-left: 2px solid darkred;
        margin-top: 22px;
      }
    </style>
    <?php
      print \cwl\config::get('htmlheader');
    ?>
  </head>
  <body>
    
    <div class='container'>
      <div class='row'>
        <div class='col-xs-12'>
          <nav class="navbar navbar-default">
            <div class="container-fluid">
              <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2">
                  <span class="sr-only">Toggle navigation</span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="">CW LYNCH</a>
              </div>

              <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-2">
                <ul class="nav navbar-nav">
                  <li><a href="#"></a></li>
                  
                </ul>
                <ul class="nav navbar-nav navbar-right">
                  
                  <li><a href="https://www.facebook.com/cwlynchwriter/"><i class="fa fa-facebook-official"></i></a></li>
                  <li><a href="https://twitter.com/cwlynch_mwm"><i class="fa fa-twitter"></i></a></li>
                  <li><a href="https://www.linkedin.com/in/chrislynchtechnologywriter/"><i class="fa fa-linkedin"></i></a></li>
                  <li><a href="https://www.instagram.com/chrislynchmwm/"><i class="fa fa-instagram"></i></a></li>
                  <li><a href="https://www.amazon.co.uk/Chris-Lynch/e/B004TVOAWK"><i class="fa fa-amazon"></i></a></li>
                  <li><a href="http://www.imdb.com/name/nm8263048/"><i class="fa fa-imdb"></i></a>
                  <li><a href="https://github.com/chrislynch"><i class="fa fa-github" aria-hidden="true"></i></a></li>
                  <li><a href="contact"><i class="fa fa-envelope"></i></a></li>
                  <?php
                    if(strlen(@$_COOKIE['cwlToolboxID']) > 0){
                      print '<li><a href="' . $toolboxLink . '"><i class="fa fa-wrench"></i></a></li>';
                    }
                  ?>
                </ul>
              </div>
            </div>
          </nav>
        </div>
      </div>
    </div>
    
    <?php 
    if($outputmode == 1){
      if($results[0]->format == 'post'){
         print '<div style="height: 600px; width: 100%; background-size: contain; background-repeat: no-repeat; background-position: center; background-image: url(\'' . $results[0]->image[0] . '\')">';
      }     
    } else {
      print '<div style="height: 600px; width: 100%; background-size: cover; background-image: url(\'_uploads/cwlynch.jpg\')">';
    }
    ?>
    
    <div class='container'>
      <div class='row'>
        <div class='col-xs-12'>
          <?php
            switch($outputmode){
              case 1:
                if($results[0]->format == 'post'){
                ?>
                  <h1 style='background-color: rgba(0,0,0,0.9); color: white; position: absolute; bottom: -500px; width: 90%; padding: 10px;'>
                    <?= $results[0]->name ?>
                  </h1>
                <?php
                } else {
                  ?>
                  <h1>
                    <?= $results[0]->name ?>
                  </h1>
                  <?php
                }
                break;
              default:
                ?>
                  <h1 style='background-color: rgba(0,0,0,0.9); color: white; position: absolute; bottom: -500px; width: 90%; padding: 10px;'>
                    <?= $seo->h1 ?>
                  </h1>
                <?php
            }
          ?>
        </div>
      </div>
    </div>
    </div> <!-- Closing div for item in print above -->
    
    <?php
      if($outputmode == 3) { include('_templates/home.inc'); }
    ?>
    
    <div class='container'>
      <div class='row'>
        <div id="content" class='col-xs-12 col-md-8'>
          <!-- Content -->
          <?php
            foreach($results as $result) {
              switch($outputmode){
                case 1:
                  include('_templates/single.inc');
                break;
                  
                case 2:
                default:
                  if(@$result->promoted == 0 || $outputmode !== 3) {
                    include('_templates/multi.inc');
                  }
              }
            }
          ?>
         
        </div>
        <div id='sidebar' class='col-xs-12 col-md-4'>
          <?php foreach($sidebars as $title => $html){ ?>
            <div class='col-xs-12'>
            <h4><?= $title ?></h4>
            <?= $html ?>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
    <?php
      if($outputmode !== 3){
        include('_templates/outnow.inc');
      }
    ?>
    <div class='stripe'>
      <div class='container'>
        <div id='footer' class='row' style='min-height: 120px; background: gainsboro; margin-top: 20px'>
          <div class='col-xs-12 col-md-6'>
            <h4>
              Hello, I'm Chris Lynch.
            </h4>
            <p>As <strong>CW Lynch</strong> I am a writer of comics, graphic novels, screenplays, books, and a variety of other things.
            I have written for a wide range of publications and publishers in the UK and the US including The Judge Dredd Megazine, Arcana, Markosia, Metaverse, The Psychedelic Journal of Time Travel, 2026 Books, Accent UK, Something Wicked, The Sorrow, The Mad Scientist Journal, KZine, Wilde Times, Another Realm, 10thology, Midnight Hour, and Insomnia Publications.
            </p>
            <p>
              In 2015 I wrote and co-produced the short "The Black Room", now available to watch on Amazon Prime. In 2017 I co-wrote and successfully crowd-funded my first feature length film, "OffWorld".
            </p>
            <p>I write software to help people be better writers and I'm also CTO of an <a href="http://www.gravit-e.co.uk">eCommerce solutions provider based in Cardiff</a>. </p>
            <p>I occasionally hypnotise people, which is the closest I have ever come to having a real superpower. Honestly.</p>
          </div>
          <?php foreach($footers as $title => $html){ ?>
              <div class='col-xs-12 col-md-3'>
                <h4><?= $title ?></h4>
                <?= $html ?>
              </div>
            <?php } ?>
          <div class='col-xs-12'>&nbsp;</div>
          <div class='col-xs-12'>Social and Copyright</div>
        </div>
      </div>
    </div>
  </body>
</html>


<?php
  function media($result){
    $string     = $result->media;
    $search     = '/www.youtube\.com\/watch\?v=([a-zA-Z0-9-]+)/smi';
    $replace    = "<div class='class='embed-responsive embed-responsive-16by9'><iframe width='100%' height='420' src='https://youtube.com/embed/$1' frameborder='0' allowfullscreen></iframe><br><br></div>";
    $content    = preg_replace($search,$replace,$string);
    $content    = explode('<div',$content);
    $content    = "<div" . array_pop($content);
    return $content;
  }
  
  function gallery($result){
  ?>
    <div id="myCarousel" class="carousel slide" data-ride="carousel">
      <!-- Indicators -->
      <!--
      <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
        <li data-target="#myCarousel" data-slide-to="2"></li>
      </ol>

      <!-- Wrapper for slides -->
      <div class="carousel-inner">
      <?php
        $active = ' active';
        foreach($result->image as $image){
          print '<div class="item' . $active . '">';
          print '<img src="' . $image . '">';
          print '</div>';
          $active = '';
        }
      ?>
      </div>

      <!-- Left and right controls -->
      <a class="left carousel-control" href="#myCarousel" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left"></span>
        <span class="sr-only">Previous</span>
      </a>
      <a class="right carousel-control" href="#myCarousel" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right"></span>
        <span class="sr-only">Next</span>
      </a>
    </div><br><br>
    <script>$("#myCarousel").carousel();</script>
  <?php
  }
?>