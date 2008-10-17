<?php

header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); 
header( 'Last-Modified: ' . date('r') ); 
header( 'Cache-Control: no-store, no-cache, must-revalidate' ); 
header( 'Cache-Control: post-check=0, pre-check=0', false ); 
header( 'Pragma: no-cache' );

if (file_exists('offline.html')) { readfile('offline.html'); exit(); }

include 'config.php';
include 'common/browser.php';
include 'common/user.php';
include 'common/menu.php';
include 'common/theme.php';
include 'common/twitter.php';

menu_register(array(
  'about' => array(
    'callback' => 'about_page',
  ),
  'logout' => array(
    'security' => true,
    'callback' => 'logout_page',
  ),
));

function logout_page() {
  user_logout();
  $content = theme('logged_out');
  theme('page', 'Logged out', $content);
}

function about_page() {
  $content = file_get_contents('about.html');
  theme('page', 'About', $content);
}

browser_detect();
menu_execute_active_handler();

?>
