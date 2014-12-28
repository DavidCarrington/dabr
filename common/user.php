<?php

function user_oauth() {
	
	//require_once ('codebird.php');
	$cb = \Codebird\Codebird::getInstance();
	// Flag forces twitter_process() to use OAuth signing
	$GLOBALS['user']['type'] = 'oauth';

	//	If there's no OAuth Token, take the user to Twiter's sign in page
	if (! isset($_SESSION['oauth_token'])) {
		// get the request token
		$reply = $cb->oauth_requestToken(array(
			'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
		));

		// store the token
		$cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
		$_SESSION['oauth_token']        = $reply->oauth_token;
		$_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
		$_SESSION['oauth_verify']       = true;

		// redirect to auth website
		$auth_url = $cb->oauth_authorize();
		header('Location: ' . $auth_url);
		die();

	}	//	If there's an OAuth Token 
//	elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify'])) {
		// verify the token
		$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		unset($_SESSION['oauth_verify']);

		// get the access token
		$reply = $cb->oauth_accessToken(array(
			'oauth_verifier' => $_GET['oauth_verifier']
		));

		// store the token (which is different from the request token!)
		$_SESSION['oauth_token']        = $reply->oauth_token;
		$_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
		
		$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
				
		//	Verify and get the username
		$user = $cb->account_verifyCredentials();
		$GLOBALS['user']['username']    = $user->screen_name;

		// Store ACCESS tokens in COOKIE
        $GLOBALS['user']['password'] = $_SESSION['oauth_token'] .'|'.$_SESSION['oauth_token_secret'];

		_user_save_cookie(1);
		// send to same URL, without oauth GET parameters
		header('Location: '. BASE_URL);
//		echo "Your Name is " . $user->screen_name;
		die();
//	}
//	header('Location: '. BASE_URL);	
}

function user_oauth_sign(&$url, &$args = false) {
	require_once 'OAuth.php';

	$method = $args !== false ? 'POST' : 'GET';

	// Move GET parameters out of $url and into $args
	if (preg_match_all('#[?&]([^=]+)=([^&]+)#', $url, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$args[$match[1]] = $match[2];
		}
		$url = substr($url, 0, strpos($url, '?'));
	}

	$sig_method = new OAuthSignatureMethod_HMAC_SHA1();
	$consumer = new OAuthConsumer(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET);
	$token = NULL;

	if (($oauth_token = $_GET['oauth_token']) && $_SESSION['oauth_request_token_secret']) {
		$oauth_token_secret = $_SESSION['oauth_request_token_secret'];
	} else {
		list($oauth_token, $oauth_token_secret) = explode('|', $GLOBALS['user']['password']);
	}
	if ($oauth_token && $oauth_token_secret) {
		$token = new OAuthConsumer($oauth_token, $oauth_token_secret);
	}

	$request = OAuthRequest::from_consumer_and_token($consumer, $token, $method, $url, $args);
	$request->sign_request($sig_method, $consumer, $token);

	switch ($method) {
		case 'GET':
			$url = $request->to_url();
			$args = false;
			return;
		case 'POST':
			$url = $request->get_normalized_http_url();
			$args = $request->to_postdata();
			return;
	}

// echo "hello";
}

function user_ensure_authenticated() {
	if (!user_is_authenticated()) {
		$content = theme('login');
		$content .= file_get_contents('about.html');
		theme('page', 'Login', $content);
	}
}

function user_logout() {
	unset($GLOBALS['user']);
	setcookie('USER_AUTH', '', time() - 3600, '/');
}

