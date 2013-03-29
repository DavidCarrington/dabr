<?php
require 'Autolink.php';
require 'Extractor.php';
require 'Embedly.php';
require 'Emoticons.php';
		
menu_register(array(
	'' => array(
		'callback' => 'twitter_home_page',
		'accesskey' => '0',
	),
	'status' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_status_page',
	),
	'update' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_update',
	),
	'twitter-retweet' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_retweet',
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
	'confirmed' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_confirmed_page',
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
	'spam' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_spam_page',
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
		'callback' => 'twitter_friends_page',
	),
	'delete' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_delete_page',
	),
	'deleteDM' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_deleteDM_page',
	),
	'retweet' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_retweet_page',
	),
	'hash' => array(
		'security' => true,
		'hidden' => true,
		'callback' => 'twitter_hashtag_page',
	),
	'upload-picture' => array(
		'security' => true,
		'callback' => 'twitter_media_page',
	),
	'trends' => array(
		'security' => true,
		'callback' => 'twitter_trends_page',
	),
	'retweets' => array(
		'security' => true,
		'callback' => 'twitter_retweets_page',
	),
	'find' => array(
		'security' => true,
		'callback' => 'twitter_find_user',
	),
	'retweeted_by' => array(
		'security' => true,
		'hidden' => true,
		'callback' => 'twitter_retweeters_page',
	),
	'edit-profile' => array(
		'security' => true,
		'callback' => 'twitter_profile_page',
	)
));

// How should external links be opened?
function get_target()
{
	// Kindle doesn't support opening in a new window
	if (stristr($_SERVER['HTTP_USER_AGENT'], "Kindle/"))
	{
		return "_self";
	}
	else 
	{
		return "_blank";
	}
}

//	Edit User Profile
function twitter_profile_page() {
	// process form data
	if ($_POST['name']){

		// post profile update
		$post_data = array(
			"name"			=> stripslashes($_POST['name']),
			"url"				=> stripslashes($_POST['url']),
			"location"		=> stripslashes($_POST['location']),
			"description"	=> stripslashes($_POST['description']),
		);

		$url = API_NEW."account/update_profile.json";
		$user = twitter_process($url, $post_data);
		$content = "<h2>Profile Updated</h2>";
	} 
	
	//	http://api.twitter.com/1/account/update_profile_image.format 
	if ($_FILES['image']['tmp_name']){	
		require 'tmhOAuth.php';
		
		list($oauth_token, $oauth_token_secret) = explode('|', $GLOBALS['user']['password']);
		
		$tmhOAuth = new tmhOAuth(array(
			'consumer_key'    => OAUTH_CONSUMER_KEY,
			'consumer_secret' => OAUTH_CONSUMER_SECRET,
			'user_token'      => $oauth_token,
			'user_secret'     => $oauth_token_secret,
		));

		// note the type and filename are set here as well
		$params = array(
			'image' => "@{$_FILES['image']['tmp_name']};type={$_FILES['image']['type']};filename={$_FILES['image']['name']}",
		);

		$code = $tmhOAuth->request('POST', 
											$tmhOAuth->url("1/account/update_profile_image"),
											$params,
											true, // use auth
											true // multipart
		);


		if ($code == 200) {
			$content = "<h2>Avatar Updated</h2>";			
		} else {
			$content = "Damn! Something went wrong. Sorry :-("  
				."<br /> code="	. $code
				."<br /> status="	. $status
				."<br /> image="	. $image
				//."<br /> response=<pre>"
				//. print_r($tmhOAuth->response['response'], TRUE)
				. "</pre><br /> info=<pre>"
				. print_r($tmhOAuth->response['info'], TRUE)
				. "</pre><br /> code=<pre>"
				. print_r($tmhOAuth->response['code'], TRUE) . "</pre>";
		}
	}
	
	// Twitter API is really slow!  If there's no delay, the old profile is returned.
	//	Wait for 5 seconds before getting the user's information, which seems to be sufficient
	sleep(5);

	// retrieve profile information
	$user = twitter_user_info(user_current_username());

	$content .= theme('user_header', $user);
	$content .= theme('profile_form', $user);

	theme('page', "Edit Profile", $content);
}

function theme_profile_form($user){
	// Profile form
	$out .= "
				<form name='profile' action='Edit Profile' method='post' enctype='multipart/form-data'>
					<hr />Name:			<input name='name' maxlength='20' value='"						. htmlspecialchars($user->name, ENT_QUOTES) ."' />
					<br />Avatar:		<img src='".theme_get_avatar($user)."' /> <input type='file' name='image' />
					<br />Bio:			<input name='description' size=40 maxlength='160' value='"	. htmlspecialchars($user->description, ENT_QUOTES) ."' />
					<br />Link:			<input name='url' maxlength='100' size=40 value='"				. htmlspecialchars($user->url, ENT_QUOTES) ."' />
					<br />Location:	<input name='location' maxlength='30' value='"					. htmlspecialchars($user->location, ENT_QUOTES) ."' />
					<br /><input type='submit' value='Update Profile' />
				</form>";

	return $out;
}

function long_url($shortURL)
{
	if (!defined('LONGURL_KEY'))
	{
		return $shortURL;
	}
	$url = "http://www.longurlplease.com/api/v1.1?q=" . $shortURL;
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($curl_handle,CURLOPT_URL,$url);
	$url_json = curl_exec($curl_handle);
	curl_close($curl_handle);

	$url_array = json_decode($url_json,true);

	$url_long = $url_array["$shortURL"];

	if ($url_long == null)
	{
		return $shortURL;
	}

	return $url_long;
}


function friendship_exists($user_a) {
	$request = API_NEW.'friendships/show.json?target_screen_name=' . $user_a;
	$following = twitter_process($request);

	if ($following->relationship->target->following == 1) {
		return true;
	} else {
		return false;
	}
}

function friendship($user_a) {
	$request = API_NEW.'friendships/show.json?target_screen_name=' . $user_a;
	return twitter_process($request);
}

