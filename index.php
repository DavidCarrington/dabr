<?php

if (file_exists('offline.html')) { readfile('offline.html'); exit(); }

include 'config.php';
include 'common/authentication.php';
include 'common/menu.php';
include 'common/theme.php';
include 'common/twitter.php';

ensure_authenticated();

menu_register(array(
  '' => array(
    'callback' => 'home_page',
  ),
  'about' => array(
    'callback' => 'about_page',
  ),
  'logout' => array(
    'callback' => 'logout_page',
  ),
));

menu_register(array(
  '' => array(
    'callback' => 'twitter_friends_page',
  ),
  'status' => array(
    'hidden' => true,
    'callback' => 'twitter_status_page',
  ),
  'update' => array(
    'hidden' => true,
    'callback' => 'twitter_update',
  ),
  'public' => array(
    'callback' => 'twitter_public_page',
  ),
  'replies' => array(
    'callback' => 'twitter_replies_page',
  ),
  'directs' => array(
    'callback' => 'twitter_directs_page',
  ),
  'search' => array(
    'callback' => 'twitter_search_page',
  ),
  'user' => array(
    'hidden' => true,
    'callback' => 'twitter_user_page',
  ),
  'follow' => array(
    'hidden' => true,
    'callback' => 'twitter_follow_page',
  ),
  'unfollow' => array(
    'hidden' => true,
    'callback' => 'twitter_follow_page',
  ),
  'delete' => array(
    'hidden' => true,
    'callback' => 'twitter_delete_page',
  ),
));

function twitter_status_page($query) {
  $t = new DabrTwitterClient();
  $id = (int) $query[1];
  if ($id) {
    $tl = $t->show($id);
    $content = theme('status', $tl);
    theme('page', "Status $id", $content);
  }
}

function twitter_delete_page($query) {
  $t = new DabrTwitterClient();
  $id = (int) $query[1];
  if ($id) {
    $tl = $t->destroy($id);
    header('Location: '. BASE_URL);
    exit();
  }
}

function twitter_follow_page($query) {
  $t = new DabrTwitterClient();
  $user = $query[1];
  if ($user) {
    if($query[0] == 'follow'){
      $t->follow_user($user);
    } else {
      $t->leave_user($user);
    }
    header('Location: '. BASE_URL);
    exit();
  }
}

function twitter_update() {
  $t = new DabrTwitterClient();
  $status = stripslashes(trim($_POST['status']));
  if ($status) {
    $b = $t->update($status);
  }
  header('Location: '. BASE_URL);
  exit();
}

function twitter_public_page() {
  $t = new DabrTwitterClient();
  $content = theme('status_form');
  $content .= theme('timeline', $t->public_timeline());
  theme('page', 'Public Timeline', $content);
}

function twitter_replies_page() {
  $t = new DabrTwitterClient();
  $content = theme('status_form');
  $content .= theme('timeline', $t->replies_timeline());
  theme('page', 'Replies', $content);
}

function twitter_directs_page() {
  $t = new DabrTwitterClient();
  $content = theme('status_form');
  $content .= theme('directs', $t->direct_messages());
  theme('page', 'Direct Messages', $content);
}

function twitter_search_page() {
  $search_query = $_GET['query'];
  $content = theme('search_form');
  if ($search_query) {
    $t = new DabrTwitterClient();
    $tl = $t->search($search_query);
    $content .= theme('search_results', $tl);
  }
  theme('page', 'Search', $content);
}

function twitter_user_page($query) {
  $screen_name = $query[1];
  if ($screen_name) {
    $t = new DabrTwitterClient();
    $tl = $t->user_timeline($screen_name);
    $content = theme('user', $tl);
    theme('page', 'User', $content);
  } else {
    // TODO: user search screen
  }
}

function twitter_friends_page() {
  $t = new DabrTwitterClient();
  $content = theme('status_form');
  $content .= theme('timeline', $t->friends_timeline());
  theme('page', 'Home', $content);
}

function logout_page() {
  global $user;
  $user->logout();
  $content = theme('logged_out');
  theme('page', 'Logged out', $content);
}

function home_page() {
  $content = '<p>This is some example content.</p>';
  theme_page('Home', $content);
}

function about_page() {
  $content = file_get_contents('about.html');
  theme_page('About', $content);
}

menu_execute_active_handler();