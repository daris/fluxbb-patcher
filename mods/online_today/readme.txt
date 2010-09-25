##
##
##        Mod title:  Online today
##
##      Mod version:  1.2
##  Works on FluxBB:  1.4.1, 1.4.0, 1.4-rc3
##     Release date:  2010-08-06
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##   Orginal author:  Vincent Garnier a.k.a. vin100 (vin100@forx.fr)
##
##      Description:  This MOD makes it possible to post the lists of the members who connected themselves to the forums during the day.
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##   Affected files:  index.php
##
##       Affects DB:  No
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at 
##                    your own risk. Backup your forum database and any and
##                    all applicable files before proceeding.
##
##

#
#-------------[ 1. UPLOAD ]-------------------------------------------------------
#

files/lang/English/online_today.php to lang/English/online_today.php

#
#-------------[ 2. OPEN ]--------------------------------------------------------
#

index.php

#
#-------------[ 3. FIND ]--------------------------------------------------------
#

	else
		echo "\t\t\t".'<div class="clearer"></div>'."\n";

#
#-------------[ 4. AFTER, ADD ]--------------------------------------------------
#

	// users online today 
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/online_today.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/online_today.php';
	else
		require PUN_ROOT.'lang/English/online_today.php';

	$date = getdate(time());
	$todaystamp = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);

	$result = $db->query('SELECT username, id, last_visit FROM '.$db->prefix.'users WHERE last_visit >= '.$todaystamp.' ORDER BY last_visit DESC') or error('Unable to find the list of the users online today', __FILE__, __LINE__, $db->error());

	$users_today = array();
	while ($user_online_today = $db->fetch_assoc($result))
	{
		if ($pun_user['g_view_users'] == '1')
			$users_today[] =  "\n\t\t\t\t".'<dd><a href="profile.php?id='.$user_online_today['id'].'" title="'.sprintf($lang_online_today['Last visit'], pun_htmlspecialchars($user_online_today['username']), format_time($user_online_today['last_visit'])).'">'.pun_htmlspecialchars($user_online_today['username']).'</a>';
		else
			$users_today[] =  "\n\t\t\t\t".'<dd><span title="'.sprintf($lang_online_today['Last visit'], pun_htmlspecialchars($user_online_today['username']), format_time($user_online_today['last_visit'])).'">'.pun_htmlspecialchars($user_online_today['username']).'</span>';
	}
	
	if (count($users_today) > 0) 
		echo "\t\t\t".'<dl id="onlinelist" class="clearb">'."\n\t\t\t\t".'<dt><strong>'.$lang_online_today['Online today'].' </strong></dt>'.implode(',</dd> ', $users_today).'</dd>'."\n\t\t\t".'</dl>'."\n";
