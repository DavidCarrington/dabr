<?php

require 'touch.php';

function bigtouch_theme_action_icon($url, $image_url, $text) {
	$image_url = str_replace('.png', 'L.png', $image_url);
	$image_url = str_replace('.gif', 'L.png', $image_url);
	if ($text == 'MAP')	{
		return "<a href='$url' target='" . get_target() . "'><img src='$image_url' alt='$text' width='24' height='24' /></a>";
	}
	return "<a href='$url'><img src='$image_url' alt='$text' width='24' height='24' /></a>";
}

function bigtouch_theme_status_form($text = '', $in_reply_to_id = NULL) {
	return desktop_theme_status_form($text, $in_reply_to_id);
}
function bigtouch_theme_search_form($query) {
	return desktop_theme_search_form($query);
}

function bigtouch_theme_avatar($url, $force_large = false) {
	return "<img src='$url' width='48' height='48' />";
}

function bigtouch_theme_page($title, $content) {
	return theme_page($title, $content);
}

function bigtouch_theme_menu_top() {
	return touch_theme_menu_top();
}

function bigtouch_theme_menu_bottom() {
	return '';
}

function bigtouch_theme_status_time_link($status, $is_link = true) {
	return touch_theme_status_time_link($status, $is_link);
}

function bigtouch_theme_css() {
	$out = theme_css();
	$out .= '<link rel="stylesheet" href="browsers/bigtouch.css" />';
	$out .= '<script type="text/javascript">'.file_get_contents('browsers/touch.js').'</script>';
	return $out;
}
?>
