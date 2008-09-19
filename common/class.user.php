<?php

class User {
  public $username, $password;

  private $td;
  private $cypher = 'blowfish';
  private $mode = 'cfb';
  private $key;
  private $cookie_name = 'USERAUTH';
  private $glue = ':';
  
 function __construct() {
    $this->key = file_get_contents(KEY_LOCATION);
    $this->td = mcrypt_module_open($this->cypher, '', $this->mode, '');
    if(array_key_exists($this->cookie_name, $_COOKIE)) {
      $buffer = $this->_unpackage($_COOKIE[$this->cookie_name]);
    }
  }
  
  function logout() {
    setcookie($this->cookie_name, '', time() - 3600);
  }
  
  function save($stay_logged_in = 0) {
    $cookie = $this->_package();
    $duration = 0;
    if ($stay_logged_in) {
      $duration = time() + (3600 * 24 * 365);
    }
    setcookie($this->cookie_name, $cookie, $duration);
  }
  
  private function _package() {
    $cookie = $this->username . $this->glue . $this->password;
    return $this->_encrypt($cookie);
  }
  
  private function _unpackage($package) {
    $cookie = $this->_decrypt($package);
    list($this->username, $this->password) = explode($this->glue, $cookie);
  }
    
  private function _encrypt($plain_text) {
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->td), MCRYPT_RAND);
    mcrypt_generic_init($this->td, $this->key, $iv);
    $crypt_text = mcrypt_generic($this->td, $plain_text);
    mcrypt_generic_deinit($this->td);
    return base64_encode($iv.$crypt_text);
  }
  
  private function _decrypt($crypt_text) {
    $crypt_text = base64_decode($crypt_text);
    $ivsize = mcrypt_enc_get_iv_size($this->td);
    $iv = substr($crypt_text, 0, $ivsize);
    $crypt_text = substr($crypt_text, $ivsize);
    mcrypt_generic_init($this->td, $this->key, $iv);
    $plain_text = mdecrypt_generic($this->td, $crypt_text);
    mcrypt_generic_deinit($this->td);
    return $plain_text;
  }
}

?>