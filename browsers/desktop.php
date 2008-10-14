<?php

function desktop_theme_external_link($url) {
  return "<a href='$url'>$url</a>";
}

function desktop_theme_status_form($text = '') {
  if (user_is_authenticated()) {
    return '<form method="POST" action="update">
  <input id="status" name="status" value="'.$text.'" size="45" />
  <input type="submit" value="Update" /> <span id="remaining">140</span>
</form>
<script type="text/javascript">
function updateCount() {
  document.getElementById("remaining").innerHTML = 140 - document.getElementById("status").value.length;
  setTimeout(updateCount, 200);
}
updateCount();
</script>';
  }
}

?>