function twitter_block_exists($query) {
	//Get an array of all ids the authenticated user is blocking (limited at 5000 without cursoring)
	$request = API_NEW.'blocks/ids.json';
	$response = twitter_process($request);
	$blocked = $response->ids;
	//If the authenticate user has blocked $query it will appear in the array
	return in_array($query,$blocked);
}

function twitter_trends_page($query) {
	$woeid = $_GET['woeid'];
	if(isset($woeid)) {
		$duration = time() + (3600 * 24 * 365);
		setcookie('woeid', $woeid, $duration, '/');
	}
	else {
		$woeid = $_COOKIE['woeid'];
	}

	if($woeid == '') $woeid = '1'; //worldwide

	//fetch "local" names
	$request = API_NEW.'trends/available.json';
	$local = twitter_process($request);
	$header = '<form method="get" action="trends"><select name="woeid">';
	$header .= '<option value="1"' . (($woeid == 1) ? ' selected="selected"' : '') . '>Worldwide</option>';

	//sort the output, going for Country with Towns as children
	foreach($local as $key => $row) {
		$c[$key] = $row->country;
		$t[$key] = $row->placeType->code;
		$n[$key] = $row->name;
	}
	array_multisort($c, SORT_ASC, $t, SORT_DESC, $n, SORT_ASC, $local);

	foreach($local as $l) {
		if($l->woeid != 1) {
			$n = $l->name;
			if($l->placeType->code != 12) $n = '-' . $n;
			$header .= '<option value="' . $l->woeid . '"' . (($l->woeid == $woeid) ? ' selected="selected"' : '') . '>' . $n . '</option>';
		}
	}
	$header .= '</select> <input type="submit" value="Go" /></form>';
	
	$request = API_NEW.'trends/place.json?id=' . $woeid;
	$trends = twitter_process($request);
	$search_url = 'search?query=';
	foreach($trends[0]->trends as $trend) {
		$row = array("<strong><a href='{$search_url}{$trend->query}'>{$trend->name}</a></strong>");
		$rows[] = array('data' => $row,  'class' => 'tweet');
	}
	$headers = array($header);
	$content = theme('table', $headers, $rows, array('class' => 'timeline'));
	theme('page', 'Trends', $content);
}

function js_counter($name, $length='140')
{
	$script = '<script type="text/javascript">
function updateCount() {
var remaining = ' . $length . ' - document.getElementById("' . $name . '").value.length;
document.getElementById("remaining").innerHTML = remaining;
if(remaining < 0) {
 var colour = "#FF0000";
 var weight = "bold";
} else {
 var colour = "";
 var weight = "";
}
document.getElementById("remaining").style.color = colour;
document.getElementById("remaining").style.fontWeight = weight;
setTimeout(updateCount, 400);
}
updateCount();
</script>';
	return $script;
}

function twitter_media_page($query) 
{
	$content = "";
	$status = stripslashes($_POST['message']);
	
	if ($_POST['message'] && $_FILES['image']['tmp_name']) 
	{
		require 'tmhOAuth.php';
		
		// Geolocation parameters
		list($lat, $long) = explode(',', $_POST['location']);
		if (is_numeric($lat) && is_numeric($long)) {
			$post_data['lat'] = $lat;
			$post_data['long'] = $long;	
		}
		
		list($oauth_token, $oauth_token_secret) = explode('|', $GLOBALS['user']['password']);
		
		$tmhOAuth = new tmhOAuth(array(
			'consumer_key'    => OAUTH_CONSUMER_KEY,
			'consumer_secret' => OAUTH_CONSUMER_SECRET,
			'user_token'      => $oauth_token,
			'user_secret'     => $oauth_token_secret,
		));

		$image = "{$_FILES['image']['tmp_name']};type={$_FILES['image']['type']};filename={$_FILES['image']['name']}";

		$code = $tmhOAuth->request('POST', 'https://upload.twitter.com/1/statuses/update_with_media.json',
											  array(
												 'media[]'  => "@{$image}",
												 'status'   => " " . $status, //A space is needed because twitter b0rks if first char is an @
												 'lat'		=> $lat,
												 'long'		=> $long,
											  ),
											  true, // use auth
											  true  // multipart
										);

		if ($code == 200) {
			$json = json_decode($tmhOAuth->response['response']);
			
			if ($_SERVER['HTTPS'] == "on") {
				$image_url = $json->entities->media[0]->media_url_https;
			}
			else {
				$image_url = $json->entities->media[0]->media_url;
			}

			$text = $json->text;
			
			$content = "<p>Upload success. Image posted to Twitter.</p>
							<p><img src=\"" . IMAGE_PROXY_URL . "x50/" . $image_url . "\" alt='' /></p>
							<p>". twitter_parse_tags($text) . "</p>";
			
		} else {
			$content = "Damn! Something went wrong. Sorry :-("  
				."<br /> code=" . $code
				."<br /> status=" . $status
				."<br /> image=" . $image
				."<br /> response=<pre>"
				. print_r($tmhOAuth->response['response'], TRUE)
				. "</pre><br /> info=<pre>"
				. print_r($tmhOAuth->response['info'], TRUE)
				. "</pre><br /> code=<pre>"
				. print_r($tmhOAuth->response['code'], TRUE) . "</pre>";
		}
	}
	
	if($_POST) {
		if (!$_POST['message']) {
			$content .= "<p>Please enter a message to go with your image.</p>";
		}

		if (!$_FILES['image']['tmp_name']) {
			$content .= "<p>Please select an image to upload.</p>";
		}
	}
	
	$content .=	"<form method='post' action='Upload Picture' enctype='multipart/form-data'>
						Image <input type='file' name='image' /><br />
						Message (optional):<br />
						<textarea name='message' style='width:90%; max-width: 400px;' rows='3' id='message'>" . $status . "</textarea><br>
						<input type='submit' value='Send' />
						<span id='remaining'>119</span>";
	$content .= '	<span id="geo" style="display: none;">
							<input onclick="goGeo()" type="checkbox" id="geoloc" name="location" />
							<label for="geoloc" id="lblGeo"></label>
						</span>
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
								geoStatus("Tweet my <a href=\'https://maps.google.com/maps?q=" + position.coords.latitude + "," + position.coords.longitude + "\' target=' . get_target() . '>location</a>");
								chkbox.value = position.coords.latitude + "," + position.coords.longitude;
							}
					</script>
					</form>';
	$content .= js_counter("message", "119");

	return theme('page', 'Picture Upload', $content);
}

