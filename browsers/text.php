<?php

function text_theme_avatar($url, $force_large = false) {
  return '';
}

function text_theme_action_icons($status) {
  $user = $status->from_user ? $status->from_user : $status->user->screen_name;
  $actions = array();
  
  if ($status->user->screen_name != $GLOBALS['user']['username']) {
    $actions[] = "<a href='directs/create/{$user}'>DM</a>";
  }
  if (!$status->is_direct) {
    if ($status->favorited == '1') {
      $actions[] = "<a href='unfavourite/{$status->id}'>UNFAV</a>";
    } else {
      $actions[] = "<a href='favourite/{$status->id}'>FAV</a>";
    }
  }
  $actions[] = "<a href='retweet/{$status->id}'>RT</a>";
  return implode(' ', $actions);
}

?>