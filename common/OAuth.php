<?php
// vim: foldmethod=marker

/* Generic exception class
 */
class OAuthException extends Exception {/*{{{*/
  // pass
}/*}}}*/

class OAuthConsumer {/*{{{*/
  public $key;
  public $secret;

  function __construct($key, $secret, $callback_url=NULL) {/*{{{*/
    $this->key = $key;
    $this->secret = $secret;
    $this->callback_url = $callback_url;
  }/*}}}*/

  function __toString() {/*{{{*/
    return "OAuthConsumer[key=$this->key,secret=$this->secret]";
  }/*}}}*/
}/*}}}*/

class OAuthSignatureMethod {/*{{{*/
  public function check_signature(&$request, $consumer, $token, $signature) {
    $built = $this->build_signature($request, $consumer, $token);
    return $built == $signature;
  }
}/*}}}*/

class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {/*{{{*/
  function get_name() {/*{{{*/
    return "HMAC-SHA1";
  }/*}}}*/

  public function build_signature($request, $consumer, $token) {/*{{{*/
    $base_string = $request->get_signature_base_string();
    $request->base_string = $base_string;

    $key_parts = array(
      $consumer->secret,
      ($token) ? $token->secret : ""
    );

    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);

    return base64_encode( hash_hmac('sha1', $base_string, $key, true));
  }/*}}}*/
}/*}}}*/


class OAuthRequest {/*{{{*/
  private $parameters;
  private $http_method;
  private $http_url;
  // for debug purposes
  public $base_string;
  public static $version = '1.0';

  function __construct($http_method, $http_url, $parameters=NULL) {/*{{{*/
    @$parameters or $parameters = array();
    $this->parameters = $parameters;
    $this->http_method = $http_method;
    $this->http_url = $http_url;
  }/*}}}*/


  /**
   * attempt to build up a request from what was passed to the server
   */
  public static function from_request($http_method=NULL, $http_url=NULL, $parameters=NULL) {/*{{{*/
    $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
    @$http_url or $http_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
    @$http_method or $http_method = $_SERVER['REQUEST_METHOD'];
    
    $request_headers = OAuthRequest::get_headers();

    // let the library user override things however they'd like, if they know
    // which parameters to use then go for it, for example XMLRPC might want to
    // do this
    if ($parameters) {
      $req = new OAuthRequest($http_method, $http_url, $parameters);
    } else {
      // collect request parameters from query string (GET) and post-data (POST) if appropriate (note: POST vars have priority)
      $req_parameters = $_GET;
      if ($http_method == "POST" && @strstr($request_headers["Content-Type"], "application/x-www-form-urlencoded") ) {
        $req_parameters = array_merge($req_parameters, $_POST);
      }

      // next check for the auth header, we need to do some extra stuff
      // if that is the case, namely suck in the parameters from GET or POST
      // so that we can include them in the signature
      if (@substr($request_headers['Authorization'], 0, 6) == "OAuth ") {
        $header_parameters = OAuthRequest::split_header($request_headers['Authorization']);
        $parameters = array_merge($req_parameters, $header_parameters);
        $req = new OAuthRequest($http_method, $http_url, $parameters);
      } else $req = new OAuthRequest($http_method, $http_url, $req_parameters);
    }

    return $req;
  }/*}}}*/

