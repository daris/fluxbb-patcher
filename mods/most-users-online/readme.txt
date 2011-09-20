##
##
##        Mod title:  Most users online
##
##      Mod version:  1.1
##  Works on FluxBB:  1.4.5, 1.4.4, 1.4.3, 1.4.2, 1.4.1, 1.4.0, 1.4-rc3
##     Release date:  2011-04-11
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  This adds a count of the most users that were online at one time.
##
##   Repository URL:  http://fluxbb.org/resources/mods/most-users-online/
##
##   Affected files:  index.php
##
##       Affects DB:  Yes
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at 
##                    your own risk. Backup your forum database and any and
##                    all applicable files before proceeding.
##
##


#
#---------[ 1. UPLOAD ]-------------------------------------------------------
#

install_mod.php to /
files/lang/English/most_users_online.php to /lang/English/most_users_online.php

#
#---------[ 2. RUN ]----------------------------------------------------------
#

install_mod.php

#
#---------[ 3. DELETE ]-------------------------------------------------------
#

install_mod.php

#
#---------[ 4. OPEN ]---------------------------------------------------------
#

index.php

#
#---------[ 5. FIND ]---------------------------------------------
#

	$num_users = count($users);

#
#---------[ 6. AFTER, ADD ]-------------------------------------------------
#

	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/most_users_online.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/most_users_online.php';
	else
		require PUN_ROOT.'lang/English/most_users_online.php';

	if ($pun_config['most_users_online'] < $num_users + $num_guests)
	{
		$pun_config['most_users_online'] = $num_users + $num_guests;
		$pun_config['most_users_online_date'] = time();
		
		$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$pun_config['most_users_online'].' WHERE conf_name=\'most_users_online\'')  or error('Unable to update config', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$pun_config['most_users_online_date'].' WHERE conf_name=\'most_users_online_date\'')  or error('Unable to update config', __FILE__, __LINE__, $db->error());

		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require PUN_ROOT.'include/cache.php';
		generate_config_cache();
	}
	echo "\t\t\t\t".'<dd><span>'.sprintf($lang_most_users_online['Most users online'], '<strong>'.forum_number_format($pun_config['most_users_online']).'</strong>', format_time($pun_config['most_users_online_date'])).'</span></dd>'."\n";
