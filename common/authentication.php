<?php

require 'class.user.php';
$user = new User();

function ensure_authenticated() {
  global $user;
  
  // Show login if not already logged in
  if (!$user->username || !$user->password) {
    if ($_POST['username'] && $_POST['password']) {
      $user->username = $_POST['username'];
      $user->password = $_POST['password'];
      $user->save($_POST['stay-logged-in'] == 'yes');
      header('Location: '. BASE_URL);
    } else {
      $content = theme('login');
      theme('page', 'Login', $content);
    }
  }
}

function theme_login() {
  return '<p><font size=5><b>dabr.co.uk</b></font><br><small>a Windows Mobile-optimised Twitter interface.</small></p>
<hr>
<form method="post" action="'.$_GET['q'].'">Username <input name="username" size="15"><br>Password <input name="password" type="password" size="15"><br><label><input type="checkbox" value="yes" name="stay-logged-in"> Stay logged in? </label><br><input type="submit" value="Log in"></form>
<hr><p><small>Security/privacy: dabr does not store your password on the site, that\'s stored as an encrypted cookie on your machine.</small></p>
';
}

function theme_logged_out() {
  return '<p>Logged out. <a href="">Login again</a></p>';
}

?>