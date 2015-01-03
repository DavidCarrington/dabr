<?php

/*
Assembled in theme_css()
Syntax is 
'Name|links,bodybg,bodyt,small,odd,even,replyodd,replyeven,menubg,menut,menua',
*/

$GLOBALS['colour_schemes'] = array(
	0 => 'Pretty In Pink|c06,fcd,623,c8a,fee,fde,ffa,dd9,c06,fee,fee',
	1 => 'Ugly Orange|b50,ddd,111,555,fff,eee,ffa,dd9,e81,c40,fff',
	2 => 'Touch Blue|138,ddd,111,555,fff,eee,ffa,dd9,138,fff,fff',
	3 => 'Sickly Green|293C03,ccc,000,555,fff,eee,CCE691,ACC671,495C23,919C35,fff',
	4 => 'Kris\' Purple|d5d,000,ddd,999,222,111,202,101,909,222,000,000',
	5 => '#red|d12,ddd,111,555,fff,eee,ffa,dd9,c12,fff,fff',
	6 => 'Blaq|0049DA,FFFFCC,333300,333300,F5EFC0,EDE8B1,CCFF99,99FF99,FFFFCC,003300,003300',
	//   'Name|links ,bodybg,bodyt ,small ,odd   ,even,replyodd,replyeven,menubg,menut,menua',
);

menu_register(array(
	'settings' => array(
		'callback' => 'settings_page',
		'display' => '⚙'
	),
	'reset' => array(
		'hidden' => true,
		'callback' => 'cookie_monster',
	),
));

function cookie_monster() {
	$cookies = array(
		'settings',
		'utc_offset',
		'search_favourite',
		'perPage',
		'USER_AUTH',
	);
	$duration = time() - 3600;
	foreach ($cookies as $cookie) {
		setcookie($cookie, null, $duration, '/');
		setcookie($cookie, null, $duration);
	}
	return theme('page', 'Cookie Monster', '<p>The cookie monster has logged you out and cleared all settings. Try logging in again now.</p>');
}

function setting_fetch($setting, $default = null) {
	$settings = (array) unserialize(base64_decode($_COOKIE['settings']));
	if (array_key_exists($setting, $settings)) {
		return $settings[$setting];
	} else {
		return $default;
	}
}

function setcookie_year($name, $value) {
	$duration = time() + (3600 * 24 * 365);
	setcookie($name, $value, $duration, '/');
}

function settings_page($args) {
	if ($args[1] == 'save') {
		$settings['perPage']      = $_POST['perPage'];
		$settings['gwt']          = $_POST['gwt'];
		$settings['colours']      = $_POST['colours'];
		$settings['reverse']      = $_POST['reverse'];
		$settings['timestamp']    = $_POST['timestamp'];
		$settings['hide_inline']  = $_POST['hide_inline'];
		$settings['hide_avatars'] = $_POST['hide_avatars'];
		$settings['utc_offset']   = (float)$_POST['utc_offset'];
		
		setcookie_year('settings', base64_encode(serialize($settings)));
		twitter_refresh('');
	}
	
	$perPage = array(
		  '5'	=>   '5 Tweets Per Page',
		 '10'	=>  '10 Tweets Per Page',
		 '20'	=>  '20 Tweets Per Page',
		 '30'	=>  '30 Tweets Per Page',
		 '40'	=>  '40 Tweets Per Page',
		 '50'	=>  '50 Tweets Per Page',
		'100' 	=> '100 Tweets Per Page (Slow)',
		'150' 	=> '150 Tweets Per Page (Very Slow)',
		'200' 	=> '200 Tweets Per Page (Extremely Slow)',
	);

	$colour_schemes = array();
	foreach ($GLOBALS['colour_schemes'] as $id => $info) {
		list($name) = explode('|', $info);
		$colour_schemes[$id] = $name;
	}
	
	$utc_offset = setting_fetch('utc_offset', 0);
/* returning 401 as it calls http://api.twitter.com/1/users/show.json?screen_name= (no username???)	
	if (!$utc_offset) {
		$user = twitter_user_info();
		$utc_offset = $user->utc_offset;
	}
*/
	if ($utc_offset > 0) {
		$utc_offset = '+' . $utc_offset;
	}

	$content = '';
	$content .= '<form action="settings/save" method="post">
	                <p>Colour scheme:
	                    <br />
	                    <select name="colours">';
	$content .= theme('options', $colour_schemes, setting_fetch('colours', 0));
	$content .=         '</select>
	                </p>';
	
	
	$content .=     '<p>Tweets Per Page:
                        <br />
                        <select name="perPage">';
	$content .=             theme('options', $perPage, setting_fetch('perPage', 20));
	$content .=         '</select>
	                    <br/>
	                </p>';

	$content .= '<p>
	                <label>
	                    <input type="checkbox" name="gwt" value="on" '. (setting_fetch('gwt') == 'on' ? ' checked="checked" ' : '') .' /> 
	                    Use Google Web Transcoder (GWT) for external links. Suitable for older phones and people with less bandwidth.
	                </label>
	            </p>';

	$content .= '<p>
	                <label>
	                    <input type="checkbox" name="timestamp" value="yes" '. (setting_fetch('timestamp') == 'yes' ? ' checked="checked" ' : '') .' /> 
	                    Show the timestamp ' . twitter_date('H:i') . ' instead of 25 sec ago
	                </label>
	            </p>';
	
	$content .= '<p>
	                <label>
	                    <input type="checkbox" name="hide_inline" value="yes" '. (setting_fetch('hide_inline') == 'yes' ? ' checked="checked" ' : '') .' /> 
	                    Hide inline media (eg pictures, videos, thumbnails).
	                </label>
	            </p>';

	$content .= '<p>
	                <label>
	                    <input type="checkbox" name="hide_avatars" value="yes" '. (setting_fetch('hide_avatars') == 'yes' ? ' checked="checked" ' : '') .' /> 
	                    Hide users\' profile images.
	                </label>
	            </p>';
	$content .= '<p><label>The time in UTC is currently ' . gmdate('H:i') . ', by using an offset of <input type="text" name="utc_offset" value="'. $utc_offset .'" size="3" /> we display the time as ' . twitter_date('H:i') . '.<br />It is worth adjusting this value if the time appears to be wrong.</label></p>';
	
	$content .= '<p><input type="submit" value="Save" /></p></form>';

	$content .= '<hr /><p>Visit <a href="reset">Reset</a> if things go horribly wrong - it will log you out and clear all settings.</p>';

	return theme('page', 'Settings', $content);
}
