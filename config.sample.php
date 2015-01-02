<?php
/*
	Welcome to Dabr!
	Edit this file and rename it "config.php"
*/

// Base URL, must be point to your website, including a trailing slash
// eg "https://example.com/dabr/"
$base_url = "https://YOUR.SITE/DABR/";
define('BASE_URL', $base_url);

// OAuth consumer and secret keys. Available from https://apps.twitter.com/
define('OAUTH_CONSUMER_KEY',    '');
define('OAUTH_CONSUMER_SECRET', '');

// Cookie encryption key. Max 52 characters
define('ENCRYPTION_KEY', 'Example Key - Change Me!');

//	That's it! You're done :-)

//	Everything in this section is optional. Add it if you wish

// Optional: Embedkit Key 
// Embed image previews in tweets
// Free sign up at https://embedkit.com/
define('EMBEDKIT_KEY', '');

// Optional: Image Proxy URL
define('IMAGE_PROXY_URL', '');

// Optional: Enable to view page processing and API time
define('DEBUG_MODE', 'OFF');

//	Optional: This will display any errors you introduce into the code.
//	See more at http://php.net/manual/en/function.error-reporting.php
error_reporting(E_ALL ^ E_NOTICE);

// Google Analytics Mobile tracking code
// You need to download ga.php from the Google Analytics website for this to work
// Copyright 2009 Google Inc. All Rights Reserved.
$GA_ACCOUNT = "";
$GA_PIXEL = "ga.php";

function googleAnalyticsGetImageUrl() {
	global $GA_ACCOUNT, $GA_PIXEL;
	$url = "";
	$url .= $GA_PIXEL . "?";
	$url .= "utmac=" . $GA_ACCOUNT;
	$url .= "&utmn=" . rand(0, 0x7fffffff);
	$referer = $_SERVER["HTTP_REFERER"];
	$query = $_SERVER["QUERY_STRING"];
	$path = $_SERVER["REQUEST_URI"];
	if (empty($referer)) {
		$referer = "-";
	}
	$url .= "&utmr=" . urlencode($referer);
	if (!empty($path)) {
		$url .= "&utmp=" . urlencode($path);
	}
	$url .= "&guid=ON";
	return str_replace("&", "&amp;", $url);
}