<?php

$menu_registry = array();

function menu_register($items) {
	foreach ($items as $url => $item) {
		$GLOBALS['menu_registry'][$url] = $item;
	}
}

function menu_execute_active_handler() {
	$query = (array) explode('/', $_GET['q']);
	$GLOBALS['page'] = $query[0];
	$page = $GLOBALS['menu_registry'][$GLOBALS['page']];
	if (!$page) {
		header('HTTP/1.0 404 Not Found');
		die('404 - Page not found.');
	}

	if ($page['security'])
	user_ensure_authenticated();

	if (function_exists('config_log_request'))
	config_log_request();

	if (function_exists($page['callback']))
	return call_user_func($page['callback'], $query);

	return false;
}

function menu_current_page() {
	return $GLOBALS['page'];
}

function menu_visible_items() {
	static $items;
	if (!isset($items)) {
		$items = array();
		foreach ($GLOBALS['menu_registry'] as $url => $page) {
			if ($page['security'] && !user_is_authenticated()) continue;
			if ($page['hidden']) continue;
			$items[$url] = $page;
		}
	}
	return $items;
}

function theme_menu_top() {
	return theme('menu_both', 'top');
}

function theme_menu_bottom() {
	return theme('menu_both', 'bottom');
}

function theme_menu_both($menu) {
	$links = array();
	foreach (menu_visible_items() as $url => $page) {
		$title = $url ? $url : 'home';
		$title = str_replace("-", " ", $title);
		if (!$url) $url = BASE_URL; // Shouldn't be required, due to <base> element but some browsers are stupid.
		if ($menu == 'bottom' && isset($page['accesskey'])) {
			$links[] = "<a href='$url' accesskey='{$page['accesskey']}'>$title</a> {$page['accesskey']}";
		} else {
			$links[] = "<a href='$url'>$title</a>";
		}
	}
	if (user_is_authenticated()) {
		$user = user_current_username();
		array_unshift($links, "<b><a href='user/$user'>$user</a></b>");
	}
	if ($menu == 'bottom') {
		$links[] = "<a href='{$_GET['q']}' accesskey='5'>refresh</a> 5";
	}
	return "<div class='menu menu-$menu'>".implode(' | ', $links).'</div>';
}

?>