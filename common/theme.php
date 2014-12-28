<?php
// require_once ("common/advert.php");

$current_theme = false;

//	Setup
//	`theme('user_header', $user);` becomes `theme_user_header($user)` etc.
function theme() {
	global $current_theme;
	$args = func_get_args();
	$function = array_shift($args);
	$function = 'theme_'.$function;

	// if ($current_theme) {
	// 	$custom_function = $current_theme.'_'.$function;
	// 	if (function_exists($custom_function))
	// 	$function = $custom_function;
	// } else {
	// 	if (!function_exists($function))
	// 	return "<p>Error: theme function <b>{$function}</b> not found.</p>";
	// }
	return call_user_func_array($function, $args);
}

// function theme_csv($headers, $rows) {
// 	$out = implode(',', $headers)."\n";
// 	foreach ($rows as $row) {
// 		$out .= implode(',', $row)."\n";
// 	}
// 	return $out;
// }

function theme_list($items, $attributes) {
	if (!is_array($items) || count($items) == 0) {
		return '';
	}
	$output = '<ul'.theme_attributes($attributes).'>';
	foreach ($items as $item) {
		$output .= "<li>$item</li>\n";
	}
	$output .= "</ul>\n";
	return $output;
}

function theme_options($options, $selected = null) {
	if (count($options) == 0) return '';
	$output = '';
	foreach($options as $value => $name) {
		if (is_array($name)) {
			$output .= '<optgroup label="'.$value.'">';
			$output .= theme('options', $name, $selected);
			$output .= '</optgroup>';
		} else {
			$output .= '<option value="'.$value.'"'.($selected == $value ? ' selected="selected"' : '').'>'.$name."</option>\n";
		}
	}
	return $output;
}

function theme_info($info) {
	$rows = array();
	foreach ($info as $name => $value) {
		$rows[] = array($name, $value);
	}
	return theme('table', array(), $rows);
}

function theme_table($headers, $rows, $attributes = null) {
	$out = '<div'.theme_attributes($attributes).'>';
	if (count($headers) > 0) {
		$out .= '<thead><tr>';
		foreach ($headers as $cell) {
			$out .= theme_table_cell($cell, true);
		}
		$out .= '</tr></thead>';
	}
	if (count($rows) > 0) {
		$out .= theme('table_rows', $rows);
	}
	$out .= '</div>';
	return $out;
}

function theme_table_rows($rows) {
	$i = 0;
    $out = '';
	foreach ($rows as $row) {
		if ($row['data']) {
			$cells = $row['data'];
			unset($row['data']);
			$attributes = $row;
		} else {
			$cells = $row;
			$attributes = false;
		}
		$attributes['class'] .= ($attributes['class'] ? ' ' : '') . ($i++ %2 ? 'even' : 'odd');
		$out .= '<div'.theme_attributes($attributes).'>';
		foreach ($cells as $cell) {
			$out .= theme_table_cell($cell);
		}
		$out .= "</div>\n";
	}
	return $out;
}

function theme_attributes($attributes) {
	if (!$attributes) return '';
    $out = '';
	foreach ($attributes as $name => $value) {
		$out .= " $name=\"$value\"";
	}
	return $out;
}

function theme_table_cell($contents, $header = false) {
	if (is_array($contents)) {
		$value = $contents['data'];
		unset($contents['data']);
		$attributes = $contents;
	} else {
		$value = $contents;
		$attributes = false;
	}
	return "<span".theme_attributes($attributes).">$value</span>";
}


function theme_error($message) {
	theme_page('Error', $message);
}

