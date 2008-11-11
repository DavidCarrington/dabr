<?php

require 'desktop.php';
function anroid_theme_external_link($url) {
  return "<a href='$url'>$url</a>";
}
function android_theme_status_form($text = '') {
  return desktop_theme_status_form($text);
}
function android_theme_search_form($query) {
  return desktop_theme_search_form($query);
}

function android_theme_avatar($url, $force_large = false) {
  return "<img src='$url' />";
}
?>