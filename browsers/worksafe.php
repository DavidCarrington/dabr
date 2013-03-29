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
