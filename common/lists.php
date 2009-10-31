<?php

menu_register(array(
  'lists' => array(
    'security' => true,
    'callback' => 'lists_page',
  ),
));

function twitter_lists($username, $list = null) {
  if ($list) {
    $url = "http://twitter.com/$username/lists/$list/statuses.json";
  } else {
    $url = "http://twitter.com/$username/lists.json";
  }
  return twitter_process($url);
}

function twitter_memberships($username) {
  $url = "http://twitter.com/{$username}/lists/memberships.json";
  return twitter_process($url);
}

function twitter_list_members($username, $list) {
  $url = "http://twitter.com/{$username}/{$list}/members.json";
  return twitter_process($url);
}

function lists_page($query) {
  $username = $query[1];
  $list = $query[2];
  $page = $query[3];
  
  if (!$username) $username = user_current_username();

  if ($list) {
    if ($page) {
      $p = twitter_list_members($username, $list);
      $content = theme('followers', $p->users);
    } else {
      $list = twitter_lists($username, $list);
      $content = theme('t_list', $list);
    }
  } else {
    // Display list of lists
    $lists = twitter_lists($username);
    $content = theme('lists', $lists);
    if ($content) {
      $content = "<h3><strong>$username</strong>'s lists:</h3>".$content;
    }
    if (!$content) {
      if (user_is_current_user($username)) {
        $content .= "<h3>What the hell are lists?!</h3><p>'Lists' are a brand new feature from Twitter to help you organise the people you follow. If you can see this then you haven't made any yet.</p><p>Check out <a href='http://twitter.com'>Twitter.com</a> from your desktop to see if you can create some.</p><p>Eventually we'll let you add your own right here in Dabr!</p>";
      } else {
        $content = "<p><strong>$username</strong> has not made any lists yet.</p>";
      }
    }
    $memberships = twitter_memberships($username);
    
    $mem = theme('lists', $memberships);
    if ($mem) {
      $count = count($memberships->lists);
      $content .= "<h3>Lists <strong>$username</strong> appears in:</h3>";
      $content .= $mem;
    }
  }
  return theme('page', 'Lists', $content);
}

function theme_lists($json) {
  if (count($json->lists) == 0) {
    return false;
  }
  $rows = array();
  $headers = array('List', 'Members', 'Subscribers');
  foreach ($json->lists as $list) {
    // print_R($list); die();
    $url = "lists/{$list->user->screen_name}/{$list->slug}";
    $rows[] = array(
      "<a href='{$url}'>{$list->full_name}</a>",
      "<a href='{$url}/members'>{$list->member_count}</a>",
      (int) $list->subscriber_count,
    );
  }
  return theme('table', $headers, $rows);
}

function theme_t_list($json) {
  $tl = twitter_standard_timeline($json, 'public');
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  return $content;
}