function theme_page($title, $content) {
	$body = "";
	$body .= theme('menu_top');
	$body .= $content;
	$body .= theme('menu_bottom');
	//$body .= theme('google_analytics');
	if (DEBUG_MODE == 'ON') {
		global $dabr_start, $api_time, $services_time, $rate_limit;
		$time = microtime(1) - $dabr_start;
		$body .= '<p>Processed in '.round($time, 4).' seconds ('.round(($time - $api_time - $services_time) / $time * 100).'% Dabr, '.round($api_time / $time * 100).'% Twitter, '.round($services_time / $time * 100).'% other services). '.$rate_limit.'.</p>';
	}
    $meta = '';
	if ($title == 'Login') {
		$title = 'Dabr - mobile Twitter Login';
		$meta = '<meta name="description" content="Free open source alternative to mobile Twitter, bringing you the complete Twitter experience to your phone." />';
	}
	ob_start('ob_gzhandler');
	header('Content-Type: text/html; charset=utf-8');
	echo	'<!DOCTYPE html>
            <html>
                <head>
					<meta charset="utf-8" />
					<meta name="viewport" content="width=device-width; initial-scale=1;" />
					<title>Dabr - ' . $title . '</title>
					<base href="',BASE_URL,'" />
					'.$meta.theme('css').'
				</head>
				<body id="thepage">';
//	echo 				"<div id=\"advert\">" . show_advert() . "</div>";
	echo 				$body;
	if (setting_fetch('colours') == null)
	{
		//	If the cookies haven't been set, remind the user that they can set how Dabr looks
		echo			'<p>Think Dabr looks ugly? <a href="settings">Change the colours!</a></p>';
	}
	echo '      </body>
			</html>';
	exit();
}

function theme_colours() {
	$info = $GLOBALS['colour_schemes'][setting_fetch('colours', 0)];
	list(, $bits) = explode('|', $info);
	$colours = explode(',', $bits);
	return (object) array(
		'links'     => $colours[0],
		'bodybg'    => $colours[1],
		'bodyt'     => $colours[2],
		'small'     => $colours[3],
		'odd'       => $colours[4],
		'even'      => $colours[5],
		'replyodd'  => $colours[6],
		'replyeven' => $colours[7],
		'menubg'    => $colours[8],
		'menut'     => $colours[9],
		'menua'     => $colours[10],
	);
}

function theme_profile_form($user){
	// Profile form
	$out .= "
				<form name='profile' action='edit-profile' method='post' enctype='multipart/form-data'>
				    <hr />Name:     <input name='name' maxlength='20' value='"                 . htmlspecialchars($user->name, ENT_QUOTES) ."' />
				    <br />Avatar:   <img src='".theme_get_avatar($user)."' /> <input type='file' name='image' />
				    <br />Bio:      <textarea name='description' cols=40 rows=6 maxlength=160>". htmlspecialchars($user->description, ENT_QUOTES)."</textarea>
				    <br />Link:     <input name='url' maxlength='100' size=40 value='"         . htmlspecialchars($user->url, ENT_QUOTES) ."' />
				    <br />Location: <input name='location' maxlength='30' value='"             . htmlspecialchars($user->location, ENT_QUOTES) ."' />
				    <br /><input type='submit' value='Update Profile' />
				</form>";

	return $out;
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
	$content = "<form action='directs/send' method='post'>$html_to<br><textarea name='message' style='width:90%; max-width: 400px;' rows='3' id='message'></textarea><br><input type='submit' value='Send'><span id='remaining'>140</span></form>";
	$content .= js_counter("message");
	return $content;
}
function theme_status_form($text = '', $in_reply_to_id = null) {

	if (user_is_authenticated()) {
		$icon = "images/twitter-bird-16x16.png";

		//	adding ?status=foo will automaticall add "foo" to the text area.
		if ($_GET['status'])
		{
			$text = $_GET['status'];
		}
		
		// return "<fieldset>
  //                   <legend>
  //                       <img src='{$icon}' width='16' height='16' /> What's Happening?
  //                   </legend>
  //                   <form method='post' action='update'>
  //                       <input name='status' value='{$text}' maxlength='140' />
  //                       <input name='in_reply_to_id' value='{$in_reply_to_id}' type='hidden' />
  //                       <input type='submit' value='Tweet' />
  //                   </form>
  //               </fieldset>" . "<h1>{$text}</h1>";
        $output = '
        <form method="post" action="update" enctype="multipart/form-data">
            <fieldset>
                <legend><img src="'.$icon.'" width="16" height="16" /> What\'s Happening?</legend>
                <textarea id="status" name="status" rows="4" style="width:95%; max-width: 400px;">'.$text.'</textarea>
                <div>
                    <input name="in_reply_to_id" value="'.$in_reply_to_id.'" type="hidden" />
                    <input type="submit" value="Tweet" />
                    <span id="remaining">140</span> 
                    <span id="geo" style="display: none;">
                        <input onclick="goGeo()" type="checkbox" id="geoloc" name="location" />
                        <label for="geoloc" id="lblGeo"></label>
                    </span>
                </div>
                <div class="fileinputs">
					Image: <input type="file" accept="image/*" name="image" class="file" />
				</div>
            </fieldset>
            <script type="text/javascript">
                started = false;
                chkbox = document.getElementById("geoloc");
                if (navigator.geolocation) {
                    geoStatus("Tweet my location");
                    if ("'.$_COOKIE['geo'].'"=="Y") {
                        chkbox.checked = true;
                        goGeo();
                    }
                }
                function goGeo(node) {
                    if (started) return;
                    started = true;
                    geoStatus("Locating...");
                    navigator.geolocation.getCurrentPosition(geoSuccess, geoStatus , { enableHighAccuracy: true });
                }
                function geoStatus(msg) {
                    document.getElementById("geo").style.display = "inline";
                    document.getElementById("lblGeo").innerHTML = msg;
                }
                function geoSuccess(position) {
                    geoStatus("Tweet my <a href=\'http://maps.google.co.uk/m?q=" + position.coords.latitude + "," + position.coords.longitude + "\' target=\'blank\'>location</a>");
                    chkbox.value = position.coords.latitude + "," + position.coords.longitude;
                }
            </script>
        </form>';
        $output .= js_counter('status');
        return $output;
	}
}

