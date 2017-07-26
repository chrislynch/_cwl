<html>
  <head>
    <title>Error Handler</title>
    <style>
      body { 
        padding: 20px;
        font-family: monospace;
      }
    </style>
  </head>
  <body>
    <h1>
      An error has occured
    </h1>
    <pre>
    <?php
      session_start();
      print_r($_SESSION['error']);
      session_destroy();
    ?>
    </pre>
  </body>
</html>