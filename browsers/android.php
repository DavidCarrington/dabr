<?php

require 'desktop.php';
function android_theme_external_link($url) {
  return "<a href='$url' target='_new'>$url</a>";
}
function android_theme_status_form($text = '') {
  return desktop_theme_status_form($text);
}
function android_theme_search_form($query) {
  return desktop_theme_search_form($query);
}

function android_theme_avatar($url, $force_large = false) {
  return "<img src='$url' width='48' height='48' />";
}

function android_theme_page($title, $content) {
  $body = theme('menu_top');
  $body .= $content;
  ob_start('ob_gzhandler');
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>',$title,'</title><base href="',BASE_URL,'" />
'.theme('css').'
<body id="thepage">', $body, '</body>
</html>';
  exit();
}

function android_theme_menu_top() {
  $links = array();
  $main_menu_titles = array('home', 'replies', 'directs', 'search');
  foreach (menu_visible_items() as $url => $page) {
    $title = $url ? $url : 'home';
    $type = in_array($title, $main_menu_titles) ? 'main' : 'extras';
    $links[$type][] = "<a href='$url'>$title</a>";
  }
  if (user_is_authenticated()) {
    $user = user_current_username();
    array_unshift($links['extras'], "<b><a href='user/$user'>$user</a></b>");
  }
  array_push($links['main'], '<a href="#" onclick="return toggleMenu()">more</a>');
  $html = '<div id="menu">';
  $html .= theme('list', $links['main'], array('id' => 'menu-main'));
  $html .= theme('list', $links['extras'], array('id' => 'menu-extras'));
  $html .= '</div>';
  return $html;
}

function android_theme_menu_bottom() {
  return '';
}


function android_theme_css() {
  $out = '<link rel="stylesheet" href="browsers/android.css" />';
  $out .= '<script type="text/javascript">'.file_get_contents('browsers/android.js').'</script>';
  return $out;
}
?>