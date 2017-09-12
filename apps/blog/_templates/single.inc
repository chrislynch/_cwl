<div class='row'>
  <div id='maincontent' class='col-xs-12'>
    <?php
      if($result->format == 'image'){
        print "<img src='{$result->image[0]}' style='width:100%'><br><br>";
      } elseif($result->format == 'gallery') {
        gallery($result);
      } elseif($result->format == 'video'){
        print media($result);
      } else {
        print "<br>";
      }
    ?>
    <?= $result->html ?>
    <div class='meta'>
      <?= $result->meta ?>
    </div>
  </div>
</div>
<div class='row'>
  <div class='col-xs-12'>
    <hr>
    <h3>
      Comments
    </h3>
    <div id="disqus_thread"></div>
    <script>
    /**
    *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
    *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables*/

    var disqus_config = function () {
    this.page.url = 'https://www.cwlynch.com/<?= $result->uri ?>';  // Replace PAGE_URL with your page's canonical URL variable
    this.page.identifier = '<?= $result->guid ?>'; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
    };

    (function() { // DON'T EDIT BELOW THIS LINE
    var d = document, s = d.createElement('script');
    s.src = 'https://cwlynch.disqus.com/embed.js';
    s.setAttribute('data-timestamp', +new Date());
    (d.head || d.body).appendChild(s);
    })();
    </script>
    <noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
  </div>
</div>