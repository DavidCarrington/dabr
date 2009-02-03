<?php

menu_register(array(
  '' => array(
    'callback' => 'twitter_home_page',
    'accesskey' => '0',
  ),
  'status' => array(
    'hidden' => true,
    'callback' => 'twitter_status_page',
  ),
  'update' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_update',
  ),
  'replies' => array(
    'security' => true,
    'callback' => 'twitter_replies_page',
    'accesskey' => '1',
  ),
  'favourite' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_mark_favourite_page',
  ),
  'unfavourite' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_mark_favourite_page',
  ),
  'directs' => array(
    'security' => true,
    'callback' => 'twitter_directs_page',
    'accesskey' => '2',
  ),
  'search' => array(
    'callback' => 'twitter_search_page',
    'accesskey' => '3',
  ),
  'public' => array(
    'callback' => 'twitter_public_page',
    'accesskey' => '4',
  ),
  'user' => array(
    'hidden' => true,
    'callback' => 'twitter_user_page',
  ),
  'follow' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_follow_page',
  ),
  'unfollow' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_follow_page',
  ),
  'favourites' => array(
    'callback' =>  'twitter_favourites_page',
  ),
  'followers' => array(
    'security' => true,
    'callback' => 'twitter_followers_page',
  ),
  'friends' => array(
    'security' => true,
    'callback' => 'twitter_friends_page',
  ),
  'delete' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_delete_page',
  ),
  'retweet' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_retweet_page',
  ),
  'flickr' => array(
    'hidden' => true,
    'callback' => 'flickr_thumbnail',
  ),
));

function twitter_process($url, $post_data = false) {
  $ch = curl_init($url);

  if($post_data !== false) {
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
  }

  if(user_is_authenticated())
    curl_setopt($ch, CURLOPT_USERPWD, user_current_username().':'.$GLOBALS['user']['password']);

  curl_setopt($ch, CURLOPT_VERBOSE, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_USERAGENT, 'dabr');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $response = curl_exec($ch);
  $response_info=curl_getinfo($ch);
  curl_close($ch);

  switch( intval( $response_info['http_code'] ) ) {
    case 200:
      return json_decode($response);
    case 401:
      user_logout();
      theme('error', '<p>Error: Login credentials incorrect.</p>');
    default:
      $result = json_decode($response);
      $result = $result->error ? $result->error : $response;
      theme('error', "<h2>An error occured while calling the Twitter API</h2><p>{$result}</p><hr><p>$url</p>");
  }
}

function twitter_isgd($text) {
  return preg_replace_callback('#(http://|www)[^ ]{33,1950}\b#', 'twitter_isgd_callback', $text);
}

function twitter_isgd_callback($match) {
  $request = 'http://is.gd/api.php?longurl='.urlencode($match[0]);
  return twitter_fetch($request);
}