function theme_status($status) {
	//32bit int / snowflake patch
	if($status->id_str) $status->id = $status->id_str;
	
	$feed[] = $status;
	$tl = twitter_standard_timeline($feed, 'status');
	$content = theme('timeline', $tl);
	return $content;
}

function theme_retweet($status)
{
	$text = "RT @{$status->user->screen_name}: {$status->text}";
	$length = function_exists('mb_strlen') ? mb_strlen($text,'UTF-8') : strlen($text);
	$from = substr($_SERVER['HTTP_REFERER'], strlen(BASE_URL));

	if($status->user->protected == 0)
	{
		$content.="<p>Twitter's new style retweet:</p>
					<form action='twitter-retweet/{$status->id_str}' method='post'>
						<input type='hidden' name='from' value='$from' />
						<input type='submit' value='Twitter Retweet' />
					</form>
					<hr />";
	}
	else
	{
		$content.="<p>@{$status->user->screen_name} doesn't allow you to retweet them. You will have to use the  use the old style editable retweet</p>";
	}

	$content .= "<p>Old style editable retweet:</p>
					<form action='update' method='post'>
						<input type='hidden' name='from' value='$from' />
						<textarea name='status' style='width:90%; max-width: 400px;' rows='3' id='status'>$text</textarea>
						<br/>
						<input type='submit' value='Retweet' />
						<span id='remaining'>" . (140 - $length) ."</span>
					</form>";
	$content .= js_counter("status");

	return $content;
}
function theme_user_header($user) {
	$friendship = friendship($user->screen_name);

	$followed_by = $friendship->relationship->target->followed_by; //The $user is followed by the authenticating
	$following = $friendship->relationship->target->following;
	$name = theme('full_name', $user);
	$full_avatar = theme_get_full_avatar($user);
	$link = twitter_parse_tags($user->url, $user->entities->url);
	//Some locations have a prefix which should be removed (UbertTwitter and iPhone)
	$cleanLocation = str_replace(array("iPhone: ","ÜT: "),"",$user->location);
	$raw_date_joined = strtotime($user->created_at);
	$date_joined = date('jS M Y', $raw_date_joined);
	$tweets_per_day = twitter_tweets_per_day($user, 1);
	$bio = twitter_parse_tags($user->description, $user->entities->description);
	$out = "<div class='profile'>
	            <span class='avatar'>".theme('external_link', $full_avatar, theme('avatar', theme_get_avatar($user)))."</span>
	            <span class='status shift'><b>{$name}</b>
	            <br />
	            <span class='about'>";
	if ($user->verified == true) {
		$out .= '   <strong>Verified ✔</strong><br />';
	}
	if ($user->protected == true) {
		$out .= '   <strong>Private/Protected Tweets</strong><br />';
	}

	$out .= "       Bio: {$bio}<br />
	                Link: {$link}<br />
	                Location: <a href=\"https://maps.google.com/maps?q={$cleanLocation}\" target=\"" . get_target() . "\">
	                              {$user->location}
	                          </a><br />
	                Joined: {$date_joined} (~" . pluralise('tweet', $tweets_per_day, true) . " per day)
	           </span>
	        </span>
	    <div class='features'>";
	
	$out .= pluralise('tweet', $user->statuses_count, true);

	//If the authenticated user is not following the protected used, the API will return a 401 error when trying to view friends, followers and favourites
	//This is not the case on the Twitter website
	//To avoid the user being logged out, check to see if she is following the protected user. If not, don't create links to friends, followers and favourites
	if ($user->protected == true && $followed_by == false) {
		$out .= " | " . pluralise('follower', $user->followers_count, true);
		$out .= " | " . pluralise('friend', $user->friends_count, true);
		$out .= " | " . pluralise('favourite', $user->favourites_count, true);
	}
	else {
		$out .= " | <a href='followers/{$user->screen_name}'>" . pluralise('follower', $user->followers_count, true) . "</a>";
		$out .= " | <a href='friends/{$user->screen_name}'>" . pluralise('friend', $user->friends_count, true) . "</a>";
		$out .= " | <a href='favourites/{$user->screen_name}'>" . pluralise('favourite', $user->favourites_count, true) . "</a>";
	}

	$out .= " | <a href='lists/{$user->screen_name}'>" . pluralise('list', $user->listed_count, true) . "</a>";
	if($following) {
		$out .=	" | <a href='directs/create/{$user->screen_name}'>Direct Message</a>";
	}
	
	//	One cannot follow, block, nor report spam oneself.
	if (strtolower($user->screen_name) !== strtolower(user_current_username())) {
	
		if ($followed_by == false) {
			$out .= " | <a href='follow/{$user->screen_name}'>Follow</a>";
		}
		else {
			$out .= " | <a href='unfollow/{$user->screen_name}'>Unfollow</a>";
		}

		// if($friendship->relationship->source->want_retweets) {
		// 	$out .= " | <a href='confirm/hideretweets/{$user->screen_name}'>Hide Retweets</a>";
		// }
		// else {
		// 	$out .= " | <a href='showretweets/{$user->screen_name}'>Show Retweets</a>";
		// }

		//We need to pass the User Name and the User ID.  The Name is presented in the UI, the ID is used in checking
		$blocked = $friendship->relationship->source->blocking; //The $user is blocked by the authenticating
		if ($blocked == true) {
			$out.= " | <a href='confirm/block/{$user->screen_name}/{$user->id}'>Unblock</a>";
		}
		else {
			$out.= " | <a href='confirm/block/{$user->screen_name}/{$user->id}'>Block</a>";
		}

		$out .= " | <a href='confirm/spam/{$user->screen_name}/{$user->id}'>Report Spam</a>";
	}
	
	$out .= " | <a href='search?query=%40{$user->screen_name}'>Search @{$user->screen_name}</a>";
	$out .= "</div></div>";
	return $out;
}

