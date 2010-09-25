<?php

$agent_names = array(
	'googlebot' => 'Google'
);


function update_user_action()
{
	global $pun_user, $db, $lang_online;
	
	if (!isset($lang_online))
	{
		if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/online.php'))
			require PUN_ROOT.'lang/'.$pun_user['language'].'/online.php';
		else
			require PUN_ROOT.'lang/English/online.php';
	}
	
	if (defined('PUN_QUIET_VISIT') || defined('UPDATE_USERS_VIEWING'))
		return;
	
	$file = $url = basename($_SERVER['PHP_SELF']);
	$action = '';
	switch ($file)
	{
		case 'edit.php':
			$action = $lang_online['Editing post'];
			break;
			
		case 'help.php':
			$action = $lang_online['Viewing help'];
			break;
			
		case 'login.php':
			$action = $lang_online['Logging in'];
			break;
			
		case 'online.php':
			$action = $lang_online['Viewing online list'];
			break;

		case 'post.php':
			if (isset($GLOBALS['tid']) && $GLOBALS['tid'] > 0)
			{
				$action = sprintf($lang_online['Replying topic'], pun_htmlspecialchars($GLOBALS['cur_posting']['subject']));
				$url .= '?id='.$GLOBALS['tid'];
			}
			elseif (isset($GLOBALS['fid'])&& $GLOBALS['fid'] > 0)
			{
				$action = sprintf($lang_online['Making new topic'], pun_htmlspecialchars($GLOBALS['cur_posting']['forum_name']));
				$url .= '?id='.$GLOBALS['fid'];
			}

			break;
			
		case 'profile.php':
			if ($GLOBALS['id'] == $pun_user['id'])
			{
				$action = $lang_online['Editing profile'];
				$url .= '?id='.$GLOBALS['id'];
			}
			else
			{
				$action = sprintf($lang_online['Viewing profile'], pun_htmlspecialchars($GLOBALS['user']['username']));
				$url .= '?id='.$GLOBALS['id'];
			}
			break;

		case 'register.php':
			$action = $lang_online['Registering'];
			break;
			
		case 'search.php':
			$action = $lang_online['Searching forum'];
			break;

		case 'userlist.php':
			$action = $lang_online['Viewing userlist'];
			break;

		case 'viewforum.php':
			$action = sprintf($lang_online['Viewing forum'], pun_htmlspecialchars($GLOBALS['cur_forum']['forum_name']));
			$url .= '?id='.$GLOBALS['id'];
			break;
		
		case 'viewtopic.php':
			$action = sprintf($lang_online['Viewing topic'], pun_htmlspecialchars($GLOBALS['cur_topic']['subject']));
			$url .= '?id='.$GLOBALS['id'];
			break;

		// Portal
		case 'about.php':
			$action = 'Przegląda stronę FluxBB';
			$url = '../informacje/';
			break;
			
		case 'convert.php':
			$action = 'Przegląda stronę Konwersja';
			$url = '../pobierz/konwersja/';
			break;

		case 'developers.php':
			$action = 'Przegląda stronę Programiści';
			$url = '../informacje/programisci/';
			break;
	
		case 'development.php':
			$action = 'Przegląda stronę Rozwój';
			$url = '../informacje/rozwoj/';
			break;

		case 'download.php':
			$action = 'Przegląda stronę Pobierz';
			$url = '../pobierz/';
			break;

		case 'features.php':
			$action = 'Przegląda stronę Funkcje';
			$url = '../informacje/funkcje/';
			break;

		case 'upgrade.php':
			$action = 'Przegląda stronę Aktualizacja';
			$url = '../pobierz/aktualizacja/';
			break;

		case 'tutorials.php':
			$url = '../poradniki/';
			if (isset($GLOBALS['cur_topic']))
			{
				$url .= $GLOBALS['id'].'/'.sef_friendly($GLOBALS['cur_topic']['subject']).'/';
				$action = 'Czyta poradnik: '.pun_htmlspecialchars($GLOBALS['cur_topic']['subject']);
			}
			else
				$action = 'Przegląda Poradniki';
			break;

		case 'translation.php':
			$action = 'Przegląda stronę Tłumaczenie';
			$url = '../pobierz/tlumaczenie/';
			break;
	
		default:
			$action = $lang_online['Viewing index'];
			break;
	}
	
	
	if (!$pun_user['is_guest'])
		$sql_where = 'user_id='.$pun_user['id'];
	else
		$sql_where = 'ident=\''.$db->escape(get_remote_address()).'\'';
	
	$db->query('UPDATE '.$db->prefix.'online SET action=\''.$db->escape($action).'\', url=\''.$db->escape($url).'\', user_agent=\''.$db->escape($_SERVER['HTTP_USER_AGENT']).'\' WHERE '.$sql_where) or error('Unable to update user info', __FILE__, __LINE__, $db->error());

	define('UPDATE_USERS_VIEWING', true);
}


function user_agent_name($ident, $user_agent)
{
	global $agent_names;
	foreach ($agent_names as $key => $value)
	{
		if (strpos(strtolower($user_agent), $key) !== false)
			return $value;
	}
	return $ident;
}