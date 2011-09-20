##
##
##        Mod title:  Colorize groups
##
##      Mod version:  1.2
##  Works on FluxBB:  1.4.5, 1.4.4, 1.4.3, 1.4.2, 1.4.1, 1.4, 1.4-rc3
##     Release date:  2011-04-03
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Colorizes username based on his group
##
##   Repository URL:  http://fluxbb.org/resources/mods/colorize-groups/
##
##   Affected files:  include/common.php
##                    include/cache.php
##                    header.php
##                    admin_groups.php
##                    index.php
##                    viewforum.php
##                    viewtopic.php
##                    moderate.php
##                    userlist.php
##                    profile.php
##                    post.php
##                    search.php
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

files/install_mod.php to /
files/include/colorize_groups.php to /include/colorize_groups.php
files/lang/English/colorize_groups.php to /lang/English/colorize_groups.php

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

include/common.php

#
#---------[ 5. FIND ]---------------------------------------------
#

// Load cached bans
if (file_exists(FORUM_CACHE_DIR.'cache_bans.php'))
	include FORUM_CACHE_DIR.'cache_bans.php';

#
#---------[ 6. BEFORE, ADD ]-------------------------------------------------
#

require PUN_ROOT.'include/colorize_groups.php';

#
#---------[ 7. OPEN ]---------------------------------------------------------
#

header.php

#
#---------[ 8. FIND ]---------------------------------------------
#

echo implode("\n", $page_head)."\n";

#
#---------[ 9. BEFORE, ADD ]-------------------------------------------------
#

global $pun_colorize_groups; // need this for message function
$page_head['colorize_groups'] = '<style type="text/css">'.$pun_colorize_groups['style'].'</style>';

#
#---------[ 10. OPEN ]--------------------------------------------------------------------------
#

admin_groups.php

#
#---------[ 11. FIND (line: 10) ]---------------------------------------------
#

								<tr>
									<th scope="row"><?php echo $lang_admin_groups['User title label'] ?></th>
									<td>
										<input type="text" name="user_title" size="25" maxlength="50" value="<?php echo pun_htmlspecialchars($group['g_user_title']) ?>" tabindex="2" />
										<span><?php echo $lang_admin_groups['User title help'] ?></span>
									</td>
								</tr>

#
#---------[ 12. AFTER, ADD ]-------------------------------------------------
#

								<tr>
									<th scope="row"><?php echo $lang_colorize_groups['Group color'] ?></th>
									<td>
										<input type="text" name="group_color" size="7" maxlength="7" value="<?php echo $group['g_color'] ?>" tabindex="25" />
										<span><?php echo $lang_colorize_groups['Group color help'] ?></span>
									</td>
								</tr>

#---------[ 13. FIND ]-------------------------------------------------------
#

	$user_title = pun_trim($_POST['user_title']);

#
#---------[ 14. AFTER, ADD ]-----------------------------------------------------------------
#

	$group_color = pun_trim($_POST['group_color']);

#
#---------[ 15. FIND ]-------------------------------------------------------
#

	if ($title == '')
		message($lang_admin_groups['Must enter title message']);

#
#---------[ 16. AFTER, ADD ]-----------------------------------------------------------------
#

	if (!empty($group_color) && !preg_match('/^#([a-fA-F0-9]){6}$/', $group_color))
		message($lang_colorize_groups['Inalid color message']);

#
#---------[ 17. FIND ]-------------------------------------------------------
#
	
		$db->query('INSERT INTO '.$db->prefix.'groups (g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood) VALUES(\''.$db->escape($title).'\', '.$user_title.', '.$moderator.', '.$mod_edit_users.', '.$mod_rename_users.', '.$mod_change_passwords.', '.$mod_ban_users.', '.$read_board.', '.$view_users.', '.$post_replies.', '.$post_topics.', '.$edit_posts.', '.$delete_posts.', '.$delete_topics.', '.$set_title.', '.$search.', '.$search_users.', '.$send_email.', '.$post_flood.', '.$search_flood.', '.$email_flood.')') or error('Unable to add group', __FILE__, __LINE__, $db->error());

