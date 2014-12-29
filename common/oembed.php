<?php

function oembed_embed_thumbnails(&$feed) {

	foreach($feed as &$status) { // Loop through the feed
		if(stripos($status->text, 'NSFW') === FALSE) { // Ignore image fetching for tweets containing NSFW
			if ($status->entities) { // If there are entities
				$entities = $status->entities;
				if($entities->urls)	{
					foreach($entities->urls as $urls) {	// Loop through the URL entities
						if($urls->expanded_url != "") {	// Use the expanded URL, if it exists, to pass to Embedly
							$url = $urls->expanded_url;
						}
						else {
							$url = $urls->url;
						}
						$matched_urls[urlencode($url)][] = $status->id;
					}
				}
			}
		}
	}

	// Make a single API call to Embedkit.
	if(defined('EMBEDKIT_KEY') && EMBEDKIT_KEY != "") {
		$justUrls = array_keys($matched_urls);
		$count = count($justUrls);
		if ($count == 0) return;
		// if ($count > 20) {
		// 	// Embedly has a limit of 10 URLs processed at a time. Not ideal for @dabr, but fair enough to ignore images after that.
		// 	// http://embed.ly/docs/embed/api/arguments
		// 	$justUrls = array_chunk ($justUrls, 10);
		// 	$justUrls = $justUrls[0];
		// }
		$url = 'https://embedkit.com/api/v1/extract?key='.EMBEDKIT_KEY.'&urls=' . implode(',', $justUrls) . '&format=json';
		$embedly_json = url_fetch($url);
		$oembeds = json_decode($embedly_json);

		echo "<pre>$url		";
		var_dump($oembeds);
		
		// Put the thumbnails into the $feed
		if($oembeds->type != 'error') {
			foreach ($justUrls as $index => $url) {
				if ($thumb = $oembeds[$index]->thumbnail_url) {
					$html = theme('external_link', urldecode($url), "<img src='" . image_proxy($thumb, "x45/") . "' class=\"embeded\" />");
					foreach ($matched_urls[$url] as $statusId) {
						$feed[$statusId]->text = $feed[$statusId]->text . '<br />' . '<span class="embed">' . $html . '</span>';
					}
				}
			}
		}
	}

	function url_fetch($url) {
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
}
