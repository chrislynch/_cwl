<?php include('_templates/outnow.inc'); ?>
<div class='container'>
<div class='row'>
  <div class='col-xs-12'>
    <h2>
      Showreel
    </h2>
  </div>
  <div class='col-xs-12 col-md-4'>
    <div class="embed-responsive embed-responsive-4by3">
      <iframe width="560" height="315" src="https://www.youtube.com/embed/MPWh72a3lrU" frameborder="0" allowfullscreen></iframe>
    </div>
  </div>
  <div class='col-xs-12 col-md-4'>
    <div class="embed-responsive embed-responsive-4by3">
      <iframe width="560" height="315" src="https://www.youtube.com/embed/15zRQGjqYaI" frameborder="0" allowfullscreen></iframe>
    </div>
  </div>
  <div class='col-xs-12 col-md-4'>
    <div class="embed-responsive embed-responsive-4by3">
      <iframe width="560" height="315" src="https://www.youtube.com/embed/6gUZ6afCHJE" frameborder="0" allowfullscreen></iframe>
    </div>
  </div>
  <div class='col-xs-12'>
    &nbsp;
  </div>
</div>
</div>

<div class='stripe'>
  <div class='container'>
    <div class='row'>
      <div class='col-xs-12'>
        <h2>
          Latest News &amp; Popular Posts
        </h2>
      </div>
      <?php
        foreach($results as $result){
          if(@$result->promoted == 1) {
          ?>
            <div class='col-xs-12 col-md-4'>
              <div style="height: 200px; width: 100%; background-size: cover; background-image: url('<?= $result->image[0] ?>')">
              </div>
              <h3>
                <?= $result->name ?>
              </h3>
              <?= $result->html ?>
            </div>
          <?php
          }
        }
      ?>
    </div>
  </div>
</div>

