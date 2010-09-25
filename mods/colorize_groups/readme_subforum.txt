Install subforum mod first (this readme is for 1.0.9.5)

#
#---------[ 1. OPEN ]---------------------------------------------
#

index.php

#
#---------[ 2. FIND (If you have last topic on index mod installed, this query may be different so you need to manually modify it :) ]-------------------------------------------------
#

$forums_info = $db->query('SELECT f.num_topics, f.num_posts, f.parent_forum_id, f.last_post_id, f.last_poster, f.last_post, f.id, f.forum_name FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.parent_forum_id <> 0 ORDER BY disp_position') or error(implode($db->error(),''),__FILE__,__LINE__,$db->error());

#
#---------[ 3. REPLACE WITH (Add "u.group_id, u.id AS uid, " (without quotes) after "SELECT" and "LEFT JOIN '.$db->prefix.'users AS u ON (u.username=f.last_poster) " before "WHERE") ]---------------------------------------------
#

$forums_info = $db->query('SELECT u.group_id, u.id AS uid, f.num_topics, f.num_posts, f.parent_forum_id, f.last_post_id, f.last_poster, f.last_post, f.id, f.forum_name FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'users AS u ON (u.username=f.last_poster) LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.parent_forum_id <> 0 ORDER BY disp_position') or error(implode($db->error(),''),__FILE__,__LINE__,$db->error());

#
#---------[ 4. FIND ]-------------------------------------------------
#

					$cur_forum['last_post'] = $cur_subforum['last_post'];

#
#---------[ 5. AFTER, ADD ]---------------------------------------------
#

					$cur_forum['group_id'] = $cur_subforum['group_id'];
					$cur_forum['uid'] = $cur_subforum['uid'];

#
#---------[ 6. OPEN ]---------------------------------------------
#

viewforum.php

#
#---------[ 7. FIND ]-------------------------------------------------
#

	$subforum_result = $db->query('SELECT f.forum_desc, f.forum_name, f.id, f.last_post, f.last_post_id, f.last_poster, f.moderators, f.num_posts, f.num_topics, f.redirect_url FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND parent_forum_id='.$id.' ORDER BY disp_position') or error('Unable to fetch sub forum info',__FILE__,__LINE__,$db->error());

#
#---------[ 8. REPLACE WITH ]-------------------------------------------------
#

	$subforum_result = $db->query('SELECT u.group_id, u.id AS uid, f.forum_desc, f.forum_name, f.id, f.last_post, f.last_post_id, f.last_poster, f.moderators, f.num_posts, f.num_topics, f.redirect_url FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'users AS u ON (u.username=f.last_poster) LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND parent_forum_id='.$id.' ORDER BY disp_position') or error('Unable to fetch sub forum info',__FILE__,__LINE__,$db->error());

#
#---------[ 9. FIND ]-------------------------------------------------
#

			if ($cur_subforum['moderators'] != '')
			{
				$mods_array = unserialize($cur_subforum['moderators']);
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
#---------[ 10. REPLACE WITH ]---------------------------------------------
#

			$last_post = str_replace(pun_htmlspecialchars($cur_subforum['last_poster']).'</span>', colorize_group($cur_subforum['last_poster'], (isset($cur_subforum['group_id']) ? $cur_subforum['group_id'] : PUN_GUEST), (isset($cur_subforum['uid']) ? $cur_subforum['uid'] : 0)).'</span>', $last_post);
			
			if ($cur_subforum['moderators'] != '')
			{
				$mods_array = unserialize($cur_subforum['moderators']);
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
#---------[ 11. SAVE/UPLOAD ]-------------------------------------------------
#