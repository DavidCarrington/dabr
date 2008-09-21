<?php

class Twitter {
  var $username;
  var $password;
  var $user_agent = 'A PHP Twitter Class';
  var $headers = array();
  
  function search($query) {
    $request = 'http://search.twitter.com/search.json?q=' . urlencode($query);
    return $this->json_process($request);
  }
  
  function show($id) {
    if (!$id) return false;
    $request = "http://twitter.com/statuses/show/{$id}.json";
    return $this->json_process($request);
  }
  
  function update($status) {
    if (!$status) return false;
    $request = 'http://twitter.com/statuses/update.json';
    $post_data = 'source=dabr&status='.urlencode($status);
    return $this->json_process($request, $post_data);
  }
  
  function destroy($id) {
    if (!$id) return false;
    $request = "http://twitter.com/statuses/destroy/{$id}.json";
    return $this->json_process($request, 'a=b');
  }
  
	function user_timeline($user) {
		$request = "http://twitter.com/statuses/user_timeline/{$user}.json"; 
		return $this->json_process($request);  
	}
  
	function friends_timeline() {
		$request = 'http://twitter.com/statuses/friends_timeline.json'; 
		return $this->json_process($request);  
	}
  
  function public_timeline() {
		$request = 'http://twitter.com/statuses/public_timeline.json'; 
		return $this->json_process($request);  
  }
  
	function replies_timeline() {
		$request = 'http://twitter.com/statuses/replies.json'; 
		return $this->json_process($request);  
	}
  
	function direct_messages() {
		$request = 'http://twitter.com/direct_messages.json'; 
		return $this->json_process($request);  
	}
  
  function rate_limit_status() {
    return $this->json_process('http://twitter.com/account/rate_limit_status.json');
  }
  
  function follow_user( $id ) {
    if (!$id) return false;
		$request = "http://twitter.com/friendships/create/{$id}.json";
		return $this->json_process($request, 'a=b');
	}
	
	function leave_user( $id ) {
    if (!$id) return false;
		$request = "http://twitter.com/friendships/destroy/{$id}.json";
		return $this->json_process($request, 'a=b');
	}
  
  function json_process($url, $post_data=false) {
    return json_decode($this->process($url, $post_data));
  }
  
  function process($url, $post_data=false, $login=true) {
		$ch = curl_init($url);
		if($post_data !== false) {
      curl_setopt ($ch, CURLOPT_POST, true);
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    if($login && $this->username !== false && $this->password !== false)
      curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);

    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $this->response = curl_exec($ch);

    $this->response_info=curl_getinfo($ch);
    curl_close($ch);

    if( intval( $this->response_info['http_code'] ) == 200 )
      return $this->response;    
    else
      return false;
  }
}

?>