function twitter_fetch($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

function twitter_parse_links_callback($matches) {
  $url = $matches[1];
  return theme('external_link', $url);
}

function twitter_parse_tags($input) {
  $out = preg_replace_callback('#([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)(?=\b)#is', 'twitter_parse_links_callback', $input);
  $out = preg_replace('#(^|\s)@([a-z_A-Z0-9]+)#', '$1@<a href="user/$2">$2</a>', $out);
  $out = preg_replace('#(\\#([a-z_A-Z0-9:_-]+))#', '<a href="hash/$2">$0</a>', $out);
  $out = twitter_photo_replace($out);
  return $out;
}

function twitter_photo_replace($text) {
  $tmp = strip_tags($text);
  if (preg_match_all('#twitpic.com/([\d\w]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $match) {
      $text = "<a href='http://twitpic.com/{$match}'><img src='http://twitpic.com/show/thumb/{$match}' class='twitpic' width='75' height='75' /></a><br>".$text;
    }
  }
  if (preg_match_all('#twitxr.com/[^ ]+/updates/([\d]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $key => $match) {
      $thumb = 'http://twitxr.com/thumbnails/'.substr($match, -2).'/'.$match.'_th.jpg';
      $text = "<a href='http://{$matches[0][$key]}'><img src='$thumb' /></a><br>".$text;
    }
  }
  if (FLICKR_API_KEY && preg_match_all('#flickr.com/[^ ]+/([\d]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $key => $match) {
      $text = "<a href='http://{$matches[0][$key]}'><img src='flickr/$match' /></a><br>".$text;
    }
  }
  return $text;
}

function flickr_thumbnail($query) {
  $id = $query[1];
  if ($id) {
    header('HTTP/1.1 301 Moved Permanently') ;
    header('Location: '. flickr_id_to_url($id));
  }
  exit();
}

function flickr_id_to_url($id) {
  if (!$id) return '';
  $url = "http://api.flickr.com/services/rest/?method=flickr.photos.getSizes&photo_id=$id&api_key=".FLICKR_API_KEY;
  $flickr_xml = twitter_fetch($url);
  preg_match('#"(http://.*_s\.jpg)"#', $flickr_xml, $matches);
  return $matches[1];
}

function format_interval($timestamp, $granularity = 2) {
  $units = array(
    'years' => 31536000,
    'days' => 86400,
    'hours' => 3600,
    'min' => 60,
    'sec' => 1
  );
  $output = '';
  foreach ($units as $key => $value) {
    if ($timestamp >= $value) {
      $output .= ($output ? ' ' : '').floor($timestamp / $value).' '.$key;
      $timestamp %= $value;
      $granularity--;
    }
    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : '0 sec';
}

function twitter_status_page($query) {
  $id = (int) $query[1];
  if ($id) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $tl = twitter_process($request, $id);
    $content = theme('status', $tl);
    theme('page', "Status $id", $content);
  }
}

function twitter_retweet_page($query) {
  $id = (int) $query[1];
  if ($id) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $tl = twitter_process($request, $id);
    $content = theme('retweet', $tl);
    theme('page', 'Retweet', $content);
  }
}

function twitter_refresh($page = NULL) {
  if (isset($page)) {
    $page = BASE_URL . $page;
  } else {
    $page = $_SERVER['HTTP_REFERER'];
  }
  header('Location: '. $page);
  exit();
}

function twitter_delete_page($query) {
  $id = (int) $query[1];
  if ($id) {
    $request = "http://twitter.com/statuses/destroy/{$id}.json?page=".intval($_GET['page']);
    $tl = twitter_process($request, 1);
    twitter_refresh('user/'.user_current_username());
  }
}

function twitter_follow_page($query) {
  $user = $query[1];
  if ($user) {
    if($query[0] == 'follow'){
      $request = "http://twitter.com/friendships/create/{$user}.json";
    } else {
      $request = "http://twitter.com/friendships/destroy/{$user}.json";
    }
    twitter_process($request, 1);
    twitter_refresh('friends');
  }
}

function twitter_friends_page($query) {
  $user = $query[1];
  if (!$user) {
    user_ensure_authenticated();
    $user = user_current_username();
  }
  $request = "http://twitter.com/statuses/friends/{$user}.json?page=".intval($_GET['page']);
  $tl = twitter_process($request);
  $content = theme('followers', $tl);
  theme('page', 'Friends', $content);
}

function twitter_followers_page($query) {
  $user = $query[1];
  if (!$user) {
    user_ensure_authenticated();
    $user = user_current_username();
  }
  $request = "http://twitter.com/statuses/followers/{$user}.json?page=".intval($_GET['page']);
  $tl = twitter_process($request);
  $content = theme('followers', $tl);
  theme('page', 'Followers', $content);
}