function twitter_process($url, $post_data = false) {
	if ($post_data === true) {
		$post_data = array();
	}

	$status = $post_data['status'];
	user_oauth_sign($url, $post_data);
	$api_start = microtime(1);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);

	if($post_data !== false && !$_GET['page']) {
		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
	}

	//from  http://github.com/abraham/twitteroauth/blob/master/twitteroauth/twitteroauth.php
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

	$response = curl_exec($ch);
	$response_info = curl_getinfo($ch);
	$erno = curl_errno($ch);
	$er = curl_error($ch);
	curl_close($ch);

	global $api_time;
	global $rate_limit;

	//	Split that headers and the body
	list($headers, $body) = explode("\r\n\r\n", $response, 2);

	//	Place the headers into an array
	$headers = explode("\n", $headers);
	foreach ($headers as $header) {
		list($key, $value) = explode(':', $header, 2);
		$headers_array[$key] = $value;
	}

	//	Not every request is rate limited
	if ($headers_array['x-rate-limit-limit']) {
		$current_time = time();
		$ratelimit_time = $headers_array['x-rate-limit-reset'];
		$time_until_reset = $ratelimit_time - $current_time;
		$minutes_until_reset = round($time_until_reset / 60);
		$rate_limit .= " Rate Limit: " . $headers_array['x-rate-limit-remaining'] . " out of " . $headers_array['x-rate-limit-limit'] . " calls remaining for the next {$minutes_until_reset} minutes";
	}

	$api_time += microtime(1) - $api_start;

	switch( intval( $response_info['http_code'] ) )	{
		case 200:
		case 201:
			$json = json_decode($body);
			if ($json) {
				return $json;
			}
			return $body;
		case 401:
			user_logout();
			theme('error', "<p>Error: Login credentials incorrect.</p><p>{$response_info['http_code']}: {$result}</p><hr><p>$url</p>");
		case 429:
			theme('error', "<h2>Rate limit exceeded!</h2><p>All {$headers_array['x-rate-limit-limit']} calls used, next reset in {$minutes_until_reset} minutes.</p>");
		case 0:
			$result = $erno . ":" . $er . "<br />" ;
			/*
			foreach ($response_info as $key => $value) {
				$result .= "Key: $key; Value: $value<br />";
			}
			*/
			theme('error', "<h2>Twitter timed out</h2><p>Dabr gave up on waiting for Twitter to respond. They're probably overloaded right now, try again in a minute. <br />{$result}</p>");
		default:
			$result = json_decode($body);
			$result = $result->error ? $result->error : $body;
			if (strlen($result) > 500) {
				$result = "Something broke on Twitter's end.";
			/*
			foreach ($response_info as $key => $value) {
				$result .= "Key: $key; Value: $value<br />";
			}
			*/	
			}
			else if ($result == "Status is over 140 characters.") {
				theme('error', "<h2>Status was tooooooo loooooong!</h2><p>{$status}</p><hr>");	
				//theme('status_form',$status);
			}
			if(DEBUG_MODE == 'ON') {
				theme('error', "<h2>An error occured while calling the Twitter API</h2><p>{$response_info['http_code']}: {$result}<br />{$url}</p><hr>");
			}
			else {
				theme('error', "<h2>An error occured while calling the Twitter API</h2><p>{$response_info['http_code']}: {$result}</p><hr>");
			}
	}
}

function twitter_fetch($url) {
	global $services_time;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$user_agent = "Mozilla/5.0 (compatible; dabr; " . BASE_URL . ")";
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$fetch_start = microtime(1);
	$response = curl_exec($ch);
	curl_close($ch);
	
	$services_time += microtime(1) - $fetch_start;
	return $response;
}

//	http://dev.twitter.com/pages/tweet_entities
function twitter_get_media($status) {
	if($status->entities->media) {
		
		$media_html = '';
		
		foreach($status->entities->media as $media) {
	
			if ($_SERVER['HTTPS'] == "on") {
				$image = $media->media_url_https;
			} else {
				$image = $media->media_url;
			}
			
			$link = $media->url;

			$width = $media->sizes->thumb->w;
			$height = $media->sizes->thumb->h;

			$media_html .= "<a href=\"" . IMAGE_PROXY_URL . $image . "\" target=\"" . get_target() . "\" >";
			$media_html .= 	"<img src=\"{$image}:thumb\" width=\"{$width}\" height=\"{$height}\" >";
			$media_html .= "</a>";
		}
	
		return $media_html . "<br/>";
	}	
}

