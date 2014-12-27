<?php
	function emoticons($text) {
		$array = array(
			":)"	=> "images/emoticons/icon_smile.gif",
			":-)"	=> "images/emoticons/icon_smile.gif",
			":("	=> "images/emoticons/icon_sad.gif",
			":-("	=> "images/emoticons/icon_sad.gif",
			":'("	=> "images/emoticons/icon_cry.gif",
			":D"	=> "images/emoticons/icon_biggrin.gif",
			";D"	=> "images/emoticons/icon_biggrin.gif",
			":-D"	=> "images/emoticons/icon_biggrin.gif",
			";)"	=> "images/emoticons/icon_wink.gif",
			";-)"	=> "images/emoticons/icon_wink.gif",
			":p"	=> "images/emoticons/icon_razz.gif",
			";p"	=> "images/emoticons/icon_razz.gif",
			":P"	=> "images/emoticons/icon_razz.gif",
			";P"	=> "images/emoticons/icon_razz.gif",
			":-p"	=> "images/emoticons/icon_razz.gif",
			":-P"	=> "images/emoticons/icon_razz.gif",
			":o"	=> "images/emoticons/icon_surprised.gif",
			":-o"	=> "images/emoticons/icon_surprised.gif",
			":O"	=> "images/emoticons/icon_surprised.gif",
			":-O"	=> "images/emoticons/icon_surprised.gif",
			":-|"	=> "images/emoticons/icon_neutral.gif",
			":|"	=> "images/emoticons/icon_neutral.gif",
			":l"	=> "images/emoticons/icon_neutral.gif",
			":-l"	=> "images/emoticons/icon_neutral.gif",
		);


		foreach($array as $emoticon => $graphic) {
        $text	= preg_replace('#(^|\W)('.preg_quote($emoticon,'#').')($|\W)#', "$1<img src='$graphic' alt='$emoticon' />$3", $text);
		}

		return $text;
	}
