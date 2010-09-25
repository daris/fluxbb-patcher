##
##
##        Mod title:  Track topics in database
##
##      Mod version:  1.0
##  Works on FluxBB:  1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-07-30
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Topics and forums you have read since your last logon 
##                    are saved in the database instead of a tracking cookie 
##                    (useful when you log in from different locations and 
##                    your board has a long visit timeout)
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##   Affected files:  include/functions.php
##                    misc.php
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

include/functions.php

#
#---------[ 5. FIND (line: 10) ]---------------------------------------------
#

				// Special case: We've timed out, but no other user has browsed the forums since we timed out
				if ($pun_user['logged'] < ($now-$pun_config['o_timeout_visit']))
				{
					$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].' WHERE id='.$pun_user['id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());


#
#---------[ 6. REPLACE WITH ]-------------------------------------------------
#

				// Special case: We've timed out, but no other user has browsed the forums since we timed out
				if ($pun_user['logged'] < ($now-$pun_config['o_timeout_visit']))
				{
					$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].', tracked_topics=null WHERE id='.$pun_user['id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());

#
#---------[ 7. FIND (line: 208) ]---------------------------------------------
#

			// If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
			if ($cur_user['logged'] < ($now-$pun_config['o_timeout_visit']))
			{
				$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$cur_user['logged'].' WHERE id='.$cur_user['user_id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());


#
#---------[ 8. REPLACE WITH ]---------------------------------------------------
#

			// If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
			if ($cur_user['logged'] < ($now-$pun_config['o_timeout_visit']))
			{
				$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$cur_user['logged'].', tracked_topics=null WHERE id='.$cur_user['user_id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());
				
#
#---------[ 7. FIND (line: 208) ]---------------------------------------------
#

//
// Save array of tracked topics in cookie
//
function set_tracked_topics($tracked_topics)
{
	global $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $pun_config;

	$cookie_data = '';
	if (!empty($tracked_topics))
	{
		// Sort the arrays (latest read first)
		arsort($tracked_topics['topics'], SORT_NUMERIC);
		arsort($tracked_topics['forums'], SORT_NUMERIC);

		// Homebrew serialization (to avoid having to run unserialize() on cookie data)
		foreach ($tracked_topics['topics'] as $id => $timestamp)
			$cookie_data .= 't'.$id.'='.$timestamp.';';
		foreach ($tracked_topics['forums'] as $id => $timestamp)
			$cookie_data .= 'f'.$id.'='.$timestamp.';';

		// Enforce a 4048 byte size limit (4096 minus some space for the cookie name)
		if (strlen($cookie_data) > 4048)
		{
			$cookie_data = substr($cookie_data, 0, 4048);
			$cookie_data = substr($cookie_data, 0, strrpos($cookie_data, ';')).';';
		}
	}

	forum_setcookie($cookie_name.'_track', $cookie_data, time() + $pun_config['o_timeout_visit']);
	$_COOKIE[$cookie_name.'_track'] = $cookie_data; // Set it directly in $_COOKIE as well
}

#
#---------[ 8. REPLACE WITH ]---------------------------------------------------
#

//
// Save array of tracked topics in cookie
//
function set_tracked_topics($tracked_topics)
{
	global $pun_user, $db;
	if (!empty($tracked_topics))
	{
		// Sort the arrays (latest read first)
		arsort($tracked_topics['topics'], SORT_NUMERIC);
		arsort($tracked_topics['forums'], SORT_NUMERIC);
		$pun_user['tracked_topics'] = serialize($tracked_topics);

		$db->query('UPDATE '.$db->prefix.'users SET tracked_topics=\''.$pun_user['tracked_topics'].'\' WHERE id='.$pun_user['id']) or error('Unable to update tracked topics', __FILE__, __LINE__, $db->error());
	}
}

#
#---------[ 7. FIND (line: 208) ]---------------------------------------------
#

//
// Extract array of tracked topics from cookie
//
function get_tracked_topics()
{
	global $cookie_name;

	$cookie_data = isset($_COOKIE[$cookie_name.'_track']) ? $_COOKIE[$cookie_name.'_track'] : false;
	if (!$cookie_data)
		return array('topics' => array(), 'forums' => array());

	if (strlen($cookie_data) > 4048)
		return array('topics' => array(), 'forums' => array());

	// Unserialize data from cookie
	$tracked_topics = array('topics' => array(), 'forums' => array());
	$temp = explode(';', $cookie_data);
	foreach ($temp as $t)
	{
		$type = substr($t, 0, 1) == 'f' ? 'forums' : 'topics';
		$id = intval(substr($t, 1));
		$timestamp = intval(substr($t, strpos($t, '=') + 1));
		if ($id > 0 && $timestamp > 0)
			$tracked_topics[$type][$id] = $timestamp;
	}

	return $tracked_topics;
}

#
#---------[ 8. REPLACE WITH ]---------------------------------------------------
#

//
// Extract array of tracked topics from cookie
//
function get_tracked_topics()
{
	global $pun_user;
	if($pun_user['tracked_topics'])
		return unserialize($pun_user['tracked_topics']);
	else
		return array('topics' => array(), 'forums' => array());
}


#
#---------[ 4. OPEN ]---------------------------------------------------------
#

misc.php

#
#---------[ 5. FIND (line: 10) ]---------------------------------------------
#

	$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].' WHERE id='.$pun_user['id']) or error('Unable to update user last visit data', __FILE__, __LINE__, $db->error());



#
#---------[ 6. REPLACE WITH ]-------------------------------------------------
#

	$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].', tracked_topics=null WHERE id='.$pun_user['id']) or error('Unable to update user last visit data', __FILE__, __LINE__, $db->error());

#
#---------[ 20. SAVE/UPLOAD ]-------------------------------------------------
#