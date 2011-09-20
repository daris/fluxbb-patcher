<?php
/**
 * SEF URLs that use a folder-like layout.
 *
 * @copyright Copyright (C) 2008 FluxBB.org, based on code copyright (C) 2002-2008 PunBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package FluxBB
 */


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// These are the "fancy" folder based SEF URLs
$forum_url = array(
	'change_email'					=>	'change/email/$1/',
	'change_email_key'				=>	'change/email/$1/$2/',
	'change_password'				=>	'change/password/$1/',
	'change_password_key'			=>	'change/password/$1/$2/',
	'delete'						=>	'delete/$1/',
	'delete_avatar'					=>	'delete/avatar/$1/$2/',
	'edit'							=>	'edit/$1/',
	'email'							=>	'email/$1/',
	'forum'							=>	'forum/$1/',
	'forum_rss'						=>	'feed/rss/forum/$1/',
	'forum_atom'					=>	'feed/atom/forum/$1/',
	'help'							=>	'help/',
	'index'							=>	'',
	'index_rss'						=>	'feed/rss/',
	'index_atom'					=>	'feed/atom/',
	'login'							=>	'login/',
	'logout'						=>	'logout/$1/$2/',
	'mark_read'						=>	'mark/read/',
	'mark_forum_read'				=>	'mark/forum/$1/read/',
	'new_topic'						=>	'new/topic/$1/',
	'new_reply'						=>	'new/reply/$1/',
	'post'							=>	'post/$1/#p$1',
	'profile_essentials'			=>	'user/$1/essentials/',
	'profile_personal'				=>	'user/$1/personal/',
	'profile_messaging'				=>	'user/$1/messaging/',
	'profile_personality'			=>	'user/$1/personality/',
	'profile_display'				=>	'user/$1/display/',
	'profile_privacy'				=>	'user/$1/privacy/',
	'profile_admin'					=>	'user/$1/admin/',
	'quote'							=>	'new/reply/$1/quote/$2/',
	'register'						=>	'register/',
	'report'						=>	'report/$1/',
	'request_password'				=>	'request/password/',
	'rules'							=>	'rules/',
	'search'						=>	'search/',
	'search_results'				=>	'search/$1/',
	'search_new'					=>	'search/new/',
	'search_new_forum'				=>	'search/new/$1/',
	'search_recent'					=>	'search/recent/',
	'search_replies'				=>	'search/replies/',
	'search_user_posts'				=>	'search/user/posts/$1/',
	'search_user_topics'			=>	'search/user/topics/$1/',
	'search_unanswered'				=>	'search/unanswered/',
	'search_subscriptions'			=>	'search/subscriptions/$1/',
	'subscribe_topic'				=>	'subscribe/topic/$1/',
	'subscribe_forum'				=>	'subscribe/forum/$1/',
	'topic'							=>	'topic/$1/',
	'topic_rss'						=>	'feed/rss/topic/$1/',
	'topic_atom'					=>	'feed/atom/topic/$1/',
	'topic_new_posts'				=>	'topic/$1/new/posts/',
	'topic_last_post'				=>	'topic/$1/last/post/',
	'unsubscribe_topic'				=>	'unsubscribe/topic/$1/',
	'unsubscribe_forum'				=>	'unsubscribe/forum/$1/',
	'user'							=>	'user/$1/',
	'users'							=>	'users/',
	'users_browse'					=>	'users/$1/$2/$3/$4/', 
	'upload_avatar'					=>	'upload/avatar/$1/',
	'page'							=>	'page/$1/',
	'moderate_forum'				=>	'moderate/$1/',
	'get_host'						=>	'get_host/$1/',
	'move'							=>	'move_topics/$1/$2/',
	'open'							=>	'open/$1/$2/',
	'close'							=>	'close/$1/$2/',
	'stick'							=>	'stick/$1/$2/',
	'unstick'						=>	'unstick/$1/$2/',
	'moderate_topic'				=>	'moderate/$1/$2/',
);