function twitter_update() {
  $status = twitter_isgd(stripslashes(trim($_POST['status'])));
  if ($status) {
    $request = 'http://twitter.com/statuses/update.json';
    $post_data = 'source=dabr&status='.urlencode($status);
    $in_reply_to_id = (int) $_POST['in_reply_to_id'];
    if ($in_reply_to_id > 0) {
      $post_data .= "&in_reply_to_status_id={$in_reply_to_id}";
    }
    $b = twitter_process($request, $post_data);
  }
  twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_public_page() {
  $request = 'http://twitter.com/statuses/public_timeline.json?page='.intval($_GET['page']);
  $content = theme('status_form');
  $tl = twitter_standard_timeline(twitter_process($request), 'public');
  $content .= theme('timeline', $tl);
  theme('page', 'Public Timeline', $content);
}

function twitter_replies_page() {
  $request = 'http://twitter.com/statuses/replies.json?page='.intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'replies');
  $tl += twitter_search('@'.user_current_username());
  krsort($tl);
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  theme('page', 'Replies', $content);
}

function twitter_directs_page($query) {
  $action = strtolower(trim($query[1]));
  switch ($action) {
    case 'create':
      $to = $query[2];
      $content = theme('directs_form', $to);
      theme('page', 'Create DM', $content);
    
    case 'send':
      $to = urlencode(trim(stripslashes($_POST['to'])));
      $message = urlencode(trim(stripslashes($_POST['message'])));
      $request = 'http://twitter.com/direct_messages/new.json';
      twitter_process($request, "user=$to&text=$message");
      twitter_refresh('directs/sent');
    
    case 'sent':
      $request = 'http://twitter.com/direct_messages/sent.json?page='.intval($_GET['page']);
      $tl = twitter_standard_timeline(twitter_process($request), 'directs_sent');
      $content = theme_directs_menu();
      $content .= theme('timeline', $tl);
      theme('page', 'DM Sent', $content);

    case 'inbox':
    default:
      $request = 'http://twitter.com/direct_messages.json?page='.intval($_GET['page']);
      $tl = twitter_standard_timeline(twitter_process($request), 'directs_inbox');
      $content = theme_directs_menu();
      $content .= theme('timeline', $tl);
      theme('page', 'DM Inbox', $content);
  }
}

function theme_directs_menu() {
  return '<p><a href="directs/create">Create</a> | <a href="directs/inbox">Inbox</a> | <a href="directs/sent">Sent</a></p>';
}

function theme_directs_form($to) {
  if ($to) {
    $html_to = "Sending direct message to <b>$to</b><input name='to' value='$to' type='hidden'>";
  } else {
    $html_to = "To: <input name='to'><br>Message:";
  }
  $content = "<form action='directs/send' method='post'>$html_to<br><textarea name='message' style='width: 100%' rows='3'></textarea><br><input type='submit' value='Send'></form>";
  return $content;
}

function twitter_search_page() {
  $search_query = $_GET['query'];
  $content = theme('search_form', $search_query);
  if (isset($_POST['query'])) {
    $duration = time() + (3600 * 24 * 365);
    setcookie('search_favourite', $_POST['query'], $duration, '/');
    twitter_refresh('search');
  }
  if (!isset($search_query) && array_key_exists('search_favourite', $_COOKIE)) {
    $search_query = $_COOKIE['search_favourite'];
  }
  if ($search_query) {
    $tl = twitter_search($search_query);
    if ($search_query !== $_COOKIE['search_favourite']) {
      $content .= '<form action="search/bookmark" method="post"><input type="hidden" name="query" value="'.$search_query.'" /><input type="submit" value="Save as default search" /></form>';
    }
    $content .= theme('timeline', $tl);
  }
  theme('page', 'Search', $content);
}

function twitter_search($search_query) {
  $page = (int) $_GET['page'];
  if ($page == 0) $page = 1;
  $request = 'http://search.twitter.com/search.json?q=' . urlencode($search_query).'&page='.$page;
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'search');
  return $tl;
}

