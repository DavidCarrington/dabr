<?php

function browser_detect() {
  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  $handle = fopen('browsers/list.csv', 'r');
  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    if (preg_match("#{$data[0]}#", $user_agent, $matches)) {
      browser_load($data[1]);
      break;
    }
  }
  fclose($handle);
}

function browser_load($browser) {
  $GLOBALS['current_theme'] = $browser;
  $file = "browsers/$browser.php";
  if (file_exists($file)) {
    include($file);
  }
}

?>