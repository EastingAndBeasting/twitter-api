<?php require_once('twitter.php'); ?>

<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Latest tweets</title>
        <link rel="stylesheet" type="text/css" href="main.css">
    </head>

    <body>
      <div class="twitterfeed">
        <?php
          $twitter = new TwitterFeed('NUnl', 5);
          $twitter->get_tweets();
        ?>
      </main>
    </body>

</html>
