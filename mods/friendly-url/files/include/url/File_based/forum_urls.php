<?php
/**
 * SEF URLs that use a file-like layout.
 *
 * @copyright Copyright (C) 2008 FluxBB.org, based on code copyright (C) 2002-2008 PunBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package FluxBB
 */


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// These are the simple file based SEF URLs
$forum_url = array(
	'insertion_find'				=>  '.html',
	'insertion_replace'				=>  '-$1.html',
	'change_email'					=>	'change-email$1.html',
	'change_email_key'				=>	'change-email$1-$2.html',
	'change_password'				=>	'change-password$1.html',
	'change_password_key'			=>	'change-password$1-$2.html',
	'delete'						=>	'delete$1.html',
	'delete_avatar'					=>	'delete-avatar$1.html',
	'edit'							=>	'edit$1.html',
	'email'							=>	'email$1.html',
	'forum'							=>	'forum$1.html',
	'forum_rss'						=>	'feed-rss-forum$1.xml',
	'forum_atom'					=>	'feed-atom-forum$1.xml',
	'help'							=>	'help.html',
	'index'							=>	'',
	'index_rss'						=>	'feed-rss.xml',
	'index_atom'					=>	'feed-atom.xml',
	'login'							=>	'login.html',
	'logout'						=>	'logout$1-$2.html',
	'mark_read'						=>	'mark-read.html',
	'mark_forum_read'				=>	'mark-forum$1-read.html',
	'new_topic'						=>	'new-topic$1.html',
	'new_reply'						=>	'new-reply$1.html',
	'post'							=>	'post$1.html#p$1',
	'profile_essentials'			=>	'user$1-essentials.html',
	'profile_personal'				=>	'user$1-personal.html',
	'profile_messaging'				=>	'user$1-messaging.html',
	'profile_personality'			=>	'user$1-personality.html',
	'profile_display'				=>	'user$1-display.html',
	'profile_privacy'				=>	'user$1-privacy.html',
	'profile_admin'					=>	'user$1-admin.html',
	'quote'							=>	'new-reply$1-quote$2.html',
	'register'						=>	'register.html',
	'report'						=>	'report$1.html',
	'request_password'				=>	'request-password.html',
	'rules'							=>	'rules.html',
	'search'						=>	'search.html',
	'search_results'				=>	'search$1.html',
	'search_new'					=>	'search-new.html',
	'search_new_forum'				=>	'search-new-$1.html',
	'search_recent'					=>	'search-recent.html',
	'search_replies'				=>	'search-replies.html',
	'search_user_posts'				=>	'search-user-posts$1.html',
	'search_user_topics'			=>	'search-user-topics$1.html',
	'search_unanswered'				=>	'search-unanswered.html',
	'search_subscriptions'			=>	'search-subscriptions$1.html',
	'subscribe_topic'				=>	'subscribe-topic$1.html',
	'subscribe_forum'				=>	'subscribe-forum$1.html',
	'topic'							=>	'topic$1.html',
	'topic_rss'						=>	'feed-rss-topic$1.xml',
	'topic_atom'					=>	'feed-atom-topic$1.xml',
	'topic_new_posts'				=>	'topic$1-new-posts.html',
	'topic_last_post'				=>	'topic$1-last-post.html',
	'unsubscribe_topic'				=>	'unsubscribe-topic$1.html',
	'unsubscribe_forum'				=>	'unsubscribe-forum$1.html',
	'user'							=>	'user$1.html',
	'users'							=>	'users.html',
	'users_browse'					=>	'users/$1/$2-$3-$4.html',
	'upload_avatar'					=>	'upload-avatar$1.html',
	'page'							=>	'p$1',
	'moderate_forum'				=>	'moderate$1.html',
	'get_host'						=>	'get_host$1.html',
	'move'							=>	'move_topics$1-$2.html',
	'open'							=>	'open$1-$2-$3.html',
	'close'							=>	'close$1-$2-$3.html',
	'stick'							=>	'stick$1-$2-$3.html',
	'unstick'						=>	'unstick$1-$2-$3.html',
	'moderate_topic'				=>	'moderate$1-$2.html',
);