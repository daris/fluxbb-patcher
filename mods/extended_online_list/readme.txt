##
##
##        Mod title:  Extended online list
##
##      Mod version:  1.0.1
##  Works on FluxBB:  1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-07-01
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Adds a browser and system icon into each new post
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##   Affected files:  footer.php
##                    index.php
##                    viewtopic.php
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

files/include/extended_online_list.php to include/extended_online_list.php
files/online.php to online.php
files/lang/English/online.php to lang/English/online.php

#
#---------[ 2. RUN ]----------------------------------------------------------
#

install_mod.php

#
#---------[ 3. DELETE ]-------------------------------------------------------
#

install_mod.php

#
#---------[ 11. OPEN ]--------------------------------------------
#

footer.php

#
#---------[ 12. FIND (line: 346) ]--------------------------------------------
#

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

#
#---------[ 13. BEFORE, ADD ]-------------------------------------------------
#

require_once PUN_ROOT.'include/extended_online_list.php';
update_user_action();

#
#---------[ 11. OPEN ]--------------------------------------------
#

index.php

#
#---------[ 12. FIND (line: 346) ]--------------------------------------------
#

	$result = $db->query('SELECT user_id, ident FROM '.$db->prefix.'online WHERE idle=0 ORDER BY ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

#
#---------[ 13. REPLACE WITH ]-------------------------------------------------
#

	$guests = array();
	require_once PUN_ROOT.'include/extended_online_list.php';
	update_user_action();

	$result = $db->query('SELECT user_id, ident, action, user_agent FROM '.$db->prefix.'online WHERE idle=0 ORDER BY ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

	
#
#---------[ 14. FIND (line: 159) ]---------------------------------------------
#
		else
			++$num_guests;
			
#
#---------[ 14. REPLACE WITH ]---------------------------------------------
#

		else
		{
			if ($pun_user['is_admmod'])
				$guests[] = "\n\t\t\t\t".'<dd><span title="IP: '.pun_htmlspecialchars($pun_user_online['ident']).', '.pun_htmlspecialchars(strip_tags($pun_user_online['action'])).'">'.pun_htmlspecialchars(user_agent_name($pun_user_online['ident'], $pun_user_online['user_agent'])).'</span>';

			++$num_guests;
		}
	
#
#---------[ 14. FIND (line: 159) ]---------------------------------------------
#

				$users[] = "\n\t\t\t\t".'<dd><a href="profile.php?id='.$pun_user_online['user_id'].'">'.pun_htmlspecialchars($pun_user_online['ident']).'</a>';

#
#---------[ 15. REPLACE WITH ]-------------------------------------------------
#

				$users[] = "\n\t\t\t\t".'<dd><a href="profile.php?id='.$pun_user_online['user_id'].'" title="'.pun_htmlspecialchars(strip_tags($pun_user_online['action'])).'">'.pun_htmlspecialchars($pun_user_online['ident']).'</a>';

#
#---------[ 14. FIND (line: 159) ]---------------------------------------------
#

	if ($num_users > 0)
		echo "\t\t\t".'<dl id="onlinelist" class="clearb">'."\n\t\t\t\t".'<dt><strong>'.$lang_index['Online'].' </strong></dt>'."\t\t\t\t".implode(',</dd> ', $users).'</dd>'."\n\t\t\t".'</dl>'."\n";

#
#---------[ 15. REPLACE WITH ]-------------------------------------------------
#

	$link1 = $link2 = '';
	if (!$pun_user['is_guest'])
	{
		$link1 = '<a href="online.php">';
		$link2 = '</a>';
	}
	
	if (count($guests) > 0)
		echo "\t\t\t".'<dl id="onlinelist" class="clearb">'."\n\t\t\t\t".'<dt><strong>'.$link1.$lang_online['Guests online'].$link2.': </strong></dt>'."\t\t\t\t".implode(',</dd> ', $guests).'</dd>'."\n\t\t\t".'</dl>'."\n";

	if ($num_users > 0)
		echo "\t\t\t".'<dl id="onlinelist" class="clearb">'."\n\t\t\t\t".'<dt><strong>'.$link1.$lang_index['Online'].$link2.' </strong></dt>'."\t\t\t\t".implode(',</dd> ', $users).'</dd>'."\n\t\t\t".'</dl>'."\n";

#
#---------[ 16. OPEN ]---------------------------------------------
#

viewtopic.php

#
#---------[ 18. FIND (line: 278) ]--------------------------------------------
#

<?php

// Display quick post if enabled
if ($quickpost)

#
#---------[ 19. BEFORE, ADD ]-------------------------------------------------
#

<?php if (!$pun_user['is_guest'])
{
	require_once PUN_ROOT.'include/extended_online_list.php';
	update_user_action(); // also loads $lang_online
?>
<div id="topic-views" class="block" style="margin-top: 12px">
	<h2><span><?php echo $lang_online['Who is viewing topic'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<p>
<?php

	$users_viewing = $guests_viewing = array();

	$result_t = $db->query('SELECT user_id, ident FROM '.$db->prefix.'online WHERE idle=0 AND url=\'viewtopic.php?id='.$id.'\'') or error('Unable to fetch users browsing topic', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result_t))
	{
		while ($cur_user_t = $db->fetch_assoc($result_t))
		{
			if ($cur_user_t['user_id'] != 1)
				$users_viewing[] = '<a href="profile.php?id='.$cur_user_t['user_id'].'">'.pun_htmlspecialchars($cur_user_t['ident']).'</a>';
			else
				$guests_viewing[] = pun_htmlspecialchars($cur_user_t['ident']);
		}
	}
	
	if (count($users_viewing) > 0)
		echo '<p><strong>'.sprintf($lang_online['Users viewing topic'], count($users_viewing)).'</strong>: '.implode(', ', $users_viewing).'</p>';

	if (count($guests_viewing) > 0)
		echo '<p><strong>'.sprintf($lang_online['Guests viewing topic'], count($guests_viewing)).'</strong>'.($is_admmod ? ': '.implode(', ', $guests_viewing) : '').'</p>';

?>
			</p>
		</div>
	</div>
</div>
<?php } ?>

#
#---------[ 20. SAVE/UPLOAD ]-------------------------------------------------
#