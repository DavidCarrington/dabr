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
    'security' => true,
    'callback' => 'twitter_search_page',
    'accesskey' => '3',
  ),
  'public' => array(
    'security' => true,
    'callback' => 'twitter_public_page',
    'accesskey' => '4',
  ),
  'user' => array(
    'hidden' => true,
    'security' => true,
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
  'confirm' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_confirmation_page',
  ),
  'block' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_block_page',
  ),
  'unblock' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_block_page',
  ),
  'favourites' => array(
    'security' => true,
    'callback' =>  'twitter_favourites_page',
  ),
  'followers' => array(
    'security' => true,
    'callback' => 'twitter_followers_page',
  ),
  'friends' => array(
    'security' => true,
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
    'security' => true,
    'hidden' => true,
    'callback' => 'generate_thumbnail',
  ),
  'mobypicture' => array(
    'security' => true,
    'hidden' => true,
    'callback' => 'generate_thumbnail',
  ),
  'moblog' => array(
    'security' => true,
    'hidden' => true,
    'callback' => 'generate_thumbnail',
  ),
  'hash' => array(
    'security' => true,
    'hidden' => true,
    'callback' => 'twitter_hashtag_page',
  ),
  'twitpic' => array(
    'security' => true,
    'callback' => 'twitter_twitpic_page',
  ),
    'trends' => array(
    'security' => true,
    'callback' => 'twitter_trends_page',
  ),
));

function friendship_exists($user_a) {
	//This function shows if _a is following the authenticated user
	//Currently broken with OAuth
	if (user_type() != 'oauth') {
		$request = 'http://twitter.com/friendships/show.json?target_screen_name=' . $user_a;
		$following = twitter_process($request);
		
		if ($following->relationship->target->following == 1) {
			return true;
		} else {
			return false;
		}
	}
	
	//As OAuth isn't working properly, we'll assume the friendship does exist.
	return true;
}

function twitter_block_exists($query) 
{
	//http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-blocks-blocking-ids
	//Get an array of all ids the authenticated user is blocking
	$request = 'http://twitter.com/blocks/blocking/ids.json';
	$blocked = twitter_process($request);
	
	//bool in_array  ( mixed $needle  , array $haystack  [, bool $strict  ] )		
	//If the authenticate user has blocked $query it will appear in the array
	return in_array($query,$blocked);
}

function twitter_trends_page($query) 
{
  $trend_type = $query[1];
  if($trend_type == '') $trend_type = 'current';
  $request = 'http://search.twitter.com/trends/' . $trend_type . '.json';
  $trends = twitter_process($request);
  $search_url = 'search?query=';
  foreach($trends->trends as $temp) {
    foreach($temp as $trend) {
      $row = array('<strong><a href="' . $search_url . urlencode($trend->query) . '">' . $trend->name . '</a></strong>');
      $rows[] = $row;
    }
  }
  //$headers = array('<p><a href="trends">Current</a> | <a href="trends/daily">Daily</a> | <a href="trends/weekly">Weekly</a></p>'); //output for daily and weekly not great at the moment
  $headers = array();
  $content = theme('table', $headers, $rows, array('class' => 'timeline'));
  theme('page', 'Trends', $content);
}

function js_counter($name)
{
	$script = '<script type="text/javascript">
	function updateCount() 
	{
		document.getElementById("remaining").innerHTML = 140 - document.getElementById("' . $name . '").value.length;
		setTimeout(updateCount, 400);
	}

	function confirmShortTweet() 
	{
		var len = document.getElementById("' . $name . '").value.length;
		if (len < 30) return confirm("That\'s a short tweet.\nContinue?");
		return true;
	}
	updateCount();
</script>';
	return $script;
}