function twitter_user_page($query) {
  $screen_name = $query[1];
  if ($screen_name) {
    $content = '';
    if ($query[2] == 'reply') {
      $in_reply_to_id = (int) $query[3];
      $content .= "<p>In reply to tweet ID $in_reply_to_id...</p>";
    } else {
      $in_reply_to_id = 0;
    }
    $user = twitter_user_info($screen_name);
    $content .= theme('status_form', "@{$user->screen_name} ", $in_reply_to_id);
    $content .= theme('user_header', $user);
    
    if (isset($user->status)) {
      $request = "http://twitter.com/statuses/user_timeline/{$screen_name}.json?page=".intval($_GET['page']);
      $tl = twitter_process($request);
      $tl = twitter_standard_timeline($tl, 'user');
      $content .= theme('timeline', $tl);
    }
    theme('page', "User {$screen_name}", $content);
  } else {
    // TODO: user search screen
  }
}

function twitter_favourites_page($query) {
  $screen_name = $query[1];
  if (!$screen_name) {
    user_ensure_authenticated();
    $screen_name = user_current_username();
  }
  $request = "http://twitter.com/favorites/{$screen_name}.json?page=".intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'favourites');
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  theme('page', 'Favourites', $content);
}

function twitter_mark_favourite_page($query) {
  $id = (int) $query[1];
  if ($query[0] == 'unfavourite') {
    $request = "http://twitter.com/favorites/destroy/$id.json";
  } else {
    $request = "http://twitter.com/favorites/create/$id.json";
  }
  twitter_process($request, 1);
  twitter_refresh();
}

function twitter_home_page() {
  user_ensure_authenticated();
  $request = 'http://twitter.com/statuses/friends_timeline.json?page='.intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'friends');
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  theme('page', 'Home', $content);
}

function theme_status_form($text = '', $in_reply_to_id = NULL) {
  if (user_is_authenticated()) {
    return "<form method='POST' action='update'><input name='status' value='{$text}' maxlength='140' /> <input name='in_reply_to_id' value='{$in_reply_to_id}' type='hidden' /><input type='submit' value='Update' /></form>";
  }
}

function theme_status($status) {
  $time_since = theme('status_time_link', $status);
  $parsed = twitter_parse_tags($status->text);
  $avatar = theme('avatar', $status->user->profile_image_url, 1);

  $out = theme('status_form', "@{$status->user->screen_name} ");
  $out .= "<p>$parsed</p>
<table align='center'><tr><td>$avatar</td><td><a href='user/{$status->user->screen_name}'>{$status->user->screen_name}</a>
<br>$time_since</table>";
  if (strtolower(user_current_username()) == strtolower($status->user->screen_name)) {
    $out .= "<form action='delete/{$status->id}' method='post'><input type='submit' value='Delete without confirmation' /></form>";
  }
  return $out;
}

function theme_retweet($status) {
  $text = "RT @{$status->user->screen_name}: {$status->text}";
  $length = strlen($text);
  $from = substr($_SERVER['HTTP_REFERER'], strlen(BASE_URL));
  $content = "<form action='update' method='post'><input type='hidden' name='from' value='$from' /><textarea name='status' cols='30' rows='5'>$text</textarea><br><input type='submit' value='Retweet'> Length before editing: $length</form>";
  return $content;
}

function theme_user_header($user) {
  $name = theme('full_name', $user);
  $out = "<table><tr><td>".theme('avatar', $user->profile_image_url, 1)."</td>
<td><b>{$name}</b>
<small>
<br>Bio: {$user->description}
<br>Link: <a href='{$user->url}'>{$user->url}</a>
<br>Location: {$user->location}
</small>
<br><a href='followers/{$user->screen_name}'>{$user->followers_count} followers</a>
| <a href='follow/{$user->screen_name}'>Follow</a>
| <a href='unfollow/{$user->screen_name}'>Unfollow</a>
| <a href='friends/{$user->screen_name}'>{$user->friends_count} friends</a>
| <a href='favourites/{$user->screen_name}'>Favourites</a>
| <a href='directs/create/{$user->screen_name}'>Direct Message</a>
</td></table>";
  return $out;
}