  /**
   * pretty much a helper function to set up the request
   */
  public static function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters=NULL) {/*{{{*/
    @$parameters or $parameters = array();
    $defaults = array("oauth_version" => OAuthRequest::$version,
                      "oauth_nonce" => OAuthRequest::generate_nonce(),
                      "oauth_timestamp" => OAuthRequest::generate_timestamp(),
                      "oauth_consumer_key" => $consumer->key);
    $parameters = array_merge($defaults, $parameters);

    if ($token) {
      $parameters['oauth_token'] = $token->key;
    }
    return new OAuthRequest($http_method, $http_url, $parameters);
  }/*}}}*/

  public function set_parameter($name, $value) {/*{{{*/
    $this->parameters[$name] = $value;
  }/*}}}*/

  public function get_parameter($name) {/*{{{*/
    return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
  }/*}}}*/

  public function get_parameters() {/*{{{*/
    return $this->parameters;
  }/*}}}*/

  /**
   * Returns the normalized parameters of the request
   * 
   * This will be all (except oauth_signature) parameters,
   * sorted first by key, and if duplicate keys, then by
   * value.
   *
   * The returned string will be all the key=value pairs
   * concated by &.
   * 
   * @return string
   */
  public function get_signable_parameters() {/*{{{*/
    // Grab all parameters
    $params = $this->parameters;
		
    // Remove oauth_signature if present
    if (isset($params['oauth_signature'])) {
      unset($params['oauth_signature']);
    }
		
    // Urlencode both keys and values
    $keys = OAuthUtil::urlencode_rfc3986(array_keys($params));
    $values = OAuthUtil::urlencode_rfc3986(array_values($params));
    $params = array_combine($keys, $values);

    // Sort by keys (natsort)
    uksort($params, 'strcmp');

    // Generate key=value pairs
    $pairs = array();
    foreach ($params as $key=>$value ) {
      if (is_array($value)) {
        // If the value is an array, it's because there are multiple 
        // with the same key, sort them, then add all the pairs
        natsort($value);
        foreach ($value as $v2) {
          $pairs[] = $key . '=' . $v2;
        }
      } else {
        $pairs[] = $key . '=' . $value;
      }
    }
		
    // Return the pairs, concated with &
    return implode('&', $pairs);
  }/*}}}*/

  /**
   * Returns the base string of this request
   *
   * The base string defined as the method, the url
   * and the parameters (normalized), each urlencoded
   * and the concated with &.
   */
  public function get_signature_base_string() {/*{{{*/
    $parts = array(
      $this->get_normalized_http_method(),
      $this->get_normalized_http_url(),
      $this->get_signable_parameters()
    );

    $parts = OAuthUtil::urlencode_rfc3986($parts);

    return implode('&', $parts);
  }/*}}}*/

  /**
   * just uppercases the http method
   */
  public function get_normalized_http_method() {/*{{{*/
    return strtoupper($this->http_method);
  }/*}}}*/

  /**
   * parses the url and rebuilds it to be
   * scheme://host/path
   */
  public function get_normalized_http_url() {/*{{{*/
    $parts = parse_url($this->http_url);

    $port = @$parts['port'];
    $scheme = $parts['scheme'];
    $host = $parts['host'];
    $path = @$parts['path'];

    $port or $port = ($scheme == 'https') ? '443' : '80';

    if (($scheme == 'https' && $port != '443')
        || ($scheme == 'http' && $port != '80')) {
      $host = "$host:$port";
    }
    return "$scheme://$host$path";
  }/*}}}*/

  /**
   * builds a url usable for a GET request
   */
  public function to_url() {/*{{{*/
    $out = $this->get_normalized_http_url() . "?";
    $out .= $this->to_postdata();
    return $out;
  }/*}}}*/

  /**
   * builds the data one would send in a POST request
   *
   * TODO(morten.fangel):
   * this function might be easily replaced with http_build_query()
   * and corrections for rfc3986 compatibility.. but not sure
   */
  public function to_postdata() {/*{{{*/
    $total = array();
    foreach ($this->parameters as $k => $v) {
      if (is_array($v)) {
        foreach ($v as $va) {
          $total[] = OAuthUtil::urlencode_rfc3986($k) . "[]=" . OAuthUtil::urlencode_rfc3986($va);
        }
      } else {
        $total[] = OAuthUtil::urlencode_rfc3986($k) . "=" . OAuthUtil::urlencode_rfc3986($v);
      }
    }
    $out = implode("&", $total);
    return $out;
  }/*}}}*/

  /**
   * builds the Authorization: header
   */
  public function to_header() {/*{{{*/
    $out ='Authorization: OAuth realm=""';
    $total = array();
    foreach ($this->parameters as $k => $v) {
      if (substr($k, 0, 5) != "oauth") continue;
      if (is_array($v)) throw new OAuthException('Arrays not supported in headers');
      $out .= ',' . OAuthUtil::urlencode_rfc3986($k) . '="' . OAuthUtil::urlencode_rfc3986($v) . '"';
    }
    return $out;
  }/*}}}*/

  public function __toString() {/*{{{*/
    return $this->to_url();
  }/*}}}*/


  public function sign_request($signature_method, $consumer, $token) {/*{{{*/
    $this->set_parameter("oauth_signature_method", $signature_method->get_name());
    $signature = $this->build_signature($signature_method, $consumer, $token);
    $this->set_parameter("oauth_signature", $signature);
  }/*}}}*/

  public function build_signature($signature_method, $consumer, $token) {/*{{{*/
    $signature = $signature_method->build_signature($this, $consumer, $token);
    return $signature;
  }/*}}}*/

  /**
   * util function: current timestamp
   */
  private static function generate_timestamp() {/*{{{*/
    return time();
  }/*}}}*/

  /**
   * util function: current nonce
   */
  private static function generate_nonce() {/*{{{*/
    $mt = microtime();
    $rand = mt_rand();

    return md5($mt . $rand); // md5s look nicer than numbers
  }/*}}}*/

  /**
   * util function for turning the Authorization: header into
   * parameters, has to do some unescaping
   */
  private static function split_header($header) {/*{{{*/
    $pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
    $offset = 0;
    $params = array();
    while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
      $match = $matches[0];
      $header_name = $matches[2][0];
      $header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
      $params[$header_name] = OAuthUtil::urldecode_rfc3986( $header_content );
      $offset = $match[1] + strlen($match[0]);
    }
  
    if (isset($params['realm'])) {
       unset($params['realm']);
    }

    return $params;
  }/*}}}*/

  /**
   * helper to try to sort out headers for people who aren't running apache
   */
  private static function get_headers() {/*{{{*/
    if (function_exists('apache_request_headers')) {
      // we need this to get the actual Authorization: header
      // because apache tends to tell us it doesn't exist
      return apache_request_headers();
    }
    // otherwise we don't have apache and are just going to have to hope
    // that $_SERVER actually contains what we need
    $out = array();
    foreach ($_SERVER as $key => $value) {
      if (substr($key, 0, 5) == "HTTP_") {
        // this is chaos, basically it is just there to capitalize the first
        // letter of every word that is not an initial HTTP and strip HTTP
        // code from przemek
        $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
        $out[$key] = $value;
      }
    }
    return $out;
  }/*}}}*/
}/*}}}*/

class OAuthUtil {/*{{{*/
  public static function urlencode_rfc3986($input) {/*{{{*/
	if (is_array($input)) {
		return array_map(array('OAuthUtil','urlencode_rfc3986'), $input);
	} else if (is_scalar($input)) {
		return str_replace('+', ' ',
	                       str_replace('%7E', '~', rawurlencode($input)));
	} else {
		return '';
	}
  }/*}}}*/
    

  // This decode function isn't taking into consideration the above 
  // modifications to the encoding process. However, this method doesn't 
  // seem to be used anywhere so leaving it as is.
  public static function urldecode_rfc3986($string) {/*{{{*/
    return rawurldecode($string);
  }/*}}}*/
}/*}}}*/

?>
