<?php

require 'desktop.php';

function worksafe_theme_status_form($text = '', $in_reply_to_id = NULL) {
  return desktop_theme_status_form($text, $in_reply_to_id);
}

function worksafe_theme_avatar($url, $force_large = false) {
  return '';
}

function worksafe_theme_css() {
  return '<style type="text/css">
table { width: 100%; }
body, input, textarea { color: #666; font-family: sans-serif; font-size: 11px; }
a { color: #447; text-decoration: none; }
a img { border: 0; }
tr.odd { background: #eee; }
td { padding: 0.3em; }
textarea, input { background: #eee; border: 1px solid #aaa; }
</style>';
}

function worksafe_theme_menu_bottom() {
  return '';
}

function worksafe_theme_timeline($feed) {
  if (count($feed) == 0) return theme('no_tweets');
  $rows = array();
  $page = menu_current_page();
  $date_heading = false;
  foreach ($feed as $status) {
    $time = strtotime($status->created_at);
    if ($time > 0) {
      $date = twitter_date('l jS F Y', strtotime($status->created_at));
      if ($date_heading !== $date) {
        $date_heading = $date;
        $rows[] = array(array(
          'data' => "<b>$date</b>",
          'colspan' => 2
        ));
      }
    } else {
      $date = $status->created_at;
    }
    $text = twitter_parse_tags($status->text);
    $link = theme('status_time_link', $status, !$status->is_direct);
    $actions = theme('action_icons', $status);
    $source = $status->source ? " from {$status->source}" : '';
    $from = '';
    if ($status->in_reply_to_status_id) {
      $from = "<small>in reply to <a href='status/{$status->in_reply_to_status_id}'>{$status->in_reply_to_screen_name}</a></small>";
    }
    $row = array(
      "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link $source<br /><span class='text'>{$text} {$from}</span>",
    );
    if ($page != 'user' && $avatar) {
      array_unshift($row, $avatar);
    }
    if ($page != 'replies' && twitter_is_reply($status)) {
      $row = array('class' => 'reply', 'data' => $row);
    }
    $rows[] = $row;
  }
  $content = theme('table', array(), $rows, array('class' => 'timeline'));
  if (count($feed) >= 15) {
    $content .= theme('pagination');
  }
  return $content;
}

function worksafe_theme_status_time_link($status, $is_link = true) {
  $time = strtotime($status->created_at);
  if ($time > 0) {
    if (twitter_date('dmy') == twitter_date('dmy', $time)) {
      $out = format_interval(time() - $time, 1). ' ago';
    } else {
      $out = twitter_date('H:i', $time);
    }
  } else {
    $out = $status->created_at;
  }
  if ($is_link)
    $out = "<a href='status/{$status->id}'>$out</a>";
  return $out;
}

?>