function theme_avatar($url, $force_large = false) {
  $size = $force_large ? 48 : 24;
  return "<img src='$url' height='$size' width='$size' />";
}

function theme_status_time_link($status, $is_link = true) {
  $time = strtotime($status->created_at);
  if (twitter_date('dmy') == twitter_date('dmy', $time)) {
    $out = format_interval(time() - $time, 1). ' ago';
  } else {
    $out = twitter_date('H:i', $time);
  }
  if ($is_link)
    $out = "<a href='status/{$status->id}'>$out</a>";
  return "<small>$out</small>";
}

function twitter_date($format, $timestamp = null) {
  static $offset;
  if (!isset($offset)) {
    if (user_is_authenticated()) {
      if (array_key_exists('utc_offset', $_COOKIE)) {
        $offset = $_COOKIE['utc_offset'];
      } else {
        $user = twitter_user_info();
        $offset = $user->utc_offset;
        setcookie('utc_offset', $offset, time() + 3000000);
      }
    } else {
      $offset = 0;
    }
  }
  if (!isset($timestamp)) {
    $timestamp = time();
  }
  return gmdate($format, $timestamp + $offset);
}

function twitter_standard_timeline($feed, $source) {
  $output = array();
  switch ($source) {
    case 'favourites':
    case 'friends':
    case 'public':
    case 'replies':
    case 'user':
      foreach ($feed as $status) {
        $new = $status;
        $new->from = $new->user;
        unset($new->user);
        $output[$new->id] = $new;
      }
      return $output;
    
    case 'search':
      foreach ($feed->results as $status) {
        $output[$status->id] = (object) array(
          'id' => $status->id,
          'text' => $status->text,
          'from' => (object) array(
            'id' => $status->from_user_id,
            'screen_name' => $status->from_user,
            'profile_image_url' => $status->profile_image_url,
          ),
          'to' => (object) array(
            'id' => $status->to_user_id,
            'screen_name' => $status->to_user,
          ),
          'created_at' => $status->created_at,
        );
      }
      return $output;
    
    case 'directs_sent':
    case 'directs_inbox':
      foreach ($feed as $status) {
        $new = $status;
        if ($source == 'directs_inbox') {
          $new->from = $new->sender;
          $new->to = $new->recipient;
        } else {
          $new->from = $new->recipient;
          $new->to = $new->sender;
        }
        unset($new->sender, $new->recipient);
        $new->is_direct = true;
        $output[] = $new;
      }
      return $output;

    default:
      echo "<h1>$source</h1><pre>";
      print_r($feed); die();
  }
}

function twitter_user_info($username = null) {
  if (!$username)
  $username = user_current_username();  
  $request = "http://twitter.com/users/show/$username.json";
  $user = twitter_process($request);
  return $user;
}

function theme_timeline($feed) {
  if (count($feed) == 0) return theme('no_tweets');
  $rows = array();
  $page = menu_current_page();
  $date_heading = false;
  foreach ($feed as $status) {
    $date = twitter_date('l jS F Y', strtotime($status->created_at));
    if ($date_heading !== $date) {
      $date_heading = $date;
      $rows[] = array(array(
        'data' => "<small><b>$date</b></small>",
        'colspan' => 2
      ));
    }
    $text = twitter_parse_tags($status->text);
    $link = theme('status_time_link', $status, !$status->is_direct);
    $actions = theme('action_icons', $status);
    $avatar = theme('avatar', $status->from->profile_image_url);
    $source = $status->source ? " from {$status->source}" : '';
    if ($status->in_reply_to_status_id) {
      $source .= " in reply to <a href='status/{$status->in_reply_to_status_id}'>{$status->in_reply_to_screen_name}</a>";
    }
    $row = array(
      "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link<br>{$text} <small>$source</small>",
    );
    if ($page != 'user' && $avatar) {
      array_unshift($row, $avatar);
    }
    if ($page != 'replies' && twitter_is_reply($status)) {
      $row = array('class' => 'reply', 'data' => $row);
    }
    $rows[] = $row;
  }
  $content = theme('table', array(), $rows, array('class' => 'timeline'));
  $content .= theme('pagination');
  return $content;
}

