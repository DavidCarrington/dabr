<?php

function url_fetch($url) 
{
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

function oembed_embed_thumbnails(&$feed) 
{
	foreach($feed as &$status) 
	{ // Loop through the feed
		if(stripos($status->text, 'NSFW') === FALSE) 
		{ // Ignore image fetching for tweets containing NSFW
			if ($status->entities) 
			{ // If there are entities
				$entities = $status->entities;
				if($entities->urls)	
				{
					foreach($entities->urls as $urls) 
					{	// Loop through the URL entities
						if($urls->expanded_url != "") {	// Use the expanded URL, if it exists, to pass to Oembed provider
							$url = $urls->expanded_url;
						}
						else {
							$url = $urls->url;
						}
						$matched_urls[urlencode($url)][] = $status->id;		
					}
				} else {
					//	No URLs, do nothing
					$matched_urls[] = null;
				}
			}
		}
	}

	//	Reduce the array of empty items
	foreach ($matched_urls as $key => $value) 
	{
		if (null == $value)
		{
			unset($matched_urls[$key]);
		}
	}

	// Make a single API call to Embedkit.
	if(defined('EMBEDKIT_KEY') && EMBEDKIT_KEY != "") 
	{
		//	Only the URLs which we're going to pass to Embedkit
		$justUrls = array_keys($matched_urls);
	
		$count = count($justUrls);
		if ($count == 0) return;
		// if ($count > 20) {
		// 	// Things can slow down with lots of links.
		// 	$justUrls = array_chunk ($justUrls, 10);
		// 	$justUrls = $justUrls[0];
		// }
		$url = 'https://embedkit.com/api/v1/embed?key='.EMBEDKIT_KEY.'&urls=' . implode(',', $justUrls) . '&format=json';

		$embedly_json = url_fetch($url);
		$oembeds = json_decode($embedly_json);

		if($oembeds->type != 'error') 
		{
			//	Single statuses don't come back in an array
			if (!is_array($oembeds))
			{
				$temp = array(0 => $oembeds);
				$oembeds = $temp;
			}
			
			foreach ($justUrls as $index => $url)
			{
				$thumb = "";
				$title = "";
				
				if ($oembeds[$index]->thumbnail_url) {
					//	Direct links to files
					$thumb = $oembeds[$index]->thumbnail_url;
				} 

				if ($oembeds[$index]->title) {
					//	Direct links to files
					$title = $oembeds[$index]->title;
				}

				if ($thumb) 
				{	//	Embed the thumbnail
					$html = theme('external_link', urldecode($url), "<img src=\"" . image_proxy($thumb, "") . "\"" .
					                                                " title=\"{$title}\" alt=\"{$title}\" class=\"embedded\" />");
					foreach ($matched_urls[$url] as $statusId) 
					{
						$feed[$statusId]->text = $feed[$statusId]->text . '<br />' . '<span class="embed">' . $html . '</span>';
					}
				} elseif  ($title) 	{	//	Embed a link
					$html = theme('external_link', urldecode($url), $title);
					foreach ($matched_urls[$url] as $statusId) 
					{
						$feed[$statusId]->text = $feed[$statusId]->text . '<br />' . '<span class="embed">' . $html . '</span>';
					}
				}

			}
		}
	}
}