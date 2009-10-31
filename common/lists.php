<?php

menu_register(array(
  'lists' => array(
    'security' => true,
    'callback' => 'lists_controller',
  ),
));



/* API Calls */

function twitter_lists_tweets($user, $list) {
	// Tweets belonging to a list
	return twitter_process("http://twitter.com/{$user}/lists/{$list}/statuses.json");
}

function twitter_lists_user_lists($user) {
	// Lists a user has created
	return twitter_process("http://twitter.com/{$user}/lists.json");
}

function twitter_lists_user_memberships($user) {
	// Lists a user belongs to
	return twitter_process("http://twitter.com/{$user}/lists/memberships.json");
}

function twitter_lists_list_members($user, $list) {
	// Members of a list
	$url = "http://twitter.com/{$user}/{$list}/members.json";
	return twitter_process($url);
}

function twitter_lists_list_subscribers($user, $list) {
	// Subscribers of a list
	$url = "http://twitter.com/{$user}/{$list}/subscribers.json";
	return twitter_process($url);
}



/* Front controller for the new pages 

List URLS:
	lists -- current user's lists
	lists/$user -- xhosen user's lists
	lists/$user/lists -- alias of the above
	lists/$user/memberships -- lists user is in
	lists/$user/$list -- tweets
	lists/$user/$list/members
	lists/$user/$list/subscribers
	lists/$user/$list/edit -- rename a list (no member editting)
*/

function lists_controller($query) {
	// Pick off $user from $query or default to the current user
	$user = $query[1];
	if (!$user) $user = user_current_username();
	
	// Fiddle with the $query to find which part identifies the page they want
	if ($query[3]) {
		// URL in form: lists/$user/$list/$method
		$method = $query[3];
		$list = $query[2];
	} else {
		// URL in form: lists/$user/$method
		$method = $query[2];
	}
	
	// Attempt to call the correct page based on $method
	switch ($method) {
		case '':
			// TODO: show a summary page?
		case 'lists':
			// Show which lists a user has created
			return lists_lists_page($user);
		case 'memberships':
			// Show which lists a user belongs to
			return lists_membership_page($user);
		case 'members':
			// Show members of a list
			return lists_list_members_page($user, $list);
		case 'subscribers':
			// Show subscribers of a list
			return lists_list_subscribers_page($user, $list);
		case 'edit':
			// TODO: List editting page (name and availability)
			break;
		default:
			// Show tweets in a particular list
			$list = $method;
			return lists_list_tweets_page($user, $list);
	}
	
	// Error to be shown for any incomplete pages (breaks above)
	return theme('error', 'List page not found');
}



/* Pages */

function lists_lists_page($user) {
	// Show a user's lists
	$lists = twitter_lists_user_lists($user);
	// TODO: Show a tabs to switch between this page and memberships like Twitter.com
	$content = theme('lists', $lists);
	theme('page', "{$user}'s lists", $content);
}

function lists_membership_page($user) {
	// Show lists a user belongs to
	$lists = twitter_lists_user_memberships($user);
	$content = theme('status_form');
    $content .= theme('lists', $lists);
	theme('page', 'List memberhips', $content);
}

function lists_list_tweets_page($user, $list) {
	// Show tweets in a list
	$tweets = twitter_lists_tweets($user, $list);
	$tl = twitter_standard_timeline($tweets, 'public');
	$content = theme('status_form');
	$content .= theme('timeline', $tl);
	theme('page', "List {$user}/{$list}", $content);
}

function lists_list_members_page($user, $list) {
	// Show members of a list
	// TODO: add logic to CREATE and REMOVE members
	$p = twitter_lists_list_members($user, $list);
	
	// TODO: use a different theme() function? Add a "delete member" link for each member
	$content = theme('followers', $p->users);
	theme('page', "Members of {$user}/{$list}", $content);
}

function lists_list_subscribers_page($user, $list) {
	// Show subscribers of a list
	$p = twitter_lists_list_subscribers($user, $list);
	$content = theme('followers', $p->users);
	theme('page', "Subscribers of {$user}/{$list}", $content);
}



/* Theme functions */

function theme_lists($json) {
  if (count($json->lists) == 0) {
    return "<p>No lists to display</p>";
  }
  $rows = array();
  $headers = array('List', 'Members', 'Subscribers');	
  foreach ($json->lists as $list) {
    // print_R($list); die();
    $url = "lists/{$list->user->screen_name}/{$list->slug}";
    $rows[] = array(
      "<a href='{$url}'>{$list->full_name}</a>",
      "<a href='{$url}/members'>{$list->member_count}</a>",
      "<a href='{$url}/subscribers'>{$list->subscriber_count}</a>",
    );
  }
  return theme('table', $headers, $rows);
}
