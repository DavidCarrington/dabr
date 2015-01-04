<?php

function user_oauth() {
	
	//require_once ('codebird.php');
	$cb = \Codebird\Codebird::getInstance();
	// Flag forces twitter_process() to use OAuth signing
	// $GLOBALS['user']['type'] = 'oauth';

	//	If there's no OAuth Token, take the user to Twiter's sign in page
	if (! isset($_SESSION['oauth_token'])) {
		// get the request token
		$reply = $cb->oauth_requestToken(array(
			// 'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			'oauth_callback' => SERVER_NAME . $_SERVER['REQUEST_URI']
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
	elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify'])) {
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
		die();
	}
	header('Location: '. BASE_URL);	
}

function user_ensure_authenticated() {
	if (!user_is_authenticated()) {
		$content = theme('login');
		$content .= theme('about');
		theme('page', 'Login', $content);
	}
}

function user_logout() {
	//	Unset everything related to OAuth
	unset($GLOBALS['user']);
	unset($_SESSION['oauth_token']);
	unset($_SESSION['oauth_token_secret']);
	setcookie('USER_AUTH',          '', time() - 3600, '/');
	setcookie('oauth_token',        '', time() - 3600, '/');
	setcookie('oauth_token_secret', '', time() - 3600, '/');
}

function user_is_authenticated() {
	if (!isset($GLOBALS['user'])) {

		if(array_key_exists('USER_AUTH', $_COOKIE)) {
			 // _user_decrypt_cookie($_COOKIE['USER_AUTH']);

			$crypt_text = base64_decode($_COOKIE['USER_AUTH']);
			$td = mcrypt_module_open('blowfish', '', 'cfb', '');
			$ivsize = mcrypt_enc_get_iv_size($td);
			$iv = substr($crypt_text, 0, $ivsize);
			$crypt_text = substr($crypt_text, $ivsize);
			mcrypt_generic_init($td, _user_encryption_key(), $iv);
			$plain_text = mdecrypt_generic($td, $crypt_text);
			mcrypt_generic_deinit($td);

		//	TODO FIXME errr...
			list($GLOBALS['user']['username'], $GLOBALS['user']['password'], $GLOBALS['user']['type']) = explode(':', $plain_text);

		} else {
			$GLOBALS['user'] = array();
		}


	}

	if (!user_current_username()) {
		// if ($_POST['username'] && $_POST['password']) {
		// 	$GLOBALS['user']['username'] = trim($_POST['username']);
		// 	$GLOBALS['user']['password'] = $_POST['password'];
		// 	$GLOBALS['user']['type'] = 'oauth';
						
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
	
	if ($stay_logged_in) {
		$duration = time() + (3600 * 24 * 365);
	} else {
			$duration = 0;
	}

	// setcookie('oauth_token',        $_SESSION['oauth_token'],        $duration);
	// setcookie('oauth_token_secret', $_SESSION['oauth_token_secret'], $duration);

	$cookie = _user_encrypt_cookie();
	
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

//	TODO FIXME errr...
	list($GLOBALS['user']['username'], $GLOBALS['user']['password'], $GLOBALS['user']['type']) = explode(':', $plain_text);
}

function theme_login() {
	//	Reset stale OAuth data
	setting_clear_session_oauth();

	$content = '<div class="tweet">
					<p>
						<a href="oauth">
							<img src="images/sign-in-with-twitter-gray.png" 
							     alt="Sign in with Twitter" 
							     width="158" 
							     height="28" 
							     class="action" /></a>
						<br />
						<a href="oauth">Sign in via Twitter.com</a>
					</p>';

	return $content;
}

function theme_logged_out() {
	return '<p>Logged out. <a href="">Login again</a></p>';
}