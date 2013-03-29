<?php

require 'desktop.php';
require_once ("common/advert.php");

function touch_theme_action_icon($url, $image_url, $text) {
	if ($text == 'MAP')	{
		return "<a href='$url' target='" . get_target() . "'><img src='$image_url' alt='$text' width='12' height='12' /></a>";
	}
	else if ($text == 'DM')	{
		return "<a href='$url'><img src='$image_url' alt='$text' width='16' height='11' /></a>";
	}
	else	{
		return "<a href='$url'><img src='$image_url' alt='$text' width='12' height='12' /></a>";
	}
}


function touch_theme_status_form($text = '', $in_reply_to_id = NULL) {
	return desktop_theme_status_form($text, $in_reply_to_id);
}
function touch_theme_search_form($query) {
	return desktop_theme_search_form($query);
}

function touch_theme_avatar($url, $force_large = false) {
	return "<img src='$url' width='48' height='48' />";
}

function touch_theme_menu_top() {
	$links = array();
	$main_menu_titles = array('home', 'replies', 'directs', 'search');
	foreach (menu_visible_items() as $url => $page) {
		$title = $url ? $url : 'home';
		$title = str_replace("-", " ", $title);
		$type = in_array($title, $main_menu_titles) ? 'main' : 'extras';
		$links[$type][] = "<a href='$url'>$title</a>";
	}
	if (user_is_authenticated()) {
		$user = user_current_username();
		array_unshift($links['extras'], "<b><a href='user/$user'>$user</a></b>");
	}
	array_push($links['main'], '<a href="#" onclick="return toggleMenu()">+</a>');
	$html = '<div id="menu" class="menu">';
	$html .= theme('list', $links['main'], array('id' => 'menu-main'));
	$html .= theme('list', $links['extras'], array('id' => 'menu-extras'));
	$html .= '</div>';
	return $html;
}

function touch_theme_menu_bottom() {
	return '';
}

function touch_theme_status_time_link($status, $is_link = true) {
	$out = theme_status_time_link($status, $is_link);
	//old method didn't work with conversation view (and no longer with correct pluralisation)
	$out = str_replace(array(' years ago', ' year ago', ' days ago', ' day ago', ' hours ago', ' hour ago', ' mins ago', ' min ago', ' secs ago', ' sec ago'),
							array('y', 'y', 'd', 'd', 'h', 'h', 'm', 'm', 's', 's'), $out);
	return $out;
}

function touch_theme_css() {
	$out = theme_css();
	$out .= '<link rel="stylesheet" href="browsers/touch.css" />';
	$out .= '<script type="text/javascript">'.file_get_contents('browsers/touch.js').'</script>';
	return $out;
}
?>
