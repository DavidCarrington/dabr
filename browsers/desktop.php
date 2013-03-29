<?php
function desktop_theme_status_form($text = '', $in_reply_to_id = NULL) {
	if (user_is_authenticated()) {
		$icon = "images/twitter-bird-16x16.png";
		
		//	adding ?status=foo will automaticall add "foo" to the text area.
		if ($_GET['status'])
		{
			$text = $_GET['status'];
		}
		
		$output = '
		<form method="post" action="update">
			<fieldset>
				<legend><img src="'.$icon.'" width="16" height="16" /> What\'s Happening?</legend>
				<textarea id="status" name="status" rows="4" style="width:95%; max-width: 400px;">'.$text.'</textarea>
				<div>
					<input name="in_reply_to_id" value="'.$in_reply_to_id.'" type="hidden" />
					<input type="submit" value="Tweet" />
					<span id="remaining">140</span> 
					<span id="geo" style="display: none;">
						<input onclick="goGeo()" type="checkbox" id="geoloc" name="location" />
						<label for="geoloc" id="lblGeo"></label>
					</span>
				</div>
			</fieldset>
			<script type="text/javascript">
				started = false;
				chkbox = document.getElementById("geoloc");
				if (navigator.geolocation) {
					geoStatus("Tweet my location");
					if ("'.$_COOKIE['geo'].'"=="Y") {
						chkbox.checked = true;
						goGeo();
					}
				}
				function goGeo(node) {
					if (started) return;
					started = true;
					geoStatus("Locating...");
					navigator.geolocation.getCurrentPosition(geoSuccess, geoStatus , { enableHighAccuracy: true });
				}
				function geoStatus(msg) {
					document.getElementById("geo").style.display = "inline";
					document.getElementById("lblGeo").innerHTML = msg;
				}
				function geoSuccess(position) {
					geoStatus("Tweet my <a href=\'http://maps.google.co.uk/m?q=" + position.coords.latitude + "," + position.coords.longitude + "\' target=\'blank\'>location</a>");
					chkbox.value = position.coords.latitude + "," + position.coords.longitude;
				}
			</script>
		</form>';
		$output .= js_counter('status');
		return $output;
	}
}

function desktop_theme_search_form($query) {
	$query = stripslashes(htmlentities($query,ENT_QUOTES,"UTF-8"));

	return '
	<form action="search" method="get"><input name="query" value="'. $query .'" />
		<input type="submit" value="Search" />
		<br />
		<span id="geo" style="display: none;">
			<input onclick="goGeo()" type="checkbox" id="geoloc" name="location" /> 
			<label for="geoloc" id="lblGeo"></label>
			<select name="radius">
				<option value="1km">1 Km</option>
				<option value="5km">5 Km</option>
				<option value="10km">10 Km</option>
				<option value="50km">50 Km</option>
			</select>
		</span>
		<script type="text/javascript">
			started = false;
			chkbox = document.getElementById("geoloc");
			if (navigator.geolocation) {
				geoStatus("Search near my location");
				if ("'.$_COOKIE['geo'].'"=="Y") {
					chkbox.checked = true;
					goGeo();
				}
			}
			function goGeo(node) {
				if (started) return;
				started = true;
				geoStatus("Locating...");
				navigator.geolocation.getCurrentPosition(geoSuccess, geoStatus , { enableHighAccuracy: true });
			}
			function geoStatus(msg) {
				document.getElementById("geo").style.display = "inline";
				document.getElementById("lblGeo").innerHTML = msg;
			}
			function geoSuccess(position) {
				geoStatus("Search near my <a href=\'http://maps.google.co.uk/m?q=" + position.coords.latitude + "," + position.coords.longitude + "\' target=\'blank\'>location</a>");
				chkbox.value = position.coords.latitude + "," + position.coords.longitude;
			}
		</script>
	</form>';
}
?>
