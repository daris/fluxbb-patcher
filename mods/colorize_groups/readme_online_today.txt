Install online today mod first (this readme is for 1.0)

#
#---------[ 1. OPEN ]---------------------------------------------
#

index.php

#
#---------[ 2. FIND ]-------------------------------------------------
#

	$result = $db->query('SELECT username, id, last_visit FROM '.$db->prefix.'users WHERE last_visit >= '.$todaystamp.' ORDER BY last_visit DESC') or error('Unable to find the list of the users online today', __FILE__, __LINE__, $db->error());

#
#---------[ 3. REPLACE WITH ]---------------------------------------------
#

	$result = $db->query('SELECT username, id, last_visit, group_id FROM '.$db->prefix.'users WHERE last_visit >= '.$todaystamp.' ORDER BY last_visit DESC') or error('Unable to find the list of the users online today', __FILE__, __LINE__, $db->error());

#
#---------[ 4. FIND ]-------------------------------------------------
#

			$users_today[] =  "\n\t\t\t\t".'<dd><span title="'.sprintf($lang_online_today['Last visit'], pun_htmlspecialchars($user_online_today['username']), format_time($user_online_today['last_visit'])).'">'.pun_htmlspecialchars($user_online_today['username']).'</span>';

#
#---------[ 5. AFTER, ADD ]---------------------------------------------
#

		$users_today[count($users_today) - 1] = str_replace(pun_htmlspecialchars($user_online_today['username']).'<', colorize_group($user_online_today['username'], $user_online_today['group_id']).'<', $users_today[count($users_today) - 1]);
