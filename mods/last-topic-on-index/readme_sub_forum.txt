Install subforum mod first (this readme is for 1.0.9.5)

#
#---------[ 1. OPEN ]------------------------------------------------------------------------------
#

index.php

#
#---------[ 2. FIND ]-------------------------------------------------------------------------
#

$forums_info = $db->query('SELECT f.num_topics, f.num_posts, f.parent_forum_id, f.last_post_id, f.last_poster, f.last_post, f.id, f.forum_name FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.parent_forum_id <> 0 ORDER BY f.disp_position') or error('Unable to fetch subforum list', __FILE__, __LINE__, $db->error());

#
#---------[ 3. REPLACE WITH ]----------------------------------------------------------------------
#

$forums_info = $db->query('SELECT f.num_topics, f.num_posts, f.parent_forum_id, f.last_post_id, f.last_poster, f.last_post, f.id, f.forum_name, f.last_topic FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.parent_forum_id <> 0 ORDER BY f.disp_position') or error('Unable to fetch subforum list', __FILE__, __LINE__, $db->error());

#
#---------[ 4. FIND ]-------------------------------------------------------------------------
#

					$cur_forum['last_post'] = $cur_subforum['last_post'];

#
#---------[ 5. AFTER, ADD ]----------------------------------------------------------------------
#

					$cur_forum['last_topic'] = $cur_subforum['last_topic'];

#
#---------[ 6. OPEN ]------------------------------------------------------------------------------
#

viewforum.php

#
#---------[ 7. FIND ]-------------------------------------------------------------------------
#

	$subforum_result = $db->query('SELECT f.forum_desc, f.forum_name, f.id, f.last_post, f.last_post_id, f.last_poster, f.moderators, f.num_posts, f.num_topics, f.redirect_url FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND parent_forum_id='.$id.' ORDER BY disp_position') or error('Unable to fetch sub forum info',__FILE__,__LINE__,$db->error());

#
#---------[ 8. REPLACE WITH ]----------------------------------------------------------------------
#

	$subforum_result = $db->query('SELECT f.forum_desc, f.forum_name, f.id, f.last_post, f.last_post_id, f.last_poster, f.moderators, f.num_posts, f.num_topics, f.redirect_url, f.last_topic FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND parent_forum_id='.$id.' ORDER BY disp_position') or error('Unable to fetch sub forum info',__FILE__,__LINE__,$db->error());

#
#---------[ 9. FIND ]------------------------------------------------------------------------
#

	$last_post = '<a href="viewtopic.php?pid='.$cur_subforum['last_post_id'].'#p'.$cur_subforum['last_post_id'].'">'.format_time($cur_subforum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_subforum['last_poster']).'</span>';

#
#---------[ 10. REPLACE WITH ]---------------------------------------------------------------------

	{
		if (pun_strlen($cur_subforum['last_topic']) > 30)
			$cur_subforum['last_topic'] = utf8_substr($cur_subforum['last_topic'], 0, 30).'...';

		if ($cur_subforum['last_topic'] != '')
			$last_post = '<a href="viewtopic.php?pid='.$cur_subforum['last_post_id'].'#p'.$cur_subforum['last_post_id'].'">'.pun_htmlspecialchars($cur_subforum['last_topic']).'</a><br />'.format_time($cur_subforum['last_post']).'<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_subforum['last_poster']).'</span>';
	}