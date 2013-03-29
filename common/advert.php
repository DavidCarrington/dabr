<?php
/* Example advert code */

function show_advert() {
	// This allows for multiple advert providers
	// If one advert provider isn't installed, or doesn't return an ad, a different provider is used

	// InMobi Adverts
	// http://inmobi.com/
	if (file_exists("common/MkhojAd.php"))
	{
		$ad = inmobi_ad();
		if ($ad)
		{
			return $ad;
		}
	}

	// Admob Adverts
	// http://admob.com/
	if (file_exists("common/admob.php"))
	{
		$ad = admob_ad();
		if ($ad)
		{
			return $ad;
		}
	}

	// Google Adverts
	// https://google.com/adsense
	if (file_exists('common/googlead.php'))
	{
		google_ad();
	}

	//	No advert found
	return	'';
}

function inmobi_ad()	{
	require_once ("common/MkhojAd.php");
	// Create an object of mkhoj_class
	// Use your own InMobi key here
	$base = new MkhojAd(INMOBI_API_KEY);
	// Set Number of ads required.
	$base->set_num_of_ads(1);
	$base->set_page_keywords("twitter facebook social chat pictures");
	$base->set_ad_placements(array("top"));
	//$base->set_test_mode(true);

	if($base->request_ads())
	{
		return $base->fetch_ad("top");
	}

	return false;
}

function admob_ad()	{
	require_once('common/admob.php');
	return "Admob:".admob_request($admob_params);

}

function google_ad()	{
	require_once('common/googlead.php');
}

function touch_theme_advert() {
	return theme_advert();

	// If you want a different advert for the touch theme
	// Uncomment the following, and add your own client ID
	// or replace with your prefered script
	/*
	return '<script type="text/javascript"><!--
		window.googleAfmcRequest = {
		  client: "",
		  ad_type: "text_image",
		  output: "html",
		  channel: "",
		  format: "320x50_mb",
		  oe: "utf8",
		  color_border: "555555",
		  color_bg: "EEEEEE",
		  color_link: "0000CC",
		  color_text: "000000",
		  color_url: "008000",
		};
		//--></script>
		<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_afmc_ads.js"></script>
	';
*/
}

function bigtouch_theme_advert() {
	return theme_advert();
}

function desktop_theme_advert() {
	return theme_advert();
	// If you want a different advert for the touch theme
	// Uncomment the following, and add your own client ID
	// or replace with your prefered script
	/*
	return '<script type="text/javascript"><!--
				google_ad_client = "";
				google_ad_slot = "";
				google_ad_width = 728;
				google_ad_height = 90;
				//-->
			</script>
			<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>';
	*/
}
