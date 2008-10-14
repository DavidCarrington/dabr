<?php

function mobile_theme_status_time_link($status) {
  $time_link = format_interval(time() - strtotime($status->created_at), 1);
  return "<small><a href='status/{$status->id}'>$time_link</a></small>";
}

?>