<?php
function desktop_theme_status_form($text = '', $in_reply_to_id = NULL) {
  if (user_is_authenticated()) {
    $output = '<form method="post" action="update">
  <textarea id="status" name="status" rows="3" style="width:100%; max-width: 400px;">'.$text.'</textarea>
  <div><input name="in_reply_to_id" value="'.$in_reply_to_id.'" type="hidden" /><input type="submit" value="Update" /> <span id="remaining">140</span> 
  <span id="geo" style="display: none; float: right;"><input type="checkbox" id="geoloc" name="location" /> <label for="geoloc" id="lblGeo"></label></span></div>
  <script type="text/javascript">
if (navigator.geolocation) {
  geoStatus("Locating...");
  navigator.geolocation.getCurrentPosition(geoSuccess, geoStatus);
}
function geoStatus(msg) {
	document.getElementById("geo").style.display = "inline";
	document.getElementById("lblGeo").innerHTML = msg;
}
function geoSuccess(position) {
	chkbox = document.getElementById("geoloc");
	chkbox.checked = true;
	geoStatus("Tweet my location");
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
  return "<form action='search' method='get'><input name='query' value=\"$query\" style='width:100%; max-width: 300px' /><input type='submit' value='Search' /></form>";
}
?>