#
#---------[ 18. REPLACE WITH ]-----------------------------------------------------------------
#

		$db->query('INSERT INTO '.$db->prefix.'groups (g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood, g_color) VALUES(\''.$db->escape($title).'\', '.$user_title.', '.$moderator.', '.$mod_edit_users.', '.$mod_rename_users.', '.$mod_change_passwords.', '.$mod_ban_users.', '.$read_board.', '.$view_users.', '.$post_replies.', '.$post_topics.', '.$edit_posts.', '.$delete_posts.', '.$delete_topics.', '.$set_title.', '.$search.', '.$search_users.', '.$send_email.', '.$post_flood.', '.$search_flood.', '.$email_flood.', \''.$db->escape($group_color).'\')') or error('Unable to add group', __FILE__, __LINE__, $db->error());

#
#---------[ 19. FIND ]-------------------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'groups SET g_title=\''.$db->escape($title).'\', g_user_title='.$user_title.', g_moderator='.$moderator.', g_mod_edit_users='.$mod_edit_users.', g_mod_rename_users='.$mod_rename_users.', g_mod_change_passwords='.$mod_change_passwords.', g_mod_ban_users='.$mod_ban_users.', g_read_board='.$read_board.', g_view_users='.$view_users.', g_post_replies='.$post_replies.', g_post_topics='.$post_topics.', g_edit_posts='.$edit_posts.', g_delete_posts='.$delete_posts.', g_delete_topics='.$delete_topics.', g_set_title='.$set_title.', g_search='.$search.', g_search_users='.$search_users.', g_send_email='.$send_email.', g_post_flood='.$post_flood.', g_search_flood='.$search_flood.', g_email_flood='.$email_flood.' WHERE g_id='.intval($_POST['group_id'])) or error('Unable to update group', __FILE__, __LINE__, $db->error());

#
#---------[ 20. REPLACE WITH ]-----------------------------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'groups SET g_title=\''.$db->escape($title).'\', g_user_title='.$user_title.', g_moderator='.$moderator.', g_mod_edit_users='.$mod_edit_users.', g_mod_rename_users='.$mod_rename_users.', g_mod_change_passwords='.$mod_change_passwords.', g_mod_ban_users='.$mod_ban_users.', g_read_board='.$read_board.', g_view_users='.$view_users.', g_post_replies='.$post_replies.', g_post_topics='.$post_topics.', g_edit_posts='.$edit_posts.', g_delete_posts='.$delete_posts.', g_delete_topics='.$delete_topics.', g_set_title='.$set_title.', g_search='.$search.', g_search_users='.$search_users.', g_send_email='.$send_email.', g_post_flood='.$post_flood.', g_search_flood='.$search_flood.', g_email_flood='.$email_flood.', g_color=\''.$db->escape($group_color).'\' WHERE g_id='.intval($_POST['group_id'])) or error('Unable to update group', __FILE__, __LINE__, $db->error());
		
#
#---------[ 21. FIND ]-------------------------------------------------------
#

	}

	// Regenerate the quick jump cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

#
#---------[ 22. AFTER, ADD ]-----------------------------------------------------------------
#

	generate_colorize_groups_cache();


#
#---------[ 23. OPEN ]---------------------------------------------------------
#

include/cache.php

#
#---------[ 24. FIND ]---------------------------------------------
#

	$result = $db->query('SELECT id, username FROM '.$db->prefix.'users WHERE group_id!='.PUN_UNVERIFIED.' ORDER BY registered DESC LIMIT 1') or error('Unable to fetch newest registered user', __FILE__, __LINE__, $db->error());