function twitter_parse_tags($input, $entities = false) {
	$out = $input;

	//Linebreaks.  Some clients insert \n for formatting.
	$out = nl2br($out);
	
	// Use the Entities to replace hyperlink URLs
	// http://dev.twitter.com/pages/tweet_entities
	if($entities) {
		if($entities->urls) {
			foreach($entities->urls as $urls) {
				if($urls->expanded_url != "") {
					$display_url = $urls->expanded_url;
				}
				else {
					$display_url = $urls->url;
				}
				
				$url = $urls->url;
				$parsed_url = parse_url($url);
				
				if (empty($parsed_url['scheme'])) {
					$url = 'http://' . $url;
				}

				if (setting_fetch('gwt') == 'on') { // If the user wants links to go via GWT 
					$encoded = urlencode($url);
					$link = "http://google.com/gwt/n?u={$encoded}";
				}
				else {
					$link = $url;
				}
			
				$link_html = '<a href="' . $link . '" target="' . get_target() . '">' . $display_url . '</a>';
				$url = $urls->url;
			
				// Replace all URLs *UNLESS* they have already been linked (for example to an image)
				$pattern = '#((?<!href\=(\'|\"))'.preg_quote($url,'#').')#i';
				$out = preg_replace($pattern,  $link_html, $out);
			}
		}
		
		if($entities->hashtags) {
			foreach($entities->hashtags as $hashtag) {
				$text = $hashtag->text;
				$pattern = '/(^|\s)([#＃]+)('. $text .')/iu';
				$link_html = ' <a href="hash/' . $text . '">#' . $text . '</a> ';
				$out = preg_replace($pattern,  $link_html, $out, 1);
			}
		}
		
		if($entities->media) {
			foreach($entities->media as $media) {
				$url = $media->url;
				$pattern = '#((?<!href\=(\'|\"))'.preg_quote($url,'#').')#i';
				$link_html = "<a href='{$media->url}' target='" . get_target() . "'>{$media->display_url}</a>";
				$out = preg_replace($pattern,  $link_html, $out, 1);
			}
		}
		
	}
	else {  // If Entities haven't been returned (usually because of search or a bio) use Autolink
		// Create an array containing all URLs
		$urls = Twitter_Extractor::create($input)
				->extractURLs();

		// Hyperlink the URLs 
		if (setting_fetch('gwt') == 'on') { // If the user wants links to go via GWT 
			foreach($urls as $url) {
				$encoded = urlencode($url);
				$out = str_replace($url, "<a href='http://google.com/gwt/n?u={$encoded}' target='" . get_target() . "'>{$url}</a>", $out);
			}	
		}
		else {
			$out = Twitter_Autolink::create($out)
					->addLinksToURLs();
		}	
		
		// Hyperlink the #	
		$out = Twitter_Autolink::create($out)
				->setTarget('')
				->addLinksToHashtags();
	}
	
	// Hyperlink the @ and lists
	$out = Twitter_Autolink::create($out)
			->setTarget('')
			->addLinksToUsernamesAndLists();

	// Emails
	$tok = strtok($out, " \n\t\n\r\0");	// Tokenise the string by whitespace

	while ($tok !== false) {	// Go through all the tokens
		$at = stripos($tok, "@");	// Does the string contain an "@"?

		if ($at && $at > 0) { // @ is in the string & isn't the first character
			$tok = trim($tok, "?.,!\"\'");	// Remove any trailing punctuation
			
			if (filter_var($tok, FILTER_VALIDATE_EMAIL)) {	// Use the internal PHP email validator
				$email = $tok;
				$out = str_replace($email, "<a href=\"mailto:{$email}\">{$email}</a>", $out);	// Create the mailto: link
			}
		}
		$tok = strtok(" \n\t\n\r\0");	// Move to the next token
	}

	//	Add Emoticons :-)
	if (setting_fetch('emoticons') != 'off') {
		$out = emoticons($out);
	}

	//Return the completed string
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



function format_interval($timestamp, $granularity = 2) {
	$units = array(
	'year' => 31536000,
	'day'  => 86400,
	'hour' => 3600,
	'min'  => 60,
	'sec'  => 1
	);
	$output = '';
	foreach ($units as $key => $value) {
		if ($timestamp >= $value) {
			$output .= ($output ? ' ' : ''). pluralise($key, floor($timestamp / $value), true);
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
		$request = API_NEW."statuses/show.json?id={$id}";
		$status = twitter_process($request);
		$text = $status->text;	//	Grab the text before it gets formatted

		$content = theme('status', $status);

		//	Show a link to the original tweet		
		$screen_name = $status->from->screen_name;
		$content .= '<p><a href="https://mobile.twitter.com/' . $screen_name . '/status/' . $id . '" target="'. get_target() . '">View orginal tweet on Twitter</a> | ';
		
		//	Translate the tweet
		$content .= '<a href="http://translate.google.com/m?hl=en&sl=auto&ie=UTF-8&q=' . urlencode($text) . '" target="'. get_target() . '">Translate this tweet</a></p>';
		
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
	$request = "https://search.twitter.com/search/thread/{$thread_id}";
	$tl = twitter_standard_timeline(twitter_fetch($request), 'thread');
	return $tl;
}

function twitter_retweet_page($query) {
	$id = (string) $query[1];
	if (is_numeric($id)) {
		$request = API_NEW."statuses/show.json?id={$id}";
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
		$request = API_NEW."statuses/destroy/{$id}.json";
		$tl = twitter_process($request, true);
		twitter_refresh('user/'.user_current_username());
	}
}

function twitter_deleteDM_page($query) {
	//Deletes a DM
	twitter_ensure_post_action();

	$id = (string) $query[1];
	if (is_numeric($id)) {
		$request = API_NEW."direct_messages/destroy.json?id={$id}";
		twitter_process($request, true);
		twitter_refresh('directs/');
	}
}

function twitter_ensure_post_action() {
	// This function is used to make sure the user submitted their action as an HTTP POST request
	// It slightly increases security for actions such as Delete, Block and Spam
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		die('Error: Invalid HTTP request method for this action.');
	}
}

function twitter_follow_page($query) {
	$user = $query[1];
	if ($user) {
		if($query[0] == 'follow'){
			$request = API_NEW."friendships/create.json?screen_name={$user}";
		} else {
			$request = API_NEW."friendships/destroy.json?screen_name={$user}";
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
			$request = API_NEW."blocks/create.json?screen_name={$user}";
			twitter_process($request, true);
			twitter_refresh("confirmed/block/{$user}");
		} else {
			$request = API_NEW."blocks/destroy.json?screen_name={$user}";
			twitter_process($request, true);
			twitter_refresh("confirmed/unblock/{$user}");
		}
	}
}

function twitter_spam_page($query)
{
	//http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-report_spam
	//We need to post this data
	twitter_ensure_post_action();
	$user = $query[1];

	//The data we need to post
	$post_data = array("screen_name" => $user);

	$request = API_NEW."users/report_spam.json";
	twitter_process($request, $post_data);

	//Where should we return the user to?  Back to the user
	twitter_refresh("confirmed/spam/{$user}");
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

		case 'deleteDM':
			$content = '<p>Are you really sure you want to delete that DM?</p>';
			$content .= "<ul><li>Tweet ID: <strong>$target</strong></li><li>There is no way to undo this action.</li><li>The DM will be deleted from both the sender's outbox <em>and</em> receiver's inbox.</li></ul>";
			break;

		case 'spam':
			$content  = "<p>Are you really sure you want to report <strong>$target</strong> as a spammer?</p>";
			$content .= "<p>They will also be blocked from following you.</p>";
			break;

	}
	$content .= "<form action='$action/$target' method='post'>
						<input type='submit' value='Yes please' />
					</form>";
	theme('Page', 'Confirm', $content);
}

function twitter_confirmed_page($query)
{
        // the URL /confirm can be passed parameters like so /confirm/param1/param2/param3 etc.
        $action = $query[1]; // The action. block, unblock, spam
        $target = $query[2]; // The username of the target
	
	switch ($action) {
                case 'block':
			$content  = "<p><span class='avatar'><img src='images/dabr.png' width='48' height='48' /></span><span class='status shift'>Bye-bye @$target - you are now <strong>blocked</strong>.</span></p>";
                        break;
                case 'unblock':
                        $content  = "<p><span class='avatar'><img src='images/dabr.png' width='48' height='48' /></span><span class='status shift'>Hello again @$target - you have been <strong>unblocked</strong>.</span></p>";
                        break;
                case 'spam':
                        $content = "<p><span class='avatar'><img src='images/dabr.png' width='48' height='48' /></span><span class='status shift'>Yum! Yum! Yum! Delicious spam! Goodbye @$target.</span></p>";
                        break;
	}
 	theme ('Page', 'Confirmed', $content);
}

function twitter_friends_page($query) {
	$user = $query[1];
	if (!$user) {
		user_ensure_authenticated();
		$user = user_current_username();
	}
	$cursor = $_GET['cursor'];
	if (!is_numeric($cursor)) {
		$cursor = -1;
	}	
	$request = API_NEW."friends/list.json?screen_name={$user}&cursor={$cursor}";
	$tl = twitter_process($request);
	$content = theme('followers_list', $tl);
	theme('page', 'Friends', $content);
}

function twitter_followers_page($query) {
	$user = $query[1];
	if (!$user) {
		user_ensure_authenticated();
		$user = user_current_username();
	}
	$cursor = $_GET['cursor'];
	if (!is_numeric($cursor)) {
		$cursor = -1;
	}	
	$request = API_NEW."followers/list.json?screen_name={$user}&cursor={$cursor}";
	$tl = twitter_process($request);
	$content = theme('followers_list', $tl);
	theme('page', 'Followers', $content);
}

//  Shows first 100 users who retweeted a specific status (limit defined by twitter)
function twitter_retweeters_page($query) {
	// Which tweet are we looking for?
	$id = $query[1];

	// Get all the user ID of the friends	
	$request = API_NEW."statuses/retweets/{$id}.json";
	$users = twitter_process($request);

	// Format the output
	$content = theme('followers_list', $users);
	theme('page', "Everyone who retweeted {$id}", $content);
}

function twitter_update() {
	twitter_ensure_post_action();
	$status = stripslashes(trim($_POST['status']));
	if ($status) {
		$request = API_NEW.'statuses/update.json';
		$post_data = array('source' => 'dabr', 'status' => $status);
		$in_reply_to_id = (string) $_POST['in_reply_to_id'];
		if (is_numeric($in_reply_to_id)) {
			$post_data['in_reply_to_status_id'] = $in_reply_to_id;
		}
		// Geolocation parameters
		list($lat, $long) = explode(',', $_POST['location']);
		$geo = 'N';
		if (is_numeric($lat) && is_numeric($long)) {
			$geo = 'Y';
			$post_data['lat'] = $lat;
			$post_data['long'] = $long;
			// $post_data['display_coordinates'] = 'false';
	  		
  			// Turns out, we don't need to manually send a place ID
/*	  		$place_id = twitter_get_place($lat, $long);
	  		if ($place_id) {
	  		
	  			// $post_data['place_id'] = $place_id;
	  		}
*/	  		
		}
		setcookie_year('geo', $geo);
		$b = twitter_process($request, $post_data);
	}
	twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_get_place($lat, $long) {
	//	http://dev.twitter.com/doc/get/geo/reverse_geocode
	//	http://api.twitter.com/version/geo/reverse_geocode.format 
	
	//	This will look up a place ID based on lat / long.
	//	Not needed (Twitter include it automagically
	//	Left in just incase we ever need it...
	$request = API_OLD.'geo/reverse_geocode.json';
	$request .= '?lat='.$lat.'&long='.$long.'&max_results=1';
	
	$locations = twitter_process($request);
	$places = $locations->result->places;
	foreach($places as $place)
	{
		if ($place->id) 
		{
			return $place->id;
		}
	}
	return false;
}

function twitter_retweet($query) {
	twitter_ensure_post_action();
	$id = $query[1];
	if (is_numeric($id)) {
		$request = API_NEW."statuses/retweet/{$id}.json";
		twitter_process($request, true);
	}
	twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_replies_page() {
	$per_page = setting_fetch('perPage', 20);	
	$request = API_NEW."statuses/mentions_timeline.json?count={$per_page}";
	if ($_GET['max_id']) {
		$request .= '&max_id='.$_GET['max_id'];
	}
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl, 'replies');
	$content = theme('status_form');
	$content .= theme('timeline', $tl);
	theme('page', 'Replies', $content);
}

function twitter_retweets_page() {
	$per_page = setting_fetch('perPage', 20);
	$request = API_NEW."statuses/retweets_of_me.json?count={$per_page}";
	if ($_GET['max_id']) {
		$request .= '&max_id='.$_GET['max_id'];
	}
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl, 'retweets');
	$content = theme('status_form');
	$content .= theme('timeline',$tl);
	theme('page', 'Retweets', $content);
}

function twitter_directs_page($query) {
	$per_page = setting_fetch('perPage', 20);
	
	$action = strtolower(trim($query[1]));
	switch ($action) {
		case 'create':
			$to = $query[2];
			$content = theme('directs_form', $to);
			theme('page', 'Create DM', $content);

		case 'send':
			twitter_ensure_post_action();
			$to = trim(stripslashes(str_replace('@','',$_POST['to'])));
			$message = trim(stripslashes($_POST['message']));
			$request = API_NEW.'direct_messages/new.json';
			twitter_process($request, array('screen_name' => $to, 'text' => $message));
			twitter_refresh('directs/sent');

		case 'sent':
			$request = API_NEW."direct_messages/sent.json?count={$per_page}";
			if ($_GET['max_id']) {
				$request .= '&max_id='.$_GET['max_id'];
			}
			$tl = twitter_process($request);
			$tl = twitter_standard_timeline($tl, 'directs_sent');	
			$content = theme_directs_menu();
			$content .= theme('timeline', $tl);
			theme('page', 'DM Sent', $content);

		case 'inbox':
		default:
			$request = API_NEW."direct_messages.json?count={$per_page}";
			if ($_GET['max_id']) {
				$request .= '&max_id='.$_GET['max_id'];
			}
			$tl = twitter_process($request);
			$tl = twitter_standard_timeline($tl, 'directs_inbox');	
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
	$content = "<form action='directs/send' method='post'>$html_to<br><textarea name='message' style='width:90%; max-width: 400px;' rows='3' id='message'></textarea><br><input type='submit' value='Send'><span id='remaining'>140</span></form>";
	$content .= js_counter("message");
	return $content;
}

function twitter_search_page() {
	$search_query = $_GET['query'];
	
	// Geolocation parameters
	list($lat, $long) = explode(',', $_GET['location']);
	$loc = $_GET['location'];
	$radius = $_GET['radius'];
	//echo "the lat = $lat, and long = $long, and $loc";
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
		$tl = twitter_search($search_query, $lat, $long, $radius);
		if ($search_query !== $_COOKIE['search_favourite']) {
			$content .= '<form action="search/bookmark" method="post"><input type="hidden" name="query" value="'.$search_query.'" /><input type="submit" value="Save as default search" /></form>';
		}
		$content .= theme('timeline', $tl);
	}
	theme('page', 'Search', $content);
}

function twitter_search($search_query, $lat = NULL, $long = NULL, $radius = NULL) {
	$per_page = setting_fetch('perPage', 20);
	$request = API_NEW."search/tweets.json?result_type=recent&q={$search_query}&rpp={$per_page}";
	if ($_GET['max_id']) {
		$request .= '&max_id='.$_GET['max_id'];
	}
	if ($lat && $long) {
		$request .= "&geocode=$lat,$long,";
		if ($radius) {
			$request .= "$radius";
		}
		else {
			$request .= "1km";
		}
	}
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl->statuses, 'search');
	return $tl;
}

function twitter_find_tweet_in_timeline($tweet_id, $tl) {
	// Parameter checks
	if (!is_numeric($tweet_id) || !$tl) return;

	// Check if the tweet exists in the timeline given
	if (array_key_exists($tweet_id, $tl)) {
		// Found the tweet
		$tweet = $tl[$tweet_id];
	} else {
		// Not found, fetch it specifically from the API
		$request = API_NEW."statuses/show.json?id={$tweet_id}";
		$tweet = twitter_process($request);
	}
	return $tweet;
}

function twitter_user_page($query) {
	$screen_name = $query[1];
	$subaction = $query[2];
	$in_reply_to_id = (string) $query[3];
	$content = '';

	if (!$screen_name) theme('error', 'No username given');

	// Load up user profile information and one tweet
	$user = twitter_user_info($screen_name);

	// If the user has at least one tweet
	if (isset($user->status)) {
		// Fetch the timeline early, so we can try find the tweet they're replying to
		$request = API_NEW."statuses/user_timeline.json?screen_name={$screen_name}";
		if ($_GET['max_id']) {
			$request .= '&max_id='.$_GET['max_id'];
		}
		$tl = twitter_process($request);
		$tl = twitter_standard_timeline($tl, 'user');
	}

	// Build an array of people we're talking to
	$to_users = array($user->screen_name);

	// Build an array of hashtags being used
	$hashtags = array();

	// Are we replying to anyone?
	if (is_numeric($in_reply_to_id)) {
		$tweet = twitter_find_tweet_in_timeline($in_reply_to_id, $tl);
		
		$out = twitter_parse_tags($tweet->text);

		$content .= "<p>In reply to:<br />{$out}</p>";

		if ($subaction == 'replyall') {
			$found = Twitter_Extractor::create($tweet->text)
				->extractMentionedUsernames();
			$to_users = array_unique(array_merge($to_users, $found));
		}
				
		if ($tweet->entities->hashtags) {
			$hashtags = $tweet->entities->hashtags;
		}		
	}

	// Build a status message to everyone we're talking to
	$status = '';
	foreach ($to_users as $username) {
		if (!user_is_current_user($username)) {
			$status .= "@{$username} ";
		}
	}

	// Add in the hashtags they've used
	foreach ($hashtags as $hashtag) {
		$status .= "#{$hashtag->text} ";
	}

	$content .= theme('status_form', $status, $in_reply_to_id);
	$content .= theme('user_header', $user);
	$content .= theme('timeline', $tl);

	theme('page', "User {$screen_name}", $content);
}

function twitter_favourites_page($query) {
	$screen_name = $query[1];
	if (!$screen_name) {
		user_ensure_authenticated();
		$screen_name = user_current_username();
	}
	$request = API_NEW."favorites/list.json?screen_name={$screen_name}";
	if ($_GET['max_id']) {
		$request .= '&max_id=' . $_GET['max_id'];
	}
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
		$request = API_NEW."favorites/destroy.json?id={$id}";
	}
	else {
		$request = API_NEW."favorites/create.json?id={$id}";
	}
	twitter_process($request, true);
	twitter_refresh();
}

function twitter_home_page() {
	user_ensure_authenticated();
	$per_page = setting_fetch('perPage', 20);
	$request = API_NEW."statuses/home_timeline.json?count={$per_page}";
	if ($_GET['max_id']) {
		$request .= '&max_id='.$_GET['max_id'];
	}
	if ($_GET['since_id']) {
		$request .= '&since_id='.$_GET['since_id'];
	}
	//echo $request;
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
		$icon = "images/twitter-bird-16x16.png";

		//	adding ?status=foo will automaticall add "foo" to the text area.
		if ($_GET['status'])
		{
			$text = $_GET['status'];
		}
		
		return "<fieldset><legend><img src='{$icon}' width='16' height='16' /> What's Happening?</legend><form method='post' action='update'><input name='status' value='{$text}' maxlength='140' /> <input name='in_reply_to_id' value='{$in_reply_to_id}' type='hidden' /><input type='submit' value='Tweet' /></form></fieldset>";
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

function twitter_tweets_per_day($user, $rounding = 1) {
	// Helper function to calculate an average count of tweets per day
	$days_on_twitter = (time() - strtotime($user->created_at)) / 86400;
	return round($user->statuses_count / $days_on_twitter, $rounding);
}

function theme_user_header($user) {
	$friendship = friendship($user->screen_name);
	$followed_by = $friendship->relationship->target->followed_by; //The $user is followed by the authenticating
	$following = $friendship->relationship->target->following;
	$name = theme('full_name', $user);
	$full_avatar = theme_get_full_avatar($user);
	$link = theme('external_link', $user->url);
	//Some locations have a prefix which should be removed (UbertTwitter and iPhone)
	//Sorry if my PC has converted from UTF-8 with the U (artesea)
	$cleanLocation = str_replace(array("iPhone: ","ÜT: "),"",$user->location);
	$raw_date_joined = strtotime($user->created_at);
	$date_joined = date('jS M Y', $raw_date_joined);
	$tweets_per_day = twitter_tweets_per_day($user, 1);
	$bio = twitter_parse_tags($user->description);
	$out = "<div class='profile'>";
	$out .= "<span class='avatar'>".theme('external_link', $full_avatar, theme('avatar', theme_get_avatar($user)))."</span>";
	$out .= "<span class='status shift'><b>{$name}</b><br />";
	$out .= "<span class='about'>";
	if ($user->verified == true) {
		$out .= '<strong>Verified Account</strong><br />';
	}
	if ($user->protected == true) {
		$out .= '<strong>Private/Protected Tweets</strong><br />';
	}

	$out .= "Bio: {$bio}<br />";
	$out .= "Link: {$link}<br />";
	$out .= "Location: <a href=\"https://maps.google.com/maps?q={$cleanLocation}\" target=\"" . get_target() . "\">{$user->location}</a><br />";
	$out .= "Joined: {$date_joined} (~" . pluralise('tweet', $tweets_per_day, true) . " per day)";
	$out .= "</span></span>";
	$out .= "<div class='features'>";
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
	$size = $force_large ? 48 : 24;
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

function twitter_date($format, $timestamp = null) {
/*
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
*/
	$offset = setting_fetch('utc_offset', 0) * 3600;
	if (!isset($timestamp)) {
		$timestamp = time();
	}
	return gmdate($format, $timestamp + $offset);
}

function twitter_standard_timeline($feed, $source) {
	$output = array();
	if (!is_array($feed) && $source != 'thread') return $output;
	
	//32bit int / snowflake patch
	if (is_array($feed)) {
		foreach($feed as $key => $status) {
			if($status->id_str) {
				$feed[$key]->id = $status->id_str;
			}
			if($status->in_reply_to_status_id_str) {
				$feed[$key]->in_reply_to_status_id = $status->in_reply_to_status_id_str;
			}
			if($status->retweeted_status->id_str) {
				$feed[$key]->retweeted_status->id = $status->retweeted_status->id_str;
			}
		}
	}
	
	switch ($source) {
		case 'status':
		case 'favourites':
		case 'friends':
		case 'replies':
		case 'retweets':
		case 'user':
		case 'search':
			foreach ($feed as $status) {
				$new = $status;
				if ($new->retweeted_status) {
					$retweet = $new->retweeted_status;
					unset($new->retweeted_status);
					$retweet->retweeted_by = $new;
					$retweet->original_id = $new->id;
					$new = $retweet;
				}
				$new->from = $new->user;
				unset($new->user);
				$output[(string) $new->id] = $new;
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
				$output[$new->id_str] = $new;
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
	$request = API_NEW."users/show.json?screen_name={$username}";
	$user = twitter_process($request);
	return $user;
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
	if (!in_array(setting_fetch('browser'), array('text', 'worksafe')))	{
		if (EMBEDLY_KEY !== '')	{
			embedly_embed_thumbnails($feed);
		}
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
			$retweeted = "<br /><small>" . theme('action_icon', "retweeted_by/{$status->id}", 'images/retweet.png', 'RT') . "retweeted by <a href='user/{$retweeted_by}'>{$retweeted_by}</a></small>";
			//$source .= "<br /><a href='retweeted_by/{$status->id}'>retweeted</a> by <a href='user/{$retweeted_by}'>{$retweeted_by}</a>";
		}
		if($status->favorite_count) {
			$source .= ', favourited ' . x_times($status->favorite_count);
		}
		//$html = "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link<br />{$text}<br />$media<small>$source</small>";
		$html = "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link{$retweeted}<br />{$text}<br />$media<span class='from'>$source</span>";

		unset($row);
		$class = 'status';
		
		if ($page != 'user' && $avatar)	{
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

function twitter_is_reply($status) {
	if (!user_is_authenticated()) {
		return false;
	}
	$user = user_current_username();

	//	Use Twitter Entities to see if this contains a mention of the user
	if ($status->entities)	// If there are entities
	{
		if ($status->entities->user_mentions)
		{
			$entities = $status->entities;
			
			foreach($entities->user_mentions as $mentions)
			{
				if ($mentions->screen_name == $user) 
				{
					return true;
				}
			}
		}
		return false;
	}
	
	// If there are no entities (for example on a search) do a simple regex
	$found = Twitter_Extractor::create($status->text)->extractMentionedUsernames();
	foreach($found as $mentions)
	{
		// Case insensitive compare
		if (strcasecmp($mentions, $user) == 0)
		{
			return true;
		}
	}
	return false;
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
	if ($_SERVER['HTTPS'] == "on" && $object->profile_image_url_https) {
		return IMAGE_PROXY_URL . "48/48/" . $object->profile_image_url_https;
	}
	else {
		return IMAGE_PROXY_URL . "48/48/" . $object->profile_image_url;
	}
}

function theme_get_full_avatar($object) {
	if ($_SERVER['HTTPS'] == "on" && $object->profile_image_url_https) {
		return IMAGE_PROXY_URL . str_replace('_normal.', '.', $object->profile_image_url_https);
	}
	else {
		return IMAGE_PROXY_URL . str_replace('_normal.', '.', $object->profile_image_url);
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
	//Long URL functionality.  Also uncomment function long_url($shortURL)
	if (!$content)
	{
		//Used to wordwrap long URLs
		//return "<a href='$url' target='_blank'>". wordwrap(long_url($url), 64, "\n", true) ."</a>";
		return "<a href='$url' target='" . get_target() . "'>". long_url($url) ."</a>";
	}
	else
	{
		return "<a href='$url' target='" . get_target() . "'>$content</a>";
	}

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
		$actions[] = theme('action_icon', "user/{$from}/reply/{$status->id}", 'images/reply.png', '@');
	}
	//Reply All functionality.
	if( $status->entities->user_mentions ) {
		$actions[] = theme('action_icon', "user/{$from}/replyall/{$status->id}", 'images/replyall.png', 'REPLY ALL');
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
		// Show a diffrent retweet icon to indicate to the user this is an RT
		if ($status->retweeted || user_is_current_user($retweeted_by)) {
			$actions[] = theme('action_icon', "retweet/{$status->id}", 'images/retweeted.png', 'RT');
		}
		else {
			$actions[] = theme('action_icon', "retweet/{$status->id}", 'images/retweet.png', 'RT');
		}
		if (user_is_current_user($from)) {
			$actions[] = theme('action_icon', "confirm/delete/{$status->id}", 'images/trash.gif', 'DEL');
		}
		//Allow users to delete what they have retweeted
		if (user_is_current_user($retweeted_by)) {
			$actions[] = theme('action_icon', "confirm/delete/{$retweeted_id}", 'images/trash.gif', 'DEL');
		}		
	}
	else {
		$actions[] = theme('action_icon', "confirm/deleteDM/{$status->id}", 'images/trash.gif', 'DEL');
	}
	if ($geo !== null) {
		$latlong = $geo->coordinates;
		$lat = $latlong[0];
		$long = $latlong[1];
		$actions[] = theme('action_icon', "https://maps.google.com/maps?q={$lat},{$long}", 'images/map.png', 'MAP');
	}
	//Search for @ to a user
	$actions[] = theme('action_icon',"search?query=%40{$from}",'images/q.png','?');

	return implode(' ', $actions);
}

function theme_action_icon($url, $image_url, $text) {
	// alt attribute left off to reduce bandwidth by about 720 bytes per page
	if ($text == 'MAP')
	{
		return "<a href='$url' alt='$text' target='" . get_target() . "'><img src='$image_url' /></a>";
	}

	return "<a href='$url'><img src='$image_url' alt='$text' /></a>";
}

function pluralise($word, $count, $show = FALSE) {
	if($show) $word = number_format($count) . " {$word}";
	return $word . (($count != 1) ? 's' : '');
}

function is_64bit() {
	$int = "9223372036854775807";
	$int = intval($int);
	return ($int == 9223372036854775807);
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

function twitter_find_user() {
#/1.1/ possibility to add the option to paginate via page=2 on these
	$name = $_GET['name'];
	if(strpos($name, '@') === 0) {
		twitter_refresh('user/' . str_replace('@','',$name));
	}
	$output = '';
	$output = '<form method="get" action="find">Find: <input name="name" id="name" value="'.urldecode($name).'" /><input type="submit" value="Go" /></form>';
	if($name) {
		$request = API_NEW."users/search.json?q=" . urlencode($name);
		$users = twitter_process($request);
	}
	//ELSE RUNS ON /1/ API, THERE ARE NO PLANS BY TWITTER TO MAKE THIS UNPUBLISHED API CALL WORK WITH /1.1/ :(
	else {
		$request = API_OLD."users/recommendations.json?expanded_results_format=1&cursor=-1&pc=true&display_location=wtf-view-all-stream&personalized=false&force_bq=false&algorithm=&connections=true";
		$users = twitter_process($request);
		$output .= "<div class='heading'>Recommend for you by Twitter:</div>\n";
	}
	$output .= theme_followers_list($users, true);
	theme('page', 'Find', $output);
}

function x_times($count) {
	if($count == 1) return 'once';
	if($count == 2) return 'twice';
	if(is_int($count)) return number_format($count) . ' times';
	return $count . ' times';
}

?>
