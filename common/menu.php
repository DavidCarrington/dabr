<?php

$menu_registry = array();

function menu_register($items) {
  global $menu_registry;
  foreach ($items as $url => $item) {
    if (!is_array($item))
      $item = array('callback' => $item);
    $menu_registry[$url] = $item;
  }
}

function menu_execute_active_handler() {
  global $menu_registry;
  
  $query = (array) explode('/', $_GET['q']);
  $id = $query[0];
  $page = $menu_registry[$id];
  if (!$page) die('404 - Page not found.');
  
  if (function_exists($page['callback']))
    return call_user_func($page['callback'], $query);

  return false;
}

function theme_menu() {
  global $menu_registry;
  $links = array();
  foreach ($menu_registry as $url => $page)
    if (!$page['hidden']) {
      $title = $url ? $url : 'home';
      $links[] = "<a href='$url'>$title</a>";
    }
  return implode(' | ', $links);
}

?>