#
#---------[ 25. REPLACE WITH ]-------------------------------------------------
#

	$result = $db->query('SELECT id, username, group_id FROM '.$db->prefix.'users WHERE group_id!='.PUN_UNVERIFIED.' ORDER BY registered DESC LIMIT 1') or error('Unable to fetch newest registered user', __FILE__, __LINE__, $db->error());

#
#---------[ 26. OPEN ]---------------------------------------------------------
#

index.php

#
#---------[ 27. FIND (If you have subforum or last topic on index mod installed, this query may be different so you need to manually modify it :) ) ]---------------------------------------------
#

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 28. REPLACE WITH (Add "u.group_id, u.id AS uid, " (without quotes) after "SELECT" and "LEFT JOIN '.$db->prefix.'users AS u ON (f.last_poster=u.username) " before "WHERE" ) ]-------------------------------------------------
#

$result = $db->query('SELECT u.group_id, u.id AS uid, c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') LEFT JOIN '.$db->prefix.'users AS u ON (f.last_poster=u.username) WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 29. FIND ]---------------------------------------------
#

	if ($cur_forum['moderators'] != '')
	{
		$mods_array = unserialize($cur_forum['moderators']);
		$moderators = array();

		foreach ($mods_array as $mod_username => $mod_id)
		{
			if ($pun_user['g_view_users'] == '1')
				$moderators[] = '<a href="profile.php?id='.$mod_id.'">'.pun_htmlspecialchars($mod_username).'</a>';
			else
				$moderators[] = pun_htmlspecialchars($mod_username);
		}

		$moderators = "\t\t\t\t\t\t\t\t".'<p class="modlist">(<em>'.$lang_common['Moderated by'].'</em> '.implode(', ', $moderators).')</p>'."\n";
	}

