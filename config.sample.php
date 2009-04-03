<?php

// Cookie encryption key. Max 52 characters
define('ENCRYPTION_KEY', 'Example Key - Change Me!');

// OAuth consumer and secret keys. Available from http://twitter.com/oauth_clients
define('OAUTH_CONSUMER_KEY', '');
define('OAUTH_CONSUMER_SECRET', '');

// bit.ly login and API key for URL shortening
define('BITLY_LOGIN', '');
define('BITLY_API_KEY', '');

// Optional API keys for retrieving thumbnails
define('MOBYPICTURE_API_KEY', '');
define('FLICKR_API_KEY', '');

// Base URL, should point to your website, including a trailing slash
// Can be set manually but the following code tries to work it out automatically.
if ($directory = trim(dirname($_SERVER['SCRIPT_NAME']), '/\,')) {
  $base_url .= '/'.$directory;
}
define('BASE_URL', $base_url.'/');

?>