function twitter_is_reply($status) {
  if (!user_is_authenticated()) {
    return false;
  }
  $user = user_current_username();
  return preg_match("#@$user#", $status->text);
}

function theme_followers($feed) {
  $rows = array();
  if (count($feed) == 0) return '<p>No users to display.</p>';
  foreach ($feed as $user) {
    $name = theme('full_name', $user);
    $rows[] = array(
      theme('avatar', $user->profile_image_url),
      "{$name} - {$user->location}<small><br>{$user->description}</small>",
    );
  }
  $content = theme('table', array(), $rows, array('class' => 'followers'));
  $content .= theme('pagination');
  return $content;
}

function theme_full_name($user) {
  $name = "<a href='user/{$user->screen_name}'>{$user->screen_name}</a>";
  if ($user->name && $user->name != $user->screen_name) {
    $name .= " ({$user->name})";
  }
  return $name;
}

function theme_no_tweets() {
  return '<p>No tweets to display.</p>';
}

function theme_search_results($feed) {
  $rows = array();
  foreach ($feed->results as $status) {
    $text = twitter_parse_tags($status->text);
    $link = theme('status_time_link', $status);
    $actions = theme('action_icons', $status);

    $row = array(
      theme('avatar', $status->profile_image_url),
      "<a href='user/{$status->from_user}'>{$status->from_user}</a> $actions - {$link}<br>{$text}",
    );
    if (twitter_is_reply($status)) {
      $row = array('class' => 'reply', 'data' => $row);
    }
    $rows[] = $row;
  }
  $content = theme('table', array(), $rows, array('class' => 'timeline'));
  $content .= theme('pagination');
  return $content;
}

function theme_search_form($query) {
  $query = stripslashes(htmlentities($query));
  return "<form action='search' method='GET'><input name='query' value=\"$query\" /><input type='submit' value='Search' /></form>";
}

function theme_external_link($url) {
  if (substr($url, 0, strlen(BASE_URL)) == BASE_URL) return "<a href='$url'>$url</a>";
  $encoded = urlencode($url);
  return "<a href='http://google.com/gwt/n?u={$encoded}'>{$url}</a>";
}

function theme_pagination() {
  $page = intval($_GET['page']);
  if (preg_match('#&q(.*)#', $_SERVER['QUERY_STRING'], $matches)) {
    $query = $matches[0];
  }
  if ($page == 0) $page = 1;
  if ($page > 1) $links[] = "<a href='{$_GET['q']}?page=".($page-1)."$query' accesskey='8'>Newer</a> 8";
  $links[] = "<a href='{$_GET['q']}?page=".($page+1)."$query' accesskey='9'>Older</a> 9";
  return '<p>'.implode(' | ', $links).'</p>';
}

function theme_action_icons($status) {
  $user = $status->from->screen_name;
  $actions = array();
  
  $actions[] = "<a href='user/{$user}/reply/{$status->id}'><img src='images/reply.png' /></a>";
  if ($status->user->screen_name != user_current_username()) {
    $actions[] = "<a href='directs/create/{$user}'><img src='images/dm.png' /></a>";
  }
  if (!$status->is_direct) {
    if ($status->favorited == '1') {
      $actions[] = "<a href='unfavourite/{$status->id}'><img src='images/star.png' /></a>";
    } else {
      $actions[] = "<a href='favourite/{$status->id}'><img src='images/star_grey.png' /></a>";
    }
    $actions[] = "<a href='retweet/{$status->id}'><img src='images/retweet.png' /></a>";
  }
  return implode(' ', $actions);
}

?>