function theme_avatar($url, $force_large = false) {
	$size = 48;	//$force_large ? 48 : 24;
	return "<img src='$url' height='$size' width='$size' />";
}

function theme_status_time_link($status, $is_link = true) {
	$time = strtotime($status->created_at);
	if ($time > 0) {
		if (twitter_date('dmy') == twitter_date('dmy', $time) && !setting_fetch('timestamp')) {
			$out = format_interval(time() - $time, 1). ' ago';
		} else {
			$out = twitter_date('H:i', $time);
		}
	} else {
		$out = $status->created_at;
	}
	if ($is_link)
		$out = "<a href='status/{$status->id}' class='time'>$out</a>";
	return $out;
}

function theme_timeline($feed, $paginate = true) {
	if (count($feed) == 0) return theme('no_tweets');
	if (count($feed) < 2) { 
		$hide_pagination = true;
	}
	$rows = array();
	$page = menu_current_page();
	$date_heading = false;
	$first=0;
	
	// Add the hyperlinks *BEFORE* adding images
	foreach ($feed as &$status)	{
		$status->text = twitter_parse_tags($status->text, $status->entities);
	}
	unset($status);
	
	// Only embed images in suitable browsers
	
	if(!setting_fetch('hide_inline') && !in_array(setting_fetch('browser'), array('text', 'worksafe'))) {
		// oembed_embed_thumbnails($feed);
	}

	foreach ($feed as $status) {
		if ($first==0) {
			$since_id = $status->id;
			$first++;
		}
		else {
			$max_id =  $status->id;
			if ($status->original_id) {
				$max_id =  $status->original_id;
			}
		}
		$time = strtotime($status->created_at);
		if ($time > 0) {
			$date = twitter_date('l jS F Y', strtotime($status->created_at));
			if ($date_heading !== $date) {
				$date_heading = $date;
				$rows[] = array('data'  => array($date), 'class' => 'date');
			}
		}
		else {
			$date = $status->created_at;
		}
		$text = $status->text;
		if (!in_array(setting_fetch('browser'), array('text', 'worksafe'))) {
			$media = twitter_get_media($status);
		}
		$link = theme('status_time_link', $status, !$status->is_direct);
		$actions = theme('action_icons', $status);
		$avatar = theme('avatar', theme_get_avatar($status->from));
		$source = $status->source ? " from ".str_replace('rel="nofollow"', 'rel="nofollow" target="' . get_target() . '"', preg_replace('/&(?![a-z][a-z0-9]*;|#[0-9]+;|#x[0-9a-f]+;)/i', '&amp;', $status->source)) : ''; //need to replace & in links with &amps and force new window on links
		if ($status->place->name) {
			$source .= ", " . $status->place->name . ", " . $status->place->country;
		}
		if ($status->in_reply_to_status_id)	{
			$source .= ", in reply to <a href='status/{$status->in_reply_to_status_id_str}'>{$status->in_reply_to_screen_name}</a>";
		}
		if ($status->retweet_count)	{
			$source .= ", <a href='retweeted_by/{$status->id}'>retweeted " . x_times($status->retweet_count) . "</a>";
		}
		$retweeted = '';
		if ($status->retweeted_by) {
			$retweeted_by = $status->retweeted_by->user->screen_name;
			$retweeted = "<br /><small>" . theme('action_icon', "retweeted_by/{$status->id}", "♻", 'RT') . "retweeted by <a href='user/{$retweeted_by}'>{$retweeted_by}</a></small>";
			//$source .= "<br /><a href='retweeted_by/{$status->id}'>retweeted</a> by <a href='user/{$retweeted_by}'>{$retweeted_by}</a>";
		}
		if($status->favorite_count) {
			$source .= ', favourited ' . x_times($status->favorite_count);
		}
		//$html = "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link<br />{$text}<br />$media<small>$source</small>";
		$html = "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link{$retweeted}<br />{$text}<br />$media<span class='from'>$source</span>";

		unset($row);
		$class = 'status';
		
		if ($avatar)	{
			$row[] = array('data' => $avatar, 'class' => 'avatar');
			$class .= ' shift';
		}
		
		$row[] = array('data' => $html, 'class' => $class);

		$class = 'tweet';
		if ($page != 'replies' && twitter_is_reply($status)) {
			$class .= ' reply';
		}
		$row = array('data' => $row, 'class' => $class);

		$rows[] = $row;
	}
	$content = theme('table', array(), $rows, array('class' => 'timeline'));

	if(!$hide_pagination) {
		if($paginate) {
			if($page == 'some-unknown-method-which-doesnt-take-max_id') {
				$content .= theme('pagination');
			}
			//if ($page == '' || $page == 'user' || $page == 'search' || $page == 'hash' || $page == 'tofrom' || $page == 'replies' || $page == 'directs') {
			else {
				if(is_64bit()) $max_id = intval($max_id) - 1; //stops last tweet appearing as first tweet on next page
				$content .= theme('pagination', $max_id);				
			}
		}
	}

	return $content;
}

