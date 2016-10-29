<?php
// Settings
curl_setopt($ch, CURLOPT_SSLVERSION, 3); // Force SSLv3 to fix Unknown SSL Protocol error
setlocale (LC_TIME, array("nl_NL", "nl_NL.utf8", "Dutch")); // crucial for localised date display // (nl_NL)?
date_default_timezone_set('Europe/Amsterdam');
ini_set('display_errors', 1);


// TODO
// Check MD5sum of cachefile

// http://stackoverflow.com/questions/12916539/simplest-php-example-for-retrieving-user-timeline-with-twitter-api-version-1-1/15314662#15314662
// https://github.com/J7mbo/twitter-api-php


require_once('TwitterAPIExchange.php');

class TwitterFeed {
    public $url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
    public $requesmethod = "GET";
    public $settings = array(
      'oauth_access_token' => "748413741067284480-Hy4NQZ1entAz5sw4ffopGdybAVRDdXm",
      'oauth_access_token_secret' => "MkvZCISbE5KDYwdv113uHZAdkRu3CMtywMGexeQX9s2b1",
      'consumer_key' => "StSYCTbEUm0CMkuSQ5fLza8ve",
      'consumer_secret' => "fCea2piGuPpvOIcy1egCGThZi2fKeJXGuQxjvXHETx4gcnUKHy"
    );
    public $cache_duraction = 10;
    public $cache_file = __DIR__.'/cache/cachefile';

    public function __construct($screenname, $num_tweets) {
      $this->screenname = $screenname;
      $this->num_tweets = $num_tweets;
      $this->getfield = "?screen_name=".$this->screenname."&count=".$this->num_tweets;
  	}

    public function get_tweets() {
      $tweets = $this->get_json_cache();
      if($this->check_errors($tweets)) {
        $this->build_html($tweets);
      }
    }

    public function get_json_cache() {
      // http://stackoverflow.com/questions/5262857/5-minute-file-cache-in-php
      if (file_exists($this->cache_file) && (filemtime($this->cache_file) > (time() - $this->cache_duraction ))) {
          // Get from cache
         $tweets = json_decode(file_get_contents($this->cache_file));
         return $tweets;
      } else {
        // Write to cache
        $json = $this->api_exchange();
        file_put_contents($this->cache_file, $json, LOCK_EX);
        $tweets = json_decode($this->api_exchange());
        return $tweets;
      }
    }

    public function api_exchange() {
      $twitter = new TwitterAPIExchange($this->settings);
      $json = $twitter->setGetfield($this->getfield)
                   ->buildOauth($this->url, $this->requesmethod)
                   ->performRequest();
      return $json;
    }

    public function check_errors($tweets) {
      if(isset($tweets->errors)) {
        $error = $tweets->errors[0]->message." (Code ".$tweets->errors[0]->code . ")";
        echo $error;
        return false;
      }
      return true;
    }

    public function build_html($tweets) {
      echo "<h1>Tweets by ".$tweets[0]->user->name."</h1>";
      echo "<ul>";
      foreach($tweets as $tweet) {
        echo "<li>";
        echo "<p class='tweet-date'>";
        echo $this->time_elapsed_string($tweet->created_at);
        // echo strftime("%A %e %B %Y, %H:%M", strtotime($tweet->created_at))."<br>";
        echo "</p>";
        //echo "@".$tweet->user->screen_name."<br>";
        echo $this->twitterize($tweet->text)."<br>";
        echo "</li><br>";
      }
      echo "</ul>";
    }

    // http://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
    public function time_elapsed_string($datetime, $full = false) {
      $now = new DateTime;
      $ago = new DateTime($datetime);
      $diff = $now->diff($ago);

      $diff->w = floor($diff->d / 7);
      $diff->d -= $diff->w * 7;

      $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
      );
      foreach ($string as $k => &$v) {
        if ($diff->$k) {
          $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
          unset($string[$k]);
        }
      }

      if (!$full) $string = array_slice($string, 0, 1);
      return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    // http://pastebin.com/gJs9nb7e
    // Creates links in the raw tweet text
    public function twitterize($raw_text) {
      $output = $raw_text;
      // parse urls;
      $output = preg_replace(
      '@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@', '<a href="$1" target="_blank">$1</a>', $output);
      // parse usernames
      $output = preg_replace(
      '/@(\w+)/', '<a href="http://twitter.com/$1" target="_blank">@$1</a>',$output);
      // parse hashtags
      $output = preg_replace(
      '/\s+#(\w+)/', ' <a href="https://twitter.com/search?q=%23$1" target="_blank">#$1</a>', $output);

      return $output;
    }

}

// $twitter = new TwitterFeed('NUnl', 6);
// $twitter->get_tweets();

?>
