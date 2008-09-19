<?php

if (file_exists('offline.html')) { readfile('offline.html'); exit(); }

define('DABR_VERSION', '0.3');

include 'config.php';
include 'common/authentication.php';
include 'common/theme.php';
include 'common/twitter.php';

ensure_authenticated();

$t = new DabrTwitterClient();
$query = explode('/', $_GET['q']);

switch ($query[0]) {
  case 'logout':
    $user->logout();
    $content = theme('logged_out');
    theme('page', 'Logged out', $content);

  case 'user':
    $screen_name = $query[1];
    if ($screen_name) {
      $tl = $t->user_timeline($screen_name);
      $content = theme('user', $tl);
      theme('page', 'User', $content);
    } else {
      // TODO: user search screen
    }

  case 'update':
    $status = stripslashes(trim($_POST['status']));
    if ($status) {
      $b = $t->update($status);
    }
    header('Location: '. BASE_URL);
    exit();
  
  case 'replies':
    $content = theme('status_form');
    $content .= theme('timeline', $t->replies_timeline());
    theme('page', 'Replies', $content);
  
  case 'directs':
    $content = theme('status_form');
    $content .= theme('timeline', $t->direct_messages());
    theme('page', 'Direct Messages', $content);
    
  
  case 'public':
    $content = theme('status_form');
    $content .= theme('timeline', $t->public_timeline());
    theme('page', 'Public Timeline', $content);
  
  case 'about':
    $content = file_get_contents('about.html');
    theme('page', 'About', $content);
  
  case 'destroy':
    $status_id = (int) $query[1];
    if ($status_id) {
      $t->destroy($status_id);
    }
    header('Location: '. BASE_URL);
    exit();
  
  case 'status':
    $id = (int) $query[1];
    if ($id) {
      $tl = $t->show($id);
      $content = theme('status', $tl);
      theme('page', "Status $id", $content);
    }

  case 'home':
  default:
    $content = theme('status_form');
    $content .= theme('timeline', $t->friends_timeline());
    theme('page', 'Home', $content);
    
}

?>