function theme_followers($feed, $nextPageURL) {
	$rows = array();
	if (count($feed) == 0 || $feed == '[]') return '<p>No users to display.</p>';

	foreach ($feed as $user) {

		$name = theme('full_name', $user);
		$tweets_per_day = twitter_tweets_per_day($user);
		$last_tweet = strtotime($user->status->created_at);
		$content = "{$name}<br /><span class='about'>";
		if($user->description != "")
			$content .= "Bio: " . twitter_parse_tags($user->description) . "<br />";
		if($user->location != "")
			$content .= "Location: {$user->location}<br />";
		$content .= "Info: ";
		$content .= pluralise('tweet', (int)$user->statuses_count, true) . ", ";
		$content .= pluralise('friend', (int)$user->friends_count, true) . ", ";
		$content .= pluralise('follower', (int)$user->followers_count, true) . ", ";
		$content .= "~" . pluralise('tweet', $tweets_per_day, true) . " per day<br />";
		$content .= "Last tweet: ";
		if($user->protected == 'true' && $last_tweet == 0)
			$content .= "Private";
		else if($last_tweet == 0)
			$content .= "Never tweeted";
		else
			$content .= twitter_date('l jS F Y', $last_tweet);
		$content .= "</span>";

		$rows[] = array('data' => array(array('data' => theme('avatar', theme_get_avatar($user)), 'class' => 'avatar'),
		                                array('data' => $content, 'class' => 'status shift')),
		                'class' => 'tweet');

	}

	$content = theme('table', array(), $rows, array('class' => 'followers'));
	if ($nextPageURL)
		$content .= "<a href='{$nextPageURL}'>Next</a>";
	return $content;
}

