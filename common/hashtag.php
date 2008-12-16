<?php

menu_register(array(
  'hash' => array(
    'hidden' => true,
    'callback' => 'hashtag_page',
  ),
));

function hashtag_page($query) {
  if (isset($query[1])) {
    $hashtag = '#'.$query[1];
    $content = theme('status_form', $hashtag.' ');
    $tl = twitter_search($hashtag);
    $content .= theme('timeline', $tl);
    theme('page', $hashtag, $content);
  } else {
    theme('page', 'Hashtag', 'Hash hash!');
  }
}

?>