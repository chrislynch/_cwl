<div class='<?= $result->format ?>'>
<?php
// Placeholder
if(strlen(@$result->image[0]) == 0){
  $result->image = array(0 => "https://loremflickr.com/g/320/240/" . str_ireplace('-',',',$result->uri));
}
  
switch($result->format){
  case 'aside':
  case 'social':
    ?>
<div class='row'>
  <div class='col-xs-12'>
    <?= $result->html ?>
    <div class='meta'>
      <?= $result->meta ?>
    </div>
  </div>
</div>
  <?php
    break;
  case 'video':
    ?>
  <div class='row'><div class='col-xs-12'><h4><a href='<?= $result->uri ?>'><?= $result->name; ?></a></h4></div></div>
    <div class='row'>
    <div class='col-xs-12'>
      <?php print media($result); ?>
    </div>
    <div class='col-xs-12'>
      <?= $result->html ?>
      <div class='meta'>
        <?= $result->meta ?>
      </div>
    </div>
  </div>
  <?php
    break;
  case 'image':
    ?>
  <div class='row'><div class='col-xs-12'><h4><a href='<?= $result->uri ?>'><?= $result->name; ?></a></h4></div></div>
<div class='row'>
  <div class='col-xs-12'>
    <div style="width:100%; max-height:600px; overflow:hidden">
      <a href='<?= $result->uri ?>'>
        <img src='<?= $result->image[0] ?>' style="width:100%">  
      </a>
    </div>
    <br>
  </div>
  <div class='col-xs-12'>
    <?= $result->html ?>
    <div class='meta'>
      <?= $result->meta ?>
    </div>
  </div>
</div>
  <?php    
    break;
  default:
    ?>
  <div class='row'><div class='col-xs-12'><h4><a href='<?= $result->uri ?>'><?= $result->name; ?></a></h4></div></div>
<div class='row'>
  <div class='col-xs-4'>
    <a href='<?= $result->uri ?>'>
    <div style="height: 200px; width: 100%; background-size: cover; background-image: url('<?= $result->image[0] ?>')"></div>
    <br>
    </a>
  </div>
  <div class='col-xs-8'>
    <?= $result->html ?>
    <div class='meta'>
      <?= $result->meta ?>
    </div>
  </div>
</div>
  <?php
}
?>
</div>
<div class='row'>
  <div class='col-xs-12'>
    <hr>
  </div>
</div>