// Annoyingly, retweeted_by.xml and followers.xml are subtly different. 
// TODO merge theme_retweeters with theme_followers
function theme_retweeters($feed, $hide_pagination = false) {
	$rows = array();
	if (count($feed) == 0 || $feed == '[]') return '<p>No one has retweeted this status.</p>';

	foreach ($feed->user as $user) {

		$name = theme('full_name', $user);
		$tweets_per_day = twitter_tweets_per_day($user);
		$last_tweet = strtotime($user->status->created_at);
		$content = "{$name}<br /><span class='about'>";
		if($user->description != "")
			$content .= "Bio: " . twitter_parse_tags($user->description) . "<br />";
		if($user->location != "")
			$content .= "Location: {$user->location}<br />";
		$content .= "Info: ";
		$content .= pluralise('tweet', (int)$user->statuses_count, true) . ", ";
		$content .= pluralise('friend', (int)$user->friends_count, true) . ", ";
		$content .= pluralise('follower', (int)$user->followers_count, true) . ", ";
		$content .= "~" . pluralise('tweet', $tweets_per_day, true) . " per day<br />";
		$content .= "</span>";

		$rows[] = array('data' => array(array('data' => theme('avatar', theme_get_avatar($user)), 'class' => 'avatar'),
		                                array('data' => $content, 'class' => 'status shift')),
		                'class' => 'tweet');

	}

	$content = theme('table', array(), $rows, array('class' => 'followers'));
	if (!$hide_pagination)
	$content .= theme('list_pagination', $feed);
	return $content;
}

function theme_full_name($user) {
	$name = "<a href='user/{$user->screen_name}'>{$user->screen_name}</a>";
	//THIS IF STATEMENT IS RETURNING FALSE EVERYTIME ?!?
	//if ($user->name && $user->name != $user->screen_name) {
	if($user->name != "") {
		$name .= " ({$user->name})";
	}
	return $name;
}

// http://groups.google.com/group/twitter-development-talk/browse_thread/thread/50fd4d953e5b5229#
function theme_get_avatar($object) {
	if ($_SERVER['HTTPS'] == "on" || (0 === strpos(BASE_URL, "https://"))) { //$object->profile_image_url_https) {
		return image_proxy($object->profile_image_url_https, "48/48/");
	}
	else {
		return image_proxy($object->profile_image_url, "48/48/");
	}
}

function theme_get_full_avatar($object) {
	if ($_SERVER['HTTPS'] == "on" && $object->profile_image_url_https) {
		return image_proxy(str_replace('_normal.', '.', $object->profile_image_url_https));
	}
	else {
		return image_proxy(str_replace('_normal.', '.', $object->profile_image_url));
	}
}

function theme_no_tweets() {
	return '<p>No tweets to display.</p>';
}

