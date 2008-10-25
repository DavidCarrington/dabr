<?php

$menu_registry = array();

function menu_register($items) {
  global $menu_registry;
  foreach ($items as $url => $item) {
    $menu_registry[$url] = $item;
  }
}

function menu_execute_active_handler() {
  global $menu_registry;
  
  $query = (array) explode('/', $_GET['q']);
  $id = $query[0];
  $page = $menu_registry[$id];
  if (!$page) {
    header("HTTP/1.0 404 Not Found");
    die('404 - Page not found.');
  }
  
  if ($page['security'])
    user_ensure_authenticated();
  
  if (function_exists($page['callback']))
    return call_user_func($page['callback'], $query);

  return false;
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

function theme_menu($menu) {
  $links = array();
  foreach (menu_visible_items() as $url => $page) {
    //~ if ($menu == 'top' && !isset($page['accesskey'])) continue;
    $title = $url ? $url : 'home';
    if ($menu == 'bottom' && isset($page['accesskey'])) {
      $links[] = "<a href='$url' accesskey='{$page['accesskey']}'>$title</a> {$page['accesskey']}";
    } else {
      $links[] = "<a href='$url'>$title</a>";
    }
  }
  return "<div class='menu'><b><a href='user/{$GLOBALS['user']['username']}'>{$GLOBALS['user']['username']}</a></b> | ".implode(' | ', $links).'</div>';
}

?>