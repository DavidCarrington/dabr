<?php
  
$GLOBALS['colour_schemes'] = array(
  1 => 'Ugly Orange|b50,ddd,111,555,fff,eee,ffa,dd9,e81,c40,fff',
  2 => 'Touch Blue|138,ddd,111,555,fff,eee,ffa,dd9,138,fff,fff',
  3 => 'Sickly Green|293C03,ccc,000,555,fff,eee,CCE691,ACC671,495C23,919C35,fff',
  4 => 'Kris\' Purple|d5d,000,ddd,999,222,111,202,101,909,222,000,000',
  5 => '#red|d12,ddd,111,555,fff,eee,ffa,dd9,c12,fff,fff',
);

menu_register(array(
  'settings' => array(
    'callback' => 'settings_page',
  ),
  'reset' => array(
    'hidden' => true,
    'callback' => 'cookie_monster',
  ),
));

function cookie_monster() {
  $cookies = array(
    'browser',
    'settings',
    'utc_offset',
    'search_favourite',
    'USER_AUTH',
  );
  $duration = time() - 3600;
  foreach ($cookies as $cookie) {
    setcookie($cookie, NULL, $duration, '/');
    setcookie($cookie, NULL, $duration);
  }
  return theme('page', 'Cookie Monster', '<p>The cookie monster has logged you out and cleared all settings. Try logging in again now.</p>');
}

function setting_fetch($setting, $default = NULL) {
  $settings = (array) unserialize(base64_decode($_COOKIE['settings']));
  if (array_key_exists($setting, $settings)) {
    return $settings[$setting];
  } else {
    return $default;
  }
}

function settings_page($args) {
  if ($args[1] == 'save') {
    $settings['browser'] = $_POST['browser'];
    $settings['gwt'] = $_POST['gwt'];
    $settings['colours'] = $_POST['colours'];
    $settings['reverse'] = $_POST['reverse'];
    $duration = time() + (3600 * 24 * 365);
    setcookie('settings', base64_encode(serialize($settings)), $duration, '/');
    twitter_refresh('');
  }
  
  $modes = array(
    'mobile' => 'Normal phone',
    'touch' => 'Touch phone',
    'desktop' => 'PC/Laptop',
    'text' => 'Text only',
    'worksafe' => 'Work Safe',
  );
  
  $gwt = array(
    'off' => 'direct',
    'on' => 'via GWT',
  );
  
  $colour_schemes = array();
  foreach ($GLOBALS['colour_schemes'] as $id => $info) {
    list($name, $colours) = explode('|', $info);
    $colour_schemes[$id] = $name;
  }
  
  $content .= '<form action="settings/save" method="post"><p>Colour scheme:<br /><select name="colours">';
  $content .= theme('options', $colour_schemes, setting_fetch('colours', 1));
  $content .= '</select></p><p>Mode:<br /><select name="browser">';
  $content .= theme('options', $modes, $GLOBALS['current_theme']);
  $content .= '</select></p><p>External links go:<br /><select name="gwt">';
  $content .= theme('options', $gwt, setting_fetch('gwt', $GLOBALS['current_theme'] == 'text' ? 'on' : 'off'));
  $content .= '</select><small><br />Google Web Transcoder (GWT) converts third-party sites into small, speedy pages suitable for older phones and people with less bandwidth.</small></p>';
  $content .= '<p><label><input type="checkbox" name="reverse" value="yes" '. (setting_fetch('reverse') == 'yes' ? ' checked="checked" ' : '') .' /> Attempt to reverse the conversation thread view.</label></p>';
  $content .= '<p><input type="submit" value="Save" /></p></form>';
  
  $content .= '<hr /><p>Visit <a href="reset">Reset</a> if things go horribly wrong - it will log you out and clear all settings.</p>';
  
  return theme('page', 'Settings', $content);
}