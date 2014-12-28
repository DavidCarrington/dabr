<?php
$dabr_start = microtime(1);

header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . date('r'));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require 'config.php';
require 'common/browser.php';
require 'common/menu.php';
require 'common/user.php';
require 'common/theme.php';
require 'common/twitter.php';
require 'common/lists.php';
require 'common/settings.php';
require 'common/codebird.php';

// Twitter's API URL.
menu_register(array (
	'about' => array (
		'callback' => 'about_page',
		'display' => 'About'
	),
	'logout' => array (
		'security' => true,
		'callback' => 'logout_page',
		'display' => 'Logout'
	),
	'oauth' => array(
		'callback' => 'user_oauth',
		'hidden' => 'true',
	),
	'login' => array(
		'callback' => 'user_login',
		'hidden' => 'true',
	),
));

function logout_page() {
	user_logout();
	header("Location: " . BASE_URL); /* Redirect browser */
	exit;
}

function about_page() {
	$content = file_get_contents('about.html');
	theme('page', 'About', $content);
}
session_start();
	
browser_detect();
menu_execute_active_handler();

