<?php

require 'class.twitter.php';

class DabrTwitterClient extends Twitter {
  function __construct() {
    global $user;
    $this->username = $user->username;
    $this->password = $user->password;
    $this->user_agent = 'dabr ' . DABR_VERSION;
  }
  
  function update($status) {
    $status = twitter_isgd($status);
    parent::update($status);
  }
  
  function process($url, $post_data=false) {
    $response = parent::process($url, $post_data);
    if ($response) {
      return $response;
    } else {
      $http_code = $this->response_info['http_code'];
      switch ($http_code) {
        case 401:
          global $user;
          $user->logout();
          theme('error', '<p>Error: Login credentials incorrect.</p>');

        default:
          $result = json_decode($this->response);
          theme('error', "<h2>Error {$http_code}</h2><p>{$result->error}</p>");
      }
    }
  }
}

function twitter_isgd($text) {
  return preg_replace_callback('#(http://|www)[^ ]{33,1950}\b#', 'twitter_isgd_callback', $text);
}
  
function twitter_isgd_callback($match) {
  $request = 'http://is.gd/api.php?longurl='.urlencode($match[0]);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $request);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

function twitter_parse_links_callback($matches) {
  $url = $matches[1];
  $encoded = urlencode($url);
  return "<a href='http://google.com/gwt/n?u={$encoded}'>{$url}</a>";
}

function twitter_parse_tags($input) {
  $out = preg_replace_callback('#([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)(?=\b)#is', 'twitter_parse_links_callback', $input);
  $out = preg_replace('#(@([a-z_A-Z0-9]+))#', '@<a href="user/$2">$2</a>', $out);
  return $out;
}

function format_interval($timestamp, $granularity = 2) {
  $units = array(
    'years' => 31536000,
    'days' => 86400,
    'hours' => 3600,
    'min' => 60,
    'sec' => 1
  );
  $output = '';
  foreach ($units as $key => $value) {
    if ($timestamp >= $value) {
      $output .= ($output ? ' ' : '').floor($timestamp / $value).' '.$key;
      $timestamp %= $value;
      $granularity--;
    }
    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : '0 sec';
}

function theme_status_form($text = '') {
  return "<form method='POST' action='update'><input name='status' value='{$text}'/> <input type='submit' value='Update' /></form>";
}

function theme_status($status) {
  $time_since = theme('status_time_link', $status);
  $parsed = twitter_parse_tags($status->text);
  $avatar = theme('avatar', $status->user->profile_image_url, 1);
  
  $out = theme('status_form', "@{$status->user->screen_name} ");
  $out .= "<p>$parsed</p>
<table align='center'><tr><td>$avatar</td><td><b>{$status->user->screen_name}</b>
<br>$time_since</table>";
  return $out;
}

function theme_user($feed) {
  $status = $feed[0];
  $out = theme('status_form', "@{$status->user->screen_name} ");
  $out .= "<table><tr><td>".theme('avatar', $status->user->profile_image_url, 1)."</td><td><b>{$status->user->screen_name}</b><br>{$status->user->description}</td></table>";
  $list = array();
  foreach ($feed as $status) {
    $list[] = twitter_parse_tags($status->text).' '.theme('status_time_link', $status);
  }
  $out .= theme('list', $list);
  return $out;
}

function theme_avatar($url, $force_large = false) {
  $size = $force_large ? 48 : 24;
  return "<img src='$url' height='$size' width='$size' />";
}

function theme_status_time_link($status) {
  $time_link = format_interval(time() - strtotime($status->created_at), 1);
  return "<small><a href='status/{$status->id}'>$time_link ago</a> from {$status->source}</small>";
}

function theme_timeline($feed) {
  $rows = array();
  foreach ($feed as $status) {
    $text = twitter_parse_tags($status->text);
    $link = theme('status_time_link', $status);
    
    $rows[] = array(
      theme('avatar', $status->user->profile_image_url),
      "<a href='user/{$status->user->screen_name}'>{$status->user->screen_name}</a> - {$link}<br>{$text}",
    );
  }
  return theme('table', array(), $rows, array('class' => 'timeline'));
}

?>