function twitter_twitpic_page($query) {
  if (user_type() == 'oauth') {
    return theme('page', 'Error', '<p>You can\'t use Twitpic uploads while accessing Dabr using an OAuth login.</p>');
  }
  if ($_POST['message']) {
    $response = twitter_process('http://twitpic.com/api/uploadAndPost', array(
      'media' => '@'.$_FILES['media']['tmp_name'],
      'message' => stripslashes($_POST['message']),
      'username' => user_current_username(),
      'password' => $GLOBALS['user']['password'],
    ));
    if (preg_match('#mediaid>(.*)</mediaid#', $response, $matches)) {
      $id = $matches[1];
      twitter_refresh("twitpic/confirm/$id");
    } else {
      twitter_refresh('twitpic/fail');
    }
  } elseif ($query[1] == 'confirm') {
    $content = "<p>Upload success.</p><p><img src='http://twitpic.com/show/thumb/{$query[2]}' alt='' /></p>";
  } elseif ($query[1] == 'fail') {
    $content = '<p>Twitpic upload failed. No idea why!</p>';
  } else {
    $content = '<form method="post" action="twitpic" enctype="multipart/form-data">Image <input type="file" name="media" /><br />Message: <input type="text" name="message" maxlength="120" /><br /><input type="submit" value="Upload" /></form>';
  }
  return theme('page', 'Twitpic Upload', $content);
}

function twitter_process($url, $post_data = false) {
  if ($post_data === true) $post_data = array();
  if (user_type() == 'oauth' && strpos($url, '/twitter.com') !== false) {
    user_oauth_sign($url, $post_data);
  } elseif (strpos($url, 'twitter.com') !== false && is_array($post_data)) {
    // Passing $post_data as an array to twitter.com (non-oauth) causes an error :(
    $s = array();
    foreach ($post_data as $name => $value)
      $s[] = $name.'='.urlencode($value);
    $post_data = implode('&', $s);
  }
  
  $ch = curl_init($url);

  if($post_data !== false && !$_GET['page']) {
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
  }

  if (user_type() != 'oauth' && user_is_authenticated())
    curl_setopt($ch, CURLOPT_USERPWD, user_current_username().':'.$GLOBALS['user']['password']);

  curl_setopt($ch, CURLOPT_VERBOSE, 0);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_USERAGENT, 'dabr');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

  $response = curl_exec($ch);
  $response_info=curl_getinfo($ch);
  curl_close($ch);

  switch( intval( $response_info['http_code'] ) ) {
    case 200:
      $json = json_decode($response);
      if ($json) return $json;
      return $response;
    case 401:
      user_logout();
      theme('error', '<p>Error: Login credentials incorrect.</p>');
    default:
      $result = json_decode($response);
      $result = $result->error ? $result->error : $response;
      if (strlen($result) > 500) $result = 'Something broke.';
      theme('error', "<h2>An error occured while calling the Twitter API</h2><p>{$response_info['http_code']}: {$result}</p><hr><p>$url</p>");
  }
}

function twitter_url_shorten($text) {
  return preg_replace_callback('#((\w+://|www)[\w\#$%&~/.\-;:=,?@\[\]+]{33,1950})(?<![.,])#is', 'twitter_url_shorten_callback', $text);
}

function twitter_url_shorten_callback($match) {
  if (preg_match('#http://www.flickr.com/photos/[^/]+/(\d+)/#', $match[0], $matches)) {
    return 'http://flic.kr/p/'.flickr_encode($matches[1]);
  }
  if (!defined('BITLY_API_KEY')) return $match[0];
  $request = 'http://api.bit.ly/shorten?version=2.0.1&longUrl='.urlencode($match[0]).'&login='.BITLY_LOGIN.'&apiKey='.BITLY_API_KEY;
  $json = json_decode(twitter_fetch($request));
  if ($json->errorCode == 0) {
    $results = (array) $json->results;
    $result = array_pop($results);
    return $result->shortUrl;
  } else {
    return $match[0];
  }
}

function twitter_fetch($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

function twitter_parse_links_callback($matches) {
  $url = $matches[1];
  if (substr($url, 0, strlen(BASE_URL)) == BASE_URL) return "<a href='$url'>$url</a>";
  if (setting_fetch('gwt') == 'on') {
    $encoded = urlencode($url);
    return "<a href='http://google.com/gwt/n?u={$encoded}' target='_blank'>{$url}</a>";
  } else {
    return theme('external_link', $url);
  }
}

function twitter_parse_tags($input) {
  $out = preg_replace_callback('#(\w+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)(?<![.,])#is', 'twitter_parse_links_callback', $input);
  $out = preg_replace('#(^|\s)@([a-z_A-Z0-9]+)#', '$1@<a href="user/$2">$2</a>', $out);
  $out = preg_replace('#(^|\s)(\\#([a-z_A-Z0-9:_-]+))#', '$1<a href="hash/$3">$2</a>', $out);
  if (!in_array(setting_fetch('browser'), array('text', 'worksafe'))) {
    $out = twitter_photo_replace($out);
  }
  return $out;
}

function flickr_decode($num) {
  $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
  $decoded = 0;
  $multi = 1;
  while (strlen($num) > 0) {
    $digit = $num[strlen($num)-1];
    $decoded += $multi * strpos($alphabet, $digit);
    $multi = $multi * strlen($alphabet);
    $num = substr($num, 0, -1);
  }
  return $decoded;
}

function flickr_encode($num) {
  $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
  $base_count = strlen($alphabet);
  $encoded = '';
  while ($num >= $base_count) {
    $div = $num/$base_count;
    $mod = ($num-($base_count*intval($div)));
    $encoded = $alphabet[$mod] . $encoded;
    $num = intval($div);
  }
  if ($num) $encoded = $alphabet[$num] . $encoded;
  return $encoded;
}

function twitter_photo_replace($text) {
  $images = array();
  $tmp = strip_tags($text);
  if (preg_match_all('#twitgoo.com/([\d\w]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $match) {
      $images[] = theme('external_link', "http://twitgoo.com/{$match}", "<img src='http://twitgoo.com/show/thumb/{$match}' class='twitpic' />");
    }
  }
  if (preg_match_all('#twitpic.com/([\d\w]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $match) {
       $images[] = theme('external_link', "http://twitpic.com/{$match}", "<img src='http://twitpic.com/show/thumb/{$match}' class='twitpic' />");
    }
  }
  if (preg_match_all('#yfrog.([a-zA-Z.]{2,5})/([0-9a-zA-Z]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[2] as $key => $match) {
       $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='http://yfrog.{$matches[1][$key]}/{$match}.th.jpg' />");
    }
  }
  if (preg_match_all('#twitxr.com/[^ ]+/updates/([\d]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $key => $match) {
      $thumb = 'http://twitxr.com/thumbnails/'.substr($match, -2).'/'.$match.'_th.jpg';
       $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='$thumb' />");
    }
  }
  if (preg_match_all('#moblog.net/view/([\d]+)/#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $key => $match) {
       $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='moblog/$match' />");
    }
  }
  if (preg_match_all('#hellotxt.com/i/([\d\w]+)#i', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $key => $match) {
       $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='http://hellotxt.com/image/{$match}.s.jpg' />");
    }
  }
  if (preg_match_all('#ts1.in/(\d+)#i', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $key => $match) {
       $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='http://ts1.in/mini/{$match}' />");
    }
  }
  if (defined('FLICKR_API_KEY')) {
    if(preg_match_all('#flickr.com/[^ ]+/([\d]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
      foreach ($matches[1] as $key => $match) {
         $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='flickr/$match' />");
      }
    }
    if(preg_match_all('#flic.kr/p/([\w\d]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
      foreach ($matches[1] as $key => $match) {
        $id = flickr_decode($match);
         $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='flickr/$id' />");
      }
    }
  }
  if (defined('MOBYPICTURE_API_KEY') && preg_match_all('#mobypicture.com/\?([a-z0-9]+)#', $tmp, $matches, PREG_PATTERN_ORDER) > 0) {
    foreach ($matches[1] as $key => $match) {
       $images[] = theme('external_link', "http://{$matches[0][$key]}", "<img src='mobypicture/$match' />");
    }
  }
  if (empty($images)) return $text;
  return implode('<br />', $images).'<br />'.$text;
}

function generate_thumbnail($query) {
  $id = $query[1];
  if ($id) {
    header('HTTP/1.1 301 Moved Permanently');
    if ($query[0] == 'flickr') {
      $url = "http://api.flickr.com/services/rest/?method=flickr.photos.getSizes&photo_id=$id&api_key=".FLICKR_API_KEY;
      $flickr_xml = twitter_fetch($url);
      if (setting_fetch('browser') == 'mobile') {
        $pattern = '#"(http://.*_t\.jpg)"#';
      } else {
        $pattern = '#"(http://.*_m\.jpg)"#';
      }
      preg_match($pattern, $flickr_xml, $matches);
      header('Location: '. $matches[1]);
    }
    if ($query[0] == 'mobypicture') {
      $url = "http://api.mobypicture.com/?action=getThumbUrl&t={$id}&s=thumbnail&k=".MOBYPICTURE_API_KEY;
      $thumb = twitter_fetch($url);
      header('Location: '. $thumb);
    }
    if ($query[0] == 'moblog') {
      $url = "http://moblog.net/view/{$id}/";
      $html = twitter_fetch($url);
      if (preg_match('#"(/media/[a-zA-Z0-9]/[^"]+)"#', $html, $matches)) {
        $thumb = 'http://moblog.net' . str_replace(array('.j', '.J'), array('_tn.j', '_tn.J'), $matches[1]);
        $pos = strrpos($thumb, '/');
        $thumb = substr($thumb, 0, $pos) . '/thumbs' . substr($thumb, $pos);
      }
      header('Location: '. $thumb);
    }
  }
  exit();
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
  $id = (string) $query[1];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $status = twitter_process($request);
    $content = theme('status', $status);
    if (!$status->user->protected) {
      $thread = twitter_thread_timeline($id);
    }
    if ($thread) {
      $content .= '<p>And the experimental conversation view...</p>'.theme('timeline', $thread);
      $content .= "<p>Don't like the thread order? Go to <a href='settings'>settings</a> to reverse it. Either way - the dates/times are not always accurate.</p>";
    }
    theme('page', "Status $id", $content);
  }
}

function twitter_thread_timeline($thread_id) {
  $request = "http://search.twitter.com/search/thread/{$thread_id}";
  $tl = twitter_standard_timeline(twitter_fetch($request), 'thread');
  return $tl;
}

function twitter_retweet_page($query) {
  $id = (string) $query[1];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $tl = twitter_process($request);
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
  twitter_ensure_post_action();
  
  $id = (string) $query[1];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/destroy/{$id}.json?page=".intval($_GET['page']);
    $tl = twitter_process($request, true);
    twitter_refresh('user/'.user_current_username());
  }
}

function twitter_ensure_post_action() {
  // This function is used to make sure the user submitted their action as an HTTP POST request
  // It slightly increases security for actions such as Delete and Block
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Error: Invalid HTTP request method for this action.');
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
    twitter_process($request, true);
    twitter_refresh('friends');
  }
}