#
#---------[ 30. REPLACE WITH ]-------------------------------------------------
#

	if ($cur_forum['last_post'] != '')
	{
		if (isset($cur_forum['group_id'])) // user
			$col_group = colorize_group($cur_forum['last_poster'], $cur_forum['group_id'], $cur_forum['uid']);
		else // guest
			$col_group = colorize_group($cur_forum['last_poster'], PUN_GUEST);
	
		$last_post = str_replace('<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_forum['last_poster']).'</span>', '<span class="byuser">'.$lang_common['by'].' '.$col_group.'</span>', $last_post);
	}
	
	if ($cur_forum['moderators'] != '')
	{
		$mods_array = unserialize($cur_forum['moderators']);
		$moderator_groups = array();
		if (isset($mods_array['groups']))
		{
			$moderator_groups = $mods_array['groups'];
			unset($mods_array['groups']);
		}
		
		if (count($mods_array) > 0)
		{
			$moderators = array();

			foreach ($mods_array as $mod_username => $mod_id)
			{
				if (isset($moderator_groups[$mod_id]))
					$moderators[] = colorize_group($mod_username, $moderator_groups[$mod_id], $mod_id);
				elseif ($pun_user['g_view_users'] == '1')
					$moderators[] = '<a href="profile.php?id='.$mod_id.'">'.pun_htmlspecialchars($mod_username).'</a>';
				else
					$moderators[] = pun_htmlspecialchars($mod_username);
			}

			$moderators = "\t\t\t\t\t\t\t\t".'<p class="modlist">(<em>'.$lang_common['Moderated by'].'</em> '.implode(', ', $moderators).')</p>'."\n";
		}
	}

#
#---------[ 31. FIND ]---------------------------------------------
#

	$stats['newest_user'] = pun_htmlspecialchars($stats['last_user']['username']);

#
#---------[ 32. AFTER, ADD ]-------------------------------------------------
#

$stats['newest_user'] = colorize_group($stats['last_user']['username'], $stats['last_user']['group_id'], $stats['last_user']['id']);

#
#---------[ 33. FIND ]---------------------------------------------
#

	$result = $db->query('SELECT user_id, ident FROM '.$db->prefix.'online WHERE idle=0 ORDER BY ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

#
#---------[ 34. REPLACE WITH ]-------------------------------------------------
#

	$result = $db->query('SELECT user_id, ident, u.group_id FROM '.$db->prefix.'online LEFT JOIN '.$db->prefix.'users AS u ON (ident=u.username) WHERE idle=0 ORDER BY ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

#
#---------[ 35. FIND ]---------------------------------------------
#

			else
				$users[] = "\n\t\t\t\t".'<dd>'.pun_htmlspecialchars($pun_user_online['ident']);

#
#---------[ 36. AFTER, ADD ]-------------------------------------------------
#

			$users[count($users) - 1] = str_replace('">'.pun_htmlspecialchars($pun_user_online['ident']).'</a>', '">'.colorize_group($pun_user_online['ident'], $pun_user_online['group_id']).'</a>', $users[count($users) - 1]);

#
#---------[ 37. FIND ]---------------------------------------------
#

	else
		echo "\t\t\t".'<div class="clearer"></div>'."\n";

#
#---------[ 38. AFTER, ADD ]-------------------------------------------------
#

	$groups = array();
	foreach ($pun_colorize_groups['groups'] as $g_id => $g_title)
	{
		if (!in_array($g_id, array(PUN_GUEST, PUN_MEMBER)))
		{
			$cur_group = colorize_group($g_title, $g_id);
			if ($pun_user['g_view_users'] == 1)
				$cur_group = '<a href="userlist.php?show_group='.$g_id.'">'.$cur_group.'</a>';
			
			$groups[] = "\n\t\t\t\t".'<dd>'.$cur_group.'</dd>';
		}
	}
	
	if (count($groups) > 0)
		echo "\t\t\t".'<dl id="onlinelist" class="clearb">'."\n\t\t\t\t".'<dt><strong>'.$lang_colorize_groups['Legend'].': </strong></dt>'.implode(', ', $groups)."\n\t\t\t".'</dl>'."\n";

#
#---------[ 38. OPEN ]---------------------------------------------------------
#

viewforum.php

#
#---------[ 39. FIND ]---------------------------------------------
#

	$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());

#
#---------[ 40. BEFORE, ADD ]-------------------------------------------------
#

	if ($pun_user['is_guest'] || $pun_config['o_show_dot'] == '0')
		$sql = 'SELECT u.id AS uid, u.group_id, up.id AS up_id, up.group_id AS up_group_id, t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'users AS u ON (t.last_poster=u.username) LEFT JOIN '.$db->prefix.'users AS up ON (t.poster=up.username) WHERE t.forum_id='.$id.' ORDER BY t.sticky DESC, '.(($cur_forum['sort_by'] == '1') ? 't.posted' : 't.last_post').' DESC LIMIT '.$start_from.', '.$pun_user['disp_topics'];
	else
	{
		$sql = str_replace('SELECT', 'SELECT u.id AS uid, u.group_id, up.id AS up_id, up.group_id AS up_group_id, ', $sql);
		$sql = str_replace('WHERE', ' LEFT JOIN '.$db->prefix.'users AS u ON (t.last_poster=u.username) LEFT JOIN '.$db->prefix.'users AS up ON (t.poster=up.username) WHERE', $sql);
	}

#
#---------[ 41. FIND ]---------------------------------------------
#

		// Insert the status text before the subject
		$subject = implode(' ', $status_text).' '.$subject;

#
#---------[ 42. BEFORE, ADD ]-------------------------------------------------
#

		if (isset($cur_topic['up_group_id'])) // user
			$col_group = colorize_group($cur_topic['poster'], $cur_topic['up_group_id'], $cur_topic['up_id']);
		else // guest
			$col_group = colorize_group($cur_topic['poster'], PUN_GUEST);

		$subject = str_replace('<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>', '<span class="byuser">'.$lang_common['by'].' '.$col_group.'</span>', $subject);
		
		if ($cur_topic['last_post'] != '')
		{
			if (isset($cur_topic['group_id'])) // user
				$col_group = colorize_group($cur_topic['last_poster'], $cur_topic['group_id'], $cur_topic['uid']);
			else // guest
				$col_group = colorize_group($cur_topic['last_poster'], PUN_GUEST);
			
			$last_post = str_replace('<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>', '<span class="byuser">'.$lang_common['by'].' '.$col_group.'</span>', $last_post);
		}

#
#---------[ 43. OPEN ]---------------------------------------------------------
#

viewtopic.php

#
#---------[ 44. FIND ]---------------------------------------------
#

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

#
#---------[ 45. BEFORE, ADD ]-------------------------------------------------
#

	if ($cur_post['poster_id'] > 1 && $pun_user['g_view_users'] == '1')
		$username = str_replace('">'.pun_htmlspecialchars($cur_post['username']).'</a>', '">'.colorize_group($cur_post['username'], $cur_post['g_id']).'</a>', $username);
	else
		$username = colorize_group($cur_post['username'], $cur_post['g_id']);
#
#---------[ 46. OPEN ]---------------------------------------------------------
#

moderate.php

#
#---------[ 47. FIND ]---------------------------------------------
#

	$result = $db->query('SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC') or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());

#
#---------[ 48. REPLACE WITH ]-------------------------------------------------
#

	$result = $db->query('SELECT u.id AS uid, u.group_id, up.id AS up_id, up.group_id AS up_group_id, t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'users AS u ON (t.last_poster=u.username) LEFT JOIN '.$db->prefix.'users AS up ON (t.poster=up.username) WHERE t.id IN ('.implode(',', $topic_ids).')'.' ORDER BY t.sticky DESC, t.'.$sort_by.', t.id DESC') or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());

#
#---------[ 49. FIND ]---------------------------------------------
#

		// Insert the status text before the subject
		$subject = implode(' ', $status_text).' '.$subject;

#
#---------[ 50. BEFORE, ADD ]-------------------------------------------------
#

		if (isset($cur_topic['up_group_id'])) // user
			$col_group = colorize_group($cur_topic['poster'], $cur_topic['up_group_id'], $cur_topic['up_id']);
		else // guest
			$col_group = colorize_group($cur_topic['poster'], PUN_GUEST);

		$subject = str_replace('<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>', '<span class="byuser">'.$lang_common['by'].' '.$col_group.'</span>', $subject);
		
		if ($cur_topic['last_post'] != '')
		{
			if (isset($cur_topic['group_id'])) // user
				$col_group = colorize_group($cur_topic['last_poster'], $cur_topic['group_id'], $cur_topic['uid']);
			else // guest
				$col_group = colorize_group($cur_topic['last_poster'], PUN_GUEST);
			
			$last_post = str_replace('<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>', '<span class="byuser">'.$lang_common['by'].' '.$col_group.'</span>', $last_post);
		}


#
#---------[ 51. OPEN ]---------------------------------------------------------
#

userlist.php

#
#---------[ 52. FIND ]---------------------------------------------
#

					<td class="tcl"><?php echo '<a href="profile.php?id='.$user_data['id'].'">'.pun_htmlspecialchars($user_data['username']).'</a>' ?></td>


#
#---------[ 53. REPLACE WITH ]-------------------------------------------------
#

					<td class="tcl"><?php echo '<a href="profile.php?id='.$user_data['id'].'">'.colorize_group($user_data['username'], $user_data['g_id']).'</a>' ?></td>
					
#
#---------[ 54. OPEN ]---------------------------------------------------------
#

profile.php

#
#---------[ 55. FIND ]---------------------------------------------
#

	$user_personal[] = '<dd>'.pun_htmlspecialchars($user['username']).'</dd>';

#
#---------[ 56. REPLACE WITH ]-------------------------------------------------
#

	$user_personal[] = '<dd>'.colorize_group($user['username'], $user['g_id']).'</dd>';

#
#---------[ 58. FIND ]---------------------------------------------
#

				$username = array_search($id, $cur_moderators);
				unset($cur_moderators[$username]);

#
#---------[ 59. AFTER, ADD ]-------------------------------------------------
#

				unset($cur_moderators['groups'][$id]);
				if (empty($cur_moderators['groups']))
					unset($cur_moderators['groups']);

#
#---------[ 58. FIND ]---------------------------------------------
#

	}

	redirect('profile.php?section=admin&amp;id='.$id, $lang_profile['Group membership redirect']);

#
#---------[ 59. BEFORE, ADD ]-------------------------------------------------
#
	}
	
	// Else update moderator's group_id
	else
	{
		$result = $db->query('SELECT id, moderators FROM '.$db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());

		while ($cur_forum = $db->fetch_assoc($result))
		{
			$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

			if (in_array($id, $cur_moderators))
			{
				$cur_moderators['groups'][$id] = $new_group_id;
				$db->query('UPDATE '.$db->prefix.'forums SET moderators=\''.$db->escape(serialize($cur_moderators)).'\' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
			}
		}

#
#---------[ 58. FIND ]---------------------------------------------
#

	// Get the username of the user we are processing
	$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	$username = $db->result($result);
	
#
#---------[ 58. REPLACE WITH ]---------------------------------------------
#

	// Get the username of the user we are processing
	$result = $db->query('SELECT username, group_id FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	list($username, $group_id) = $db->fetch_row($result);

#
#---------[ 58. FIND ]---------------------------------------------
#

		// If the user should have moderator access (and he/she doesn't already have it)
		if (in_array($cur_forum['id'], $moderator_in) && !in_array($id, $cur_moderators))

#
#---------[ 59. BEFORE, ADD ]-------------------------------------------------
#

		if (in_array($cur_forum['id'], $moderator_in) || in_array($id, $cur_moderators))
		{
			if (!isset($cur_moderators['groups']))
				$cur_moderators['groups'] = array();
			$cur_moderators['groups'][$id] = $group_id;
		}

#
#---------[ 58. FIND ]---------------------------------------------
#

		else if (!in_array($cur_forum['id'], $moderator_in) && in_array($id, $cur_moderators))
		{
			unset($cur_moderators[$username]);

#
#---------[ 58. AFTER, ADD ]---------------------------------------------
#

			unset($cur_moderators['groups'][$id]);
			if (empty($cur_moderators['groups']))
					unset($cur_moderators['groups']);

#
#---------[ 58. FIND ]---------------------------------------------
#

	}

	redirect('profile.php?section=admin&amp;id='.$id, $lang_profile['Update forums redirect']);

#
#---------[ 59. BEFORE, ADD ]-------------------------------------------------
#

		elseif (in_array($cur_forum['id'], $moderator_in) || in_array($id, $cur_moderators))
			$db->query('UPDATE '.$db->prefix.'forums SET moderators=\''.$db->escape(serialize($cur_moderators)).'\' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $db->error());

#
#---------[ 57. OPEN ]---------------------------------------------------------
#

post.php

#
#---------[ 58. FIND ]---------------------------------------------
#

	$result = $db->query('SELECT poster, message, hide_smilies, posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT '.$pun_config['o_topic_review']) or error('Unable to fetch topic review', __FILE__, __LINE__, $db->error());

#
#---------[ 59. REPLACE WITH ]-------------------------------------------------
#

	$result = $db->query('SELECT p.poster, p.message, p.hide_smilies, p.posted, u.group_id FROM '.$db->prefix.'posts AS p LEFT JOIN '.$db->prefix.'users AS u ON (p.poster=u.username) WHERE p.topic_id='.$tid.' ORDER BY p.id DESC LIMIT '.$pun_config['o_topic_review']) or error('Unable to fetch topic review', __FILE__, __LINE__, $db->error());


#
#---------[ 60. FIND ]---------------------------------------------
#

							<dt><strong><?php echo pun_htmlspecialchars($cur_post['poster']) ?></strong></dt>

#
#---------[ 61. REPLACE WITH ]-------------------------------------------------
#

							<dt><strong><?php echo colorize_group($cur_post['poster'], $cur_post['group_id']) ?></strong></dt>
							
#
#---------[ 62. OPEN ]---------------------------------------------------------
#

search.php

#
#---------[ 63. FIND ]---------------------------------------------
#

		if ($show_as == 'posts')
			$result = $db->query('SELECT p.id AS pid, p.poster AS pposter, p.posted AS pposted, p.poster_id, p.message, p.hide_smilies, t.id AS tid, t.poster, t.subject, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id, f.forum_name FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE p.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
		else
			$result = $db->query('SELECT t.id AS tid, t.poster, t.subject, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.closed, t.sticky, t.forum_id, f.forum_name FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE t.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());

#
#---------[ 64. REPLACE WITH ]-------------------------------------------------
#

		if ($show_as == 'posts')
			$result = $db->query('SELECT u.group_id, p.id AS pid, p.poster AS pposter, p.posted AS pposted, p.poster_id, p.message, p.hide_smilies, t.id AS tid, t.poster, t.subject, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id, f.forum_name FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'users AS u ON (p.poster_id=u.id) WHERE p.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
		else
			$result = $db->query('SELECT u.id AS uid, u.group_id, up.id AS up_id, up.group_id AS up_group_id, t.id AS tid, t.poster, t.subject, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.closed, t.sticky, t.forum_id, f.forum_name FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'users AS u ON (t.last_poster=u.username) LEFT JOIN '.$db->prefix.'users AS up ON (t.poster=up.username) WHERE t.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());

#
#---------[ 65. FIND ]---------------------------------------------
#

				$pposter = pun_htmlspecialchars($cur_search['pposter']);

#
#---------[ 66. REPLACE WITH ]-------------------------------------------------
#

				$pposter = colorize_group($cur_search['pposter'], $cur_search['group_id']);

#
#---------[ 67. FIND ]---------------------------------------------
#
				// Insert the status text before the subject
				$subject = implode(' ', $status_text).' '.$subject;

#
#---------[ 68. BEFORE, ADD ]-------------------------------------------------
#

				if (isset($cur_search['up_group_id'])) // user
					$col_group = colorize_group($cur_search['poster'], $cur_search['up_group_id'], $cur_search['up_id']);
				else // guest
					$col_group = colorize_group($cur_search['poster'], PUN_GUEST);
				
				$subject = str_replace('<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_search['poster']).'</span>', '<span class="byuser">'.$lang_common['by'].' '.$col_group.'</span>', $subject);

#
#---------[ 69. FIND ]---------------------------------------------
#

					<td class="tcr"><?php echo '<a href="viewtopic.php?pid='.$cur_search['last_post_id'].'#p'.$cur_search['last_post_id'].'">'.format_time($cur_search['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_search['last_poster']) ?></span></td>


#
#---------[ 70. REPLACE WITH ]-------------------------------------------------
#

					<td class="tcr"><?php echo '<a href="viewtopic.php?pid='.$cur_search['last_post_id'].'#p'.$cur_search['last_post_id'].'">'.format_time($cur_search['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.(isset($cur_search['group_id']) ? colorize_group($cur_search['last_poster'], $cur_search['group_id'], $cur_search['uid']) : colorize_group($cur_search['last_poster'], PUN_GUEST)) ?></span></td>
#
#---------[ 71. DELETE (if exist) ]-------------------------------------------------
#

cache/cache_users_info.php

#
#---------[ 71. INFORMATION ]-------------------------------------------------
#

If you have subforum mod installed, follow also steps from readme_sub_forum.txt
If you have online today mod installed, follow also steps from readme_online_today.txt

#
#---------[ 72. SAVE/UPLOAD ]-------------------------------------------------
#