function user_is_authenticated() {
	if (!isset($GLOBALS['user'])) {
		if(array_key_exists('USER_AUTH', $_COOKIE)) {
			_user_decrypt_cookie($_COOKIE['USER_AUTH']);
		} else {
			$GLOBALS['user'] = array();
		}
	}
	
	// // Auto-logout any users that aren't correctly using OAuth
	// if (user_current_username() && user_type() !== 'oauth') {
	// 	user_logout();
	// 	twitter_refresh('logout');
	// }

	if (!user_current_username()) {
		// if ($_POST['username'] && $_POST['password']) {
		// 	$GLOBALS['user']['username'] = trim($_POST['username']);
		// 	$GLOBALS['user']['password'] = $_POST['password'];
		// 	$GLOBALS['user']['type'] = 'oauth';
			
		// 	$sql = sprintf("SELECT * FROM user WHERE username='%s' AND password=MD5('%s') LIMIT 1", mysql_escape_string($GLOBALS['user']['username']), mysql_escape_string($GLOBALS['user']['password']));
		// 	$rs = mysql_query($sql);
		// 	if ($rs && $user = mysql_fetch_object($rs)) {
		// 		$GLOBALS['user']['password'] = $user->oauth_key . '|' . $user->oauth_secret;
		// 	} else {
		// 		theme('error', 'Invalid username or password.');
		// 	}
			
		// 	_user_save_cookie($_POST['stay-logged-in'] == 'yes');
		// 	header('Location: '. BASE_URL);
		// 	exit();
		// } else {
		// 	return false;
		// }
		return false;
	}
	return true;
}

function user_current_username() {
	return $GLOBALS['user']['username'];
}

function user_is_current_user($username) {
	return (strcasecmp($username, user_current_username()) == 0);
}

function user_type() {
	return $GLOBALS['user']['type'];
}

function _user_save_cookie($stay_logged_in = 0) {
	$cookie = _user_encrypt_cookie();
	$duration = 0;
	if ($stay_logged_in) {
		$duration = time() + (3600 * 24 * 365);
	}
	setcookie('USER_AUTH', $cookie, $duration, '/');
}

function _user_encryption_key() {
	return ENCRYPTION_KEY;
}

function _user_encrypt_cookie() {
	$plain_text = $GLOBALS['user']['username'] . ':' . $GLOBALS['user']['password'] . ':' . $GLOBALS['user']['type'];

	$td = mcrypt_module_open('blowfish', '', 'cfb', '');
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, _user_encryption_key(), $iv);
	$crypt_text = mcrypt_generic($td, $plain_text);
	mcrypt_generic_deinit($td);
	return base64_encode($iv.$crypt_text);
}

function _user_decrypt_cookie($crypt_text) {
	$crypt_text = base64_decode($crypt_text);
	$td = mcrypt_module_open('blowfish', '', 'cfb', '');
	$ivsize = mcrypt_enc_get_iv_size($td);
	$iv = substr($crypt_text, 0, $ivsize);
	$crypt_text = substr($crypt_text, $ivsize);
	mcrypt_generic_init($td, _user_encryption_key(), $iv);
	$plain_text = mdecrypt_generic($td, $crypt_text);
	mcrypt_generic_deinit($td);

	list($GLOBALS['user']['username'], $GLOBALS['user']['password'], $GLOBALS['user']['type']) = explode(':', $plain_text);
}

function user_login() {
// 	return theme('page', 'Login','
// <form method="post" action="'.$_GET['q'].'">
// <p>Username <input name="username" size="15" />
// <br />Password <input name="password" type="password" size="15" />
// <br /><label><input type="checkbox" checked="checked" value="yes" name="stay-logged-in" /> Stay logged in? </label>
// <br /><input type="submit" value="Sign In" /></p>
// </form>

// <p><b>Registration steps:</b></p>

// <ol>
// 	<li><a href="oauth">Sign in via Twitter.com</a> from any computer</li>
// 	<li>Visit the Dabr settings page to choose a password</li>
// 	<li>Done! You can now benefit from accessing Twitter through Dabr from anywhere (even from computers that block Twitter.com)</li>
// </ol>
// ');
}

function theme_login() {
	$content = '<div style="margin:1em; font-size: 1.2em">
					<p>
						<a href="oauth">
							<img src="images/twitter_button_2_lo.gif" alt="Sign in with Twitter/" width="165" height="28" />
						</a>
						<br />
						<a href="oauth">Sign in via Twitter.com</a>
					</p>';

	$content .= "SESSION<pre>" . print_r($_SESSION, true) . "GLOBALS" . print_r($GLOBALS, true) ;
	
	return $content;
}

function theme_logged_out() {
	return '<p>Logged out. <a href="">Login again</a></p>';
}