function twitter_block_page($query) {
  twitter_ensure_post_action();
  $user = $query[1];
  if ($user) {
    if($query[0] == 'block'){
      $request = "http://twitter.com/blocks/create/{$user}.json";
    } else {
      $request = "http://twitter.com/blocks/destroy/{$user}.json";
    }
    twitter_process($request, true);
    twitter_refresh("user/{$user}");
  }
}

function twitter_confirmation_page($query) 
{
	// the URL /confirm can be passed parameters like so /confirm/param1/param2/param3 etc.
	$action = $query[1];
	$target = $query[2];	//The name of the user we are doing this action on
	$target_id = $query[3];	//The targets's ID.  Needed to check if they are being blocked.

  switch ($action) {
    case 'block':
      if (twitter_block_exists($target_id)) //Is the target blocked by the user?
      {
        $action = 'unblock';
        $content  = "<p>Are you really sure you want to <strong>Unblock $target</strong>?</p>";
        $content .= '<ul><li>They will see your updates on their home page if they follow you again.</li><li>You <em>can</em> block them again if you want.</li></ul>';		
      }
      else
      {
        $content = "<p>Are you really sure you want to <strong>$action $target</strong>?</p>";
        $content .= "<ul><li>You won't show up in their list of friends</li><li>They won't see your updates on their home page</li><li>They won't be able to follow you</li><li>You <em>can</em> unblock them but you will need to follow them again afterwards</li></ul>";
      }
      break;
    
    case 'delete':
      $content = '<p>Are you really sure you want to delete your tweet?</p>';
      $content .= "<ul><li>Tweet ID: <strong>$target</strong></li><li>There is no way to undo this action.</li></ul>";
      break;
  }    
  $content .= "<form action='$action/$target' method='post'><p><input type='submit' value='Yes please' /></form>";
  theme('Page', 'Confirm', $content);
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
  twitter_ensure_post_action();
  $status = twitter_url_shorten(stripslashes(trim($_POST['status'])));
  if ($status) {
    $request = 'http://twitter.com/statuses/update.json';
    $post_data = array('source' => 'dabr', 'status' => $status);
    $in_reply_to_id = (string) $_POST['in_reply_to_id'];
    if (is_numeric($in_reply_to_id)) {
      $post_data['in_reply_to_status_id'] = $in_reply_to_id;
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
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  theme('page', 'Replies', $content);
}

function twitter_directs_page($query) {
  $action = strtolower(trim($query[1]));
  switch ($action) {
    case 'delete':
      $id = $query[2];
      if (!is_numeric($id)) return;
      $request = "http://twitter.com/direct_messages/destroy/$id.json";
      twitter_process($request, true);
      twitter_refresh();
      
    case 'create':
      $to = $query[2];
      $content = theme('directs_form', $to);
      theme('page', 'Create DM', $content);
    
    case 'send':
      twitter_ensure_post_action();
      $to = trim(stripslashes($_POST['to']));
      $message = trim(stripslashes($_POST['message']));
      $request = 'http://twitter.com/direct_messages/new.json';
      twitter_process($request, array('user' => $to, 'text' => $message));
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
	
	if (friendship_exists($to) != 1)
	{
		$html_to = "<em>Warning</em> <b>" . $to . "</b> is not following you. You cannot send them a Direct Message :-(<br/>";
	}
    $html_to .= "Sending direct message to <b>$to</b><input name='to' value='$to' type='hidden'>";
  } else {
    $html_to .= "To: <input name='to'><br />Message:";
  }
   $content = "<form action='directs/send' method='post'>$html_to<br><textarea name='message' cols='50' rows='3' id='message'></textarea><br><input type='submit' value='Send'><span id='remaining'>140</span></form>";
   $content .= js_counter("message");
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
  $tl = twitter_standard_timeline($tl->results, 'search');
  return $tl;
}

function twitter_user_page($query) {
  $screen_name = $query[1];
  if ($screen_name) {
    $content = '';
    if ($query[2] == 'reply') {
      $in_reply_to_id = (string) $query[3];
      if (is_numeric($in_reply_to_id)) {
        $content .= "<p>In reply to tweet ID $in_reply_to_id...</p>";
      }
    } else {
      $in_reply_to_id = 0;
    }
    $user = twitter_user_info($screen_name);
    if (user_is_current_user($user->screen_name)) {
      $status = "@{$user->screen_name} ";
    } else {
      $status = '';
    }
    $content .= theme('status_form', $status, $in_reply_to_id);
    $content .= theme('user_header', $user);
    
    if (isset($user->status)) {
      if (user_type() == 'oauth') {
        $request = "http://twitter.com/statuses/user_timeline/{$screen_name}.json?page=".intval($_GET['page']);
      } else {
        $request = "http://twitter.com/statuses/user_timeline.json?screen_name={$screen_name}&page=".intval($_GET['page']);
      }
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
  $id = (string) $query[1];
  if (!is_numeric($id)) return;
  if ($query[0] == 'unfavourite') {
    $request = "http://twitter.com/favorites/destroy/$id.json";
  } else {
    $request = "http://twitter.com/favorites/create/$id.json";
  }
  twitter_process($request, true);
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

function twitter_hashtag_page($query) {
  if (isset($query[1])) {
    $hashtag = '#'.$query[1];
    $content = theme('status_form', $hashtag.' ');
    $tl = twitter_search($hashtag);
    $content .= theme('timeline', $tl);
    theme('page', $hashtag, $content);
  } else {
    theme('page', 'Hashtag', 'Hash hash!');
  }
}

function theme_status_form($text = '', $in_reply_to_id = NULL) {
  if (user_is_authenticated()) {
    return "<form method='post' action='update'><input name='status' value='{$text}' maxlength='140' /> <input name='in_reply_to_id' value='{$in_reply_to_id}' type='hidden' /><input type='submit' value='Update' /></form>";
  }
}

function theme_status($status) {
  $time_since = theme('status_time_link', $status);
  $parsed = twitter_parse_tags($status->text);
  $avatar = theme('avatar', $status->user->profile_image_url, 1);

  $out = theme('status_form', "@{$status->user->screen_name} ");
  $out .= "<p>$parsed</p>
<table align='center'><tr><td>$avatar</td><td><a href='user/{$status->user->screen_name}'>{$status->user->screen_name}</a>
<br />$time_since</td></tr></table>";
  if (user_is_current_user($status->user->screen_name)) {
    $out .= "<form action='delete/{$status->id}' method='post'><input type='submit' value='Delete without confirmation' /></form>";
  }
  return $out;
}

function theme_retweet($status) {
  $text = "RT @{$status->user->screen_name}: {$status->text}";
  $length = strlen($text);
  $from = substr($_SERVER['HTTP_REFERER'], strlen(BASE_URL));
  $content = "<form action='update' method='post'><input type='hidden' name='from' value='$from' /><textarea name='status' cols='50' rows='3' id='status'>$text</textarea><br><input type='submit' value='Retweet'><span id='remaining'>" . (140 - $length) ."</span></form>";
  $content .= js_counter("status");  
  return $content;
}

function theme_user_header($user) {
  $name = theme('full_name', $user);
  $full_avatar = str_replace('_normal.', '.', $user->profile_image_url);
  $link = theme('external_link', $user->url);
  $raw_date_joined = strtotime($user->created_at);
  $date_joined = date('jS M Y', $raw_date_joined);
  $days_on_twitter = (time() - $raw_date_joined) / 86400;
  $tweets_per_day = round($user->statuses_count / $days_on_twitter, 1);
  $out = "<table><tr><td>".theme('external_link', $full_avatar, theme('avatar', $user->profile_image_url, 1))."</td>
<td><b>{$name}</b>
<small>";
  if ($user->verified == true) {
    $out .= '<br /><strong>Verified Account</strong>';
  }
  if ($user->protected == true) {
    $out .= '<br /><strong>Private/Protected Tweets</strong>';
  }
  $out .= "
<br />Bio: {$user->description}
<br />Link: {$link}
<br />Location: {$user->location}
<br />Joined: {$date_joined} (~$tweets_per_day tweets per day)
</small>
<br />
{$user->statuses_count} tweets |
<a href='followers/{$user->screen_name}'>{$user->followers_count} followers</a> ";

  if ($user->following !== true) {
    $out .= "| <a href='follow/{$user->screen_name}'>Follow</a>";
  } else {
    $out .= " | <a href='unfollow/{$user->screen_name}'>Unfollow</a>";
  }
  
	//We need to pass the User Name and the User ID.  The Name is presented in the UI, the ID is used in checking
	$out.= " | <a href='confirm/block/{$user->screen_name}/{$user->id}'>Block | Unblock</a>";

  $out.= " | <a href='friends/{$user->screen_name}'>{$user->friends_count} friends</a>
| <a href='favourites/{$user->screen_name}'>{$user->favourites_count} favourites</a>
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
  if ($time > 0) {
    if (twitter_date('dmy') == twitter_date('dmy', $time)) {
      $out = format_interval(time() - $time, 1). ' ago';
    } else {
      $out = twitter_date('H:i', $time);
    }
  } else {
    $out = $status->created_at;
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
        setcookie('utc_offset', $offset, time() + 3000000, '/');
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
  if (!is_array($feed) && $source != 'thread') return $output;
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
        $output[(string) $new->id] = $new;
      }
      return $output;
    
    case 'search':
      foreach ($feed as $status) {
        $output[(string) $status->id] = (object) array(
          'id' => $status->id,
          'text' => $status->text,
          'source' => strpos($status->source, '&lt;') !== false ? html_entity_decode($status->source) : $status->source,
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
    
    case 'thread':
      // First pass: extract tweet info from the HTML
      $html_tweets = explode('</li>', $feed);
      foreach ($html_tweets as $tweet) {
        $id = preg_match_one('#msgtxt(\d*)#', $tweet);
        if (!$id) continue;
        $output[$id] = (object) array(
          'id' => $id,
          'text' => strip_tags(preg_match_one('#</a>: (.*)</span>#', $tweet)),
          'source' => preg_match_one('#>from (.*)</span>#', $tweet),
          'from' => (object) array(
            'id' => preg_match_one('#profile_images/(\d*)#', $tweet),
            'screen_name' => preg_match_one('#twitter.com/([^"]+)#', $tweet),
            'profile_image_url' => preg_match_one('#src="([^"]*)"#' , $tweet),
          ),
          'to' => (object) array(
            'screen_name' => preg_match_one('#@([^<]+)#', $tweet),
          ),
          'created_at' => str_replace('about', '', preg_match_one('#info">\s(.*)#', $tweet)),
        );
      }
      // Second pass: OPTIONALLY attempt to reverse the order of tweets
      if (setting_fetch('reverse') == 'yes') {
        $first = false;
        foreach ($output as $id => $tweet) {
          $date_string = str_replace('later', '', $tweet->created_at);
          if ($first) {
            $attempt = strtotime("+$date_string");
            if ($attempt == 0) $attempt = time();
            $previous = $current = $attempt - time() + $previous;
          } else {
            $previous = $current = $first = strtotime($date_string);
          }
          $output[$id]->created_at = date('r', $current);
        }
        $output = array_reverse($output);
      }
      return $output;

    default:
      echo "<h1>$source</h1><pre>";
      print_r($feed); die();
  }
}

function preg_match_one($pattern, $subject, $flags = NULL) {
  preg_match($pattern, $subject, $matches, $flags);
  return trim($matches[1]);
}

function twitter_user_info($username = null) {
  if (!$username)
  $username = user_current_username();
  if (user_type() == 'oauth') {
    $request = "http://twitter.com/users/show/$username.json";
  } else {
    $request = "http://twitter.com/users/show.json?screen_name=$username";
  }
  $user = twitter_process($request);
  return $user;
}

function theme_timeline($feed) {
  if (count($feed) == 0) return theme('no_tweets');
  $rows = array();
  $page = menu_current_page();
  $date_heading = false;
  foreach ($feed as $status) {
    $time = strtotime($status->created_at);
    if ($time > 0) {
      $date = twitter_date('l jS F Y', strtotime($status->created_at));
      if ($date_heading !== $date) {
        $date_heading = $date;
        $rows[] = array(array(
          'data' => "<small><b>$date</b></small>",
          'colspan' => 2
        ));
      }
    } else {
      $date = $status->created_at;
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
      "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link<br />{$text} <small>$source</small>",
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
  if (count($feed) >= 15) {
    $content .= theme('pagination');
  }
  return $content;
}

function twitter_is_reply($status) {
  if (!user_is_authenticated()) {
    return false;
  }
  $user = user_current_username();
  return preg_match("#@$user#i", $status->text);
}

function theme_followers($feed) {
  $rows = array();
  if (count($feed) == 0) return '<p>No users to display.</p>';
  foreach ($feed as $user) {
    $name = theme('full_name', $user);
    $rows[] = array(
      theme('avatar', $user->profile_image_url),
      "{$name} - {$user->location}<small><br />{$user->description}</small>",
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
      "<a href='user/{$status->from_user}'>{$status->from_user}</a> $actions - {$link}<br />{$text}",
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
  return "<form action='search' method='get'><input name='query' value=\"$query\" /><input type='submit' value='Search' /></form>";
}

function theme_external_link($url, $content = null) {
  if (!$content) $content = $url;
  return "<a href='$url' target='_blank'>$content</a>";
}

function theme_pagination() {
  $page = intval($_GET['page']);
  if (preg_match('#&q(.*)#', $_SERVER['QUERY_STRING'], $matches)) {
    $query = $matches[0];
  }
  if ($page == 0) $page = 1;
  $links[] = "<a href='{$_GET['q']}?page=".($page+1)."$query' accesskey='9'>Older</a> 9";
  if ($page > 1) $links[] = "<a href='{$_GET['q']}?page=".($page-1)."$query' accesskey='8'>Newer</a> 8";
  return '<p>'.implode(' | ', $links).'</p>';
}


function theme_action_icons($status) {
  $from = $status->from->screen_name;
  $actions = array();
  
  if (!$status->is_direct) {
    $actions[] = theme('action_icon', "user/{$from}/reply/{$status->id}", 'images/reply.png', '@');
  }
  if (!user_is_current_user($from)) {
    $actions[] = theme('action_icon', "directs/create/{$from}", 'images/dm.png', 'DM');
  }
  if (!$status->is_direct) {
    if ($status->favorited == '1') {
      $actions[] = theme('action_icon', "unfavourite/{$status->id}", 'images/star.png', 'UNFAV');
    } else {
      $actions[] = theme('action_icon', "favourite/{$status->id}", 'images/star_grey.png', 'FAV');
    }
    $actions[] = theme('action_icon', "retweet/{$status->id}", 'images/retweet.png', 'RT');
    if (user_is_current_user($from)) {
      $actions[] = theme('action_icon', "confirm/delete/{$status->id}", 'images/trash.gif', 'DEL');
    }
  } else {
    $actions[] = theme('action_icon', "directs/delete/{$status->id}", 'images/trash.gif', 'DEL');
  }

  return implode(' ', $actions);
}

function theme_action_icon($url, $image_url, $text) {
  // alt attribute left off to reduce bandwidth by about 720 bytes per page
  return "<a href='$url'><img src='$image_url' /></a>";
}

?>