function theme_search_results($feed) {
	$rows = array();
	foreach ($feed->results as $status) {
		$text = twitter_parse_tags($status->text, $status->entities);
		$link = theme('status_time_link', $status);
		$actions = theme('action_icons', $status);

		$row = array(
		theme('avatar', theme_get_avatar($status)),
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
	$query = stripslashes(htmlentities($query,ENT_QUOTES,"UTF-8"));
	return '
	<form action="search" method="get"><input name="query" value="'. $query .'" />
		<input type="submit" value="Search" />
	</form>';
}

function theme_external_link($url, $content = null) {
	// //Long URL functionality.  Also uncomment function long_url($shortURL)
	// if (!$content)
	// {
	// 	//Used to wordwrap long URLs
	// 	//return "<a href='$url' target='_blank'>". wordwrap(long_url($url), 64, "\n", true) ."</a>";
	// 	return "<a href='$url' target='" . get_target() . "'>". long_url($url) ."</a>";
	// }
	// else
	// {
		return "<a href='$url' target='" . get_target() . "'>$content</a>";
	// }

}

function theme_pagination($max_id = false) {
	$page = intval($_GET['page']);
	if (preg_match('#&q(.*)#', $_SERVER['QUERY_STRING'], $matches))	{
		$query = $matches[0];
	}
	if($max_id) {
		$links[] = "<a href='{$_GET['q']}?max_id=".$max_id."$query' accesskey='9'>Older</a> 9";
	}
	else {
		if ($page == 0) $page = 1;
		$links[] = "<a href='{$_GET['q']}?page=".($page+1)."$query' accesskey='9'>Older</a> 9";
		if ($page > 1) $links[] = "<a href='{$_GET['q']}?page=".($page-1)."$query' accesskey='8'>Newer</a> 8";
	}
	if($query) {
		$query = '?' . substr($query, 1);
	}
	$links[] = "<a href='{$_GET['q']}?$query'>First</a>";
	return '<p>'.implode(' | ', $links).'</p>';
}

function theme_action_icons($status) {
	$from = $status->from->screen_name;
	$retweeted_by = $status->retweeted_by->user->screen_name;
	$retweeted_id = $status->retweeted_by->id;
	$geo = $status->geo;
	$actions = array();

	if (!$status->is_direct) {
		$actions[] = theme('action_icon', "user/{$from}/reply/{$status->id}", '↩', '@');
	}
	//Reply All functionality.
	if( $status->entities->user_mentions ) {
//		$actions[] = theme('action_icon', "user/{$from}/replyall/{$status->id}", 'images/replyall.png', 'REPLY ALL');
	}

	if (!user_is_current_user($from)) {
		$actions[] = theme('action_icon', "directs/create/{$from}", '✉', 'DM');
	}
	if (!$status->is_direct) {
		if ($status->favorited == '1') {
			$actions[] = theme('action_icon', "unfavourite/{$status->id}", '★', 'UNFAV');
		} else {
			$actions[] = theme('action_icon', "favourite/{$status->id}", '☆', 'FAV');
		}
		// Show a diffrent retweet icon to indicate to the user this is an RT
		if ($status->retweeted || user_is_current_user($retweeted_by)) {
			$actions[] = theme('action_icon', "retweet/{$status->id}", 'images/retweeted.png', 'RT');
		}
		else {
			$actions[] = theme('action_icon', "retweet/{$status->id}", '♻', 'RT');
		}
		if (user_is_current_user($from)) {
			$actions[] = theme('action_icon', "confirm/delete/{$status->id}", '🗑', 'DEL');
		}
		//Allow users to delete what they have retweeted
		if (user_is_current_user($retweeted_by)) {
			$actions[] = theme('action_icon', "confirm/delete/{$retweeted_id}", '🗑', 'DEL');
		}		
	}
	else {
		$actions[] = theme('action_icon', "confirm/deleteDM/{$status->id}", 'images/trash.gif', 'DEL');
	}
	if ($geo !== null) {
		$latlong = $geo->coordinates;
		$lat = $latlong[0];
		$long = $latlong[1];
		$actions[] = theme('action_icon', "https://maps.google.com/maps?q={$lat},{$long}", '⌖', 'MAP');
	}
	//Search for @ to a user
	$actions[] = theme('action_icon',"search?query=%40{$from}",'🔍','?');

	return '<span class="actionicons">' . implode(' ', $actions) . '</span>';
}

function theme_action_icon($url, $image_url, $text) {
	// alt attribute left off to reduce bandwidth by about 720 bytes per page
	if ($text == 'MAP')
	{
		return "<a href='$url' target='" . get_target() . "'>{$image_url}</a>";
	}

    if (0 === strpos($image_url, "images/"))
    {
        return "<a href='$url'><img src='$image_url' alt='$text' /></a>";
    }

    return "<a href='{$url}' class='action' >{$image_url}</a>";
	
}
function theme_followers_list($feed, $hide_pagination = false) {
	if(isset($feed->users))
		$users = $feed->users;
	else
		$users = $feed;
	$rows = array();
	if (count($users) == 0 || $users == '[]') return '<p>No users to display.</p>';

	foreach($users as $user) {
		if($user->user) $user = $user->user;
		$name = theme('full_name', $user);
		$tweets_per_day = twitter_tweets_per_day($user);
		$last_tweet = strtotime($user->status->created_at);
		#$vicon = ($user->verified) ? theme('action_icon', "", 'images/verified.png', '&#10004;') : '';
		$content = "{$vicon}{$name}<br /><span class='about'>";
		if($user->description != "")
			$content .= "Bio: {$user->description}<br />";
		if($user->location != "")
			$content .= "Location: {$user->location}<br />";
		$content .= "Info: ";
		$content .= pluralise('tweet', $user->statuses_count, true) . ", ";
		$content .= pluralise('friend', $user->friends_count, true) . ", ";
		$content .= pluralise('follower', $user->followers_count, true) . ", ";
		$content .= "~" . pluralise('tweet', $tweets_per_day, true) . " per day<br />";
		if($user->status->created_at) {
			$content .= "Last tweet: ";
			if($user->protected == 'true' && $last_tweet == 0)
				$content .= "Private";
			else if($last_tweet == 0)
				$content .= "Never tweeted";
			else
				$content .= twitter_date('l jS F Y', $last_tweet);
		}
		$content .= "</span>";

		$rows[] = array('data' => array(array('data' => theme('avatar', $user->profile_image_url), 'class' => 'avatar'),
		                                array('data' => $content, 'class' => 'status shift')),
		                'class' => 'tweet');

	}

	$content = theme('table', array(), $rows, array('class' => 'followers'));
	if (!$hide_pagination)
		#$content .= theme('pagination');
		$content .= theme('list_pagination', $feed);
	return $content;
}

function theme_trends_page($locales, $trends) {
	// TODO FIXME
}

function theme_css() {
	$c = theme('colours');
	return "<style type='text/css'>
	
form{margin:.3em;}

body{
	margin:0;
	font-family:sans-serif;
	background:#{$c->bodybg};
	color:#{$c->bodyt};
}

a{color:#{$c->links}}
small,small a{color:#{$c->small}}
.odd{background:#{$c->odd}}
.even{background:#{$c->even}}
.reply{background:#{$c->replyodd}}
.reply.even{background:#{$c->replyeven}}
.menu{color:#{$c->menut};background:#{$c->menubg};padding: 2px}
.menu a{color:#{$c->menua};text-decoration: none}
.tweet,.features{padding-top:5px;padding-bottom:5px;}

.timeline a img{
	padding:2px;
}

.avatar{
	left:5px;
	margin-top:1px;
	position:absolute;
}

.shift{
}

.status {
	display:block;
	word-wrap:break-word;
	margin-left:58px;
	min-height:50px;
}

.embed{
	margin:-55px;
	overflow-x:auto;
	clear:both;
}

.date{padding:5px;font-size:0.8em;font-weight:bold;color:#{$c->small}}
.about,.time{font-size:0.75em;color:#{$c->small}}
.from{font-size:0.75em;color:#{$c->small};font-family:serif;}.
from a{color:#{$c->small};}
</style>";
}

function theme_google_analytics() {
	global $GA_ACCOUNT;
	if (!$GA_ACCOUNT) return '';
	$googleAnalyticsImageUrl = googleAnalyticsGetImageUrl();
	return "<img src='{$googleAnalyticsImageUrl}' />";
}
