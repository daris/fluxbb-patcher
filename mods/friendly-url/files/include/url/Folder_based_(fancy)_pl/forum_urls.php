<?php
/**
 * SEF URLs that use a folder-like layout and include topic name and forum name
 * where applicable.
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
	'change_email'					=>	'zmien/email/$1/',
	'change_email_key'				=>	'zmien/email/$1/$2/',
	'change_password'				=>	'zmien/haslo/$1/',
	'change_password_key'			=>	'zmien/haslo/$1/$2/',
	'delete'						=>	'usun/$1/',
	'delete_avatar'					=>	'usun/avatar/$1/',
	'edit'							=>	'edytuj/$1/',
	'email'							=>	'email/$1/',
	'forum'							=>	'forum/$1/$2/',
	'forum_rss'						=>	'kanal/rss/forum/$1/',
	'forum_atom'					=>	'kanal/atom/forum/$1/',
	'help'							=>	'pomoc/',
	'index'							=>	'',
	'index_rss'						=>	'kanal/rss/',
	'index_atom'					=>	'kanal/atom/',
	'login'							=>	'loguj/',
	'logout'						=>	'wyloguj/$1/$2/',
	'mark_read'						=>	'oznacz/przeczytane/',
	'mark_forum_read'				=>	'oznacz/forum/$1/przeczytane/',
	'new_topic'						=>	'nowy/watek/$1/',
	'new_reply'						=>	'nowa/odpowiedz/$1/',
	'post'							=>	'post/$1/#p$1',
	'profile_essentials'			=>	'profil/$1/glowne/',
	'profile_personal'				=>	'profil/$1/personalne/',
	'profile_messaging'				=>	'profil/$1/komunikatory/',
	'profile_personality'			=>	'profil/$1/avatar-podpis/',
	'profile_display'				=>	'profil/$1/wyswietlanie/',
	'profile_privacy'				=>	'profil/$1/prywatnosc/',
	'profile_admin'					=>	'profil/$1/administracja/',
	'quote'							=>	'nowa/odpowiedz/$1/cytuj/$2/',
	'register'						=>	'rejestruj/',
	'report'						=>	'raportuj/$1/',
	'request_password'				=>	'nowe/haslo/',
	'rules'							=>	'zasady/',
	'search'						=>	'szukaj/',
	'search_results'				=>	'szukaj/$1/',
	'search_new'					=>	'szukaj/nowe/',
	'search_new_forum'				=>	'szukaj/nowe/$1/',
	'search_recent'					=>	'szukaj/ostatnie/',
	'search_replies'				=>	'szukaj/twoje-odpowiedzi/',
	'search_user_posts'				=>	'szukaj/posty-uzytkownika/$1/',
	'search_user_topics'			=>	'szukaj/watki-uzytkownika/$1/',
	'search_unanswered'				=>	'szukaj/bez-odpowiedzi/',
	'search_subscriptions'			=>	'szukaj/subskrybcje/$1/',
	'subscribe_topic'				=>	'subskrybuj/watek/$1/',
	'subscribe_forum'				=>	'subskrybuj/forum/$1/',
	'topic'							=>	'watek/$1/$2/',
	'topic_rss'						=>	'kanal/rss/watek/$1/',
	'topic_atom'					=>	'kanal/atom/watek/$1/',
	'topic_new_posts'				=>	'watek/$1/$2/nowe/posty/',
	'topic_last_post'				=>	'watek/$1/ostatni/post/',
	'unsubscribe_topic'				=>	'przerwij-subskrybcje/watek/$1/',
	'unsubscribe_forum'				=>	'przerwij-subskrybcje/forum/$1/',
	'user'							=>	'profil/$1/',
	'users'							=>	'uzytkownicy/',
	'users_browse'					=>	'uzytkownicy/$1/$2/$3/$4/', 
	'upload_avatar'					=>	'wgraj/avatar/$1/',
	'page'							=>	'strona/$1/',
	'moderate_forum'				=>	'moderuj/$1/',
	'get_host'						=>	'pokaz-host/$1/',
	'move'							=>	'przenies/$1/$2/',
	'open'							=>	'otworz/$1/$2/',
	'close'							=>	'zamknij/$1/$2/',
	'stick'							=>	'przyklej/$1/$2/',
	'unstick'						=>	'odklej/$1/$2/',
	'moderate_topic'				=>	'moderuj/$1/$2/',
);