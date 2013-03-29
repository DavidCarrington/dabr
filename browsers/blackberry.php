<?php

function blackberry_theme_avatar($url, $force_large = false) {
	return "<img src='$url' width'48' height='48'/>";
}

function blackberry_theme_css() {
	$out = theme_css();
	$out .= '<style type="text/css">.avatar{display:block; height:50px; width:50px; left:5px; margin:0; overflow:hidden; position:absolute;}.shift{margin-left:58px;min-height:48px;}</style>';
	return $out;
}

?>