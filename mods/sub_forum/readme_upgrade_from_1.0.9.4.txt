
#
#---------[ 1. OPEN ]---------------------------------------------------------
#

index.php

#
#---------[ 2. FIND ]---------------------------------------------
#

################################################################################
########################### Sub Forum MOD (start) ##############################
################################################################################
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php';
else
	require PUN_ROOT.'lang/English/sub_forum.php';

$sfcount = 0;
$sfdb = array();

$forums_info = $db->query('SELECT f.num_topics, f.num_posts, f.parent_forum_id, f.last_post_id, f.last_poster, f.last_post, f.id, f.forum_name, p.poster_id as last_poster_id, t.subject FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'posts AS p ON (p.id=f.last_post_id) LEFT JOIN '.$db->prefix.'topics AS t ON t.last_post_id=f.last_post_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) ORDER BY disp_position') or error(implode($db->error(),''),__FILE__,__LINE__,$db->error());

while($current = $db->fetch_assoc($forums_info)) 
{
	if ($current['parent_forum_id'] != 0)
	{
		$sfdb[$sfcount] = $current;

		$sfcount++;
	}
}
################################################################################
########################### Sub Forum MOD ( end ) ##############################
################################################################################

#
#---------[ 3. REPLACE WITH ]---------------------------------------------
#

################################################################################
########################### Sub Forum MOD (start) ##############################
################################################################################
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php';
else
	require PUN_ROOT.'lang/English/sub_forum.php';

$sfdb = array();

$forums_info = $db->query('SELECT f.num_topics, f.num_posts, f.parent_forum_id, f.last_post_id, f.last_poster, f.last_post, f.id, f.forum_name FROM '.$db->prefix.'forums AS f WHERE f.parent_forum_id <> 0 ORDER BY disp_position') or error(implode($db->error(),''),__FILE__,__LINE__,$db->error());

while($current = $db->fetch_assoc($forums_info)) 
{
	if (!isset($sfdb[$current['parent_forum_id']]))
		$sfdb[$current['parent_forum_id']] = array();
		
	$sfdb[$current['parent_forum_id']][] = $current;
}
################################################################################
########################### Sub Forum MOD ( end ) ##############################
################################################################################

#
#---------[ 4. FIND (line: 65) ]---------------------------------------------
#

// Print the categories and forums
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster, f.parent_forum_id, p.poster_id as last_poster_id, t.subject FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'topics AS t ON t.last_post_id=f.last_post_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') LEFT JOIN '.$db->prefix.'posts AS p ON (p.id=f.last_post_id) WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND (f.parent_forum_id IS NULL OR f.parent_forum_id=0) ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 5. REPLACE WITH ]-------------------------------------------------
#

// Print the categories and forums
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster, f.parent_forum_id FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND (f.parent_forum_id IS NULL OR f.parent_forum_id=0) ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 6. FIND (line: 125) ]---------------------------------------------
#

################################################################################
########################### Sub Forum MOD (start) ##############################
################################################################################
		$n_t = 0;
		$n_p = 0;
		for ($i = 0; $i < $sfcount; $i++)
		{
			if ($sfdb[$i]['parent_forum_id'] == $cur_forum['fid'])
			{
				$n_t = $n_t + $sfdb[$i]['num_topics'];
				$n_p = $n_p + $sfdb[$i]['num_posts'];
				if ($cur_forum['last_post_id'] < $sfdb[$i]['last_post_id'])
				{
					$cur_forum['last_post_id'] = $sfdb[$i]['last_post_id'];
					$cur_forum['last_poster'] = $sfdb[$i]['last_poster'];
					$cur_forum['last_poster_id'] = $sfdb[$i]['last_poster_id'];
					$cur_forum['last_post'] = $sfdb[$i]['last_post'];
					$cur_forum['subject'] = $sfdb[$i]['subject'];
				}
			}
		}
		$num_topics = $n_t + $cur_forum['num_topics'];
		$num_posts = $n_p + $cur_forum['num_posts'];
################################################################################
########################### Sub Forum MOD ( end ) ##############################
################################################################################

#
#---------[ 7. REPLACE WITH ]---------------------------------------------
#

		$num_topics = $cur_forum['num_topics'];
		$num_posts = $cur_forum['num_posts'];

################################################################################
########################### Sub Forum MOD (start) ##############################
################################################################################
		if (isset($sfdb[$cur_forum['fid']]))
		{
			foreach ($sfdb[$cur_forum['fid']] as $cur_subforum)
			{
				$num_topics += $cur_subforum['num_topics'];
				$num_posts += $cur_subforum['num_posts'];
				if ($cur_forum['last_post_id'] < $cur_subforum['last_post_id'])
				{
					$cur_forum['last_post_id'] = $cur_subforum['last_post_id'];
					$cur_forum['last_poster'] = $cur_subforum['last_poster'];
					$cur_forum['last_post'] = $cur_subforum['last_post'];
				}
			}
		}
################################################################################
########################### Sub Forum MOD ( end ) ##############################
################################################################################

#
#---------[ 8. FIND (line: 158) ]---------------------------------------------
#

	// Display the last topic
    $subject = $cur_forum['subject'];
    if (pun_strlen($subject) > 26)
	{
        $subject_title = ' title="'.pun_htmlspecialchars($subject).'"';
        $subject = utf8_substr($subject, 0, 26).'...';
    } else
        $subject_title = '';
 
    // If there is a last_post/last_poster.
    if ($cur_forum['last_post'] != '')
	{
		$last_poster = pun_htmlspecialchars($cur_forum['last_poster']);
		if ($pun_user['g_view_users'] == 1 && $cur_forum['last_poster_id'] > 1)
			$last_poster = '<a href="profile.php?id='.$cur_forum['last_poster_id'].'">'.$last_poster.'</a>';
		$last_post = '<a href="viewtopic.php?pid='.$cur_forum['last_post_id'].'#p'.$cur_forum['last_post_id'].'"'.$subject_title.'>'.pun_htmlspecialchars($subject).'</a><br />'.format_time($cur_forum['last_post']).'<br /><span class="byuser">'.$lang_common['by'].' '.$last_poster.'</span>';
	}
    else
        $last_post = '&nbsp;';

#
#---------[ 9. REPLACE WITH ]-------------------------------------------------
#

	// If there is a last_post/last_poster
	if ($cur_forum['last_post'] != '')
		$last_post = '<a href="viewtopic.php?pid='.$cur_forum['last_post_id'].'#p'.$cur_forum['last_post_id'].'">'.format_time($cur_forum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_forum['last_poster']).'</span>';
	else if ($cur_forum['redirect_url'] != '')
		$last_post = '- - -';
	else
		$last_post = $lang_common['Never'];

#
#---------[ 10. FIND ]---------------------------------------------
#
		
	// Are there new posts since our last visit?
	if (!empty($sfdb))
	{
		foreach ($sfdb as $sub_forums)
		{
			if (!$pun_user['is_guest'] && $cur_forum['fid'] == $sub_forums['parent_forum_id'] && !$pun_user['is_guest'] && $sub_forums['last_post'] > $pun_user['last_visit'] && (empty($tracked_topics['forums'][$sub_forums['id']]) || $cur_forum['last_post'] > $tracked_topics['forums'][$sub_forums['id']]))
			{
				
				// There are new posts in this forum, but have we read all of them already?
				foreach ($new_topics[$sub_forums['id']] as $check_topic_id => $check_last_post)
				{
					if ((empty($tracked_topics['topics'][$check_topic_id]) || $tracked_topics['topics'][$check_topic_id] < $check_last_post) && (empty($tracked_topics['forums'][$sub_forums['id']]) || $tracked_topics['forums'][$sub_forums['id']] < $check_last_post))
					{
						$item_status .= ' inew';
						$forum_field_new = '<span class="newtext">[ <a href="search.php?action=show_new&amp;fid='.$cur_forum['fid'].'">'.$lang_common['New posts'].'</a> ]</span>';
						$icon_type = 'icon icon-new';

						break;
					}
				}
			}
		}
	}

#
#---------[ 11. REPLACE WITH ]-------------------------------------------------
#

	// Are there new posts since our last visit?
	if (!empty($sfdb) && isset($sfdb[$cur_forum['fid']]))
	{
		foreach ($sfdb[$cur_forum['fid']] as $cur_subforum)
		{
			if (!$pun_user['is_guest'] && $cur_subforum['last_post'] > $pun_user['last_visit'] && (empty($tracked_topics['forums'][$cur_subforum['id']]) || $cur_forum['last_post'] > $tracked_topics['forums'][$cur_subforum['id']]))
			{
				// There are new posts in this forum, but have we read all of them already?
				foreach ($new_topics[$cur_subforum['id']] as $check_topic_id => $check_last_post)
				{
					if ((empty($tracked_topics['topics'][$check_topic_id]) || $tracked_topics['topics'][$check_topic_id] < $check_last_post) && (empty($tracked_topics['forums'][$cur_subforum['id']]) || $tracked_topics['forums'][$cur_subforum['id']] < $check_last_post))
					{
						$item_status .= ' inew';
						$forum_field_new = '<span class="newtext">[ <a href="search.php?action=show_new&amp;fid='.$cur_forum['fid'].'">'.$lang_common['New posts'].'</a> ]</span>';
						$icon_type = 'icon icon-new';

						break;
					}
				}
			}
		}
	}

#
#---------[ 12. FIND ]---------------------------------------------
#

				$sub_forums_list = array();
				if (!empty($sfdb))
				{
					foreach ($sfdb as $sub_forums)
					{
						if ($cur_forum['fid'] == $sub_forums['parent_forum_id'])
							$sub_forums_list[] = '<a class="subforum_name" href="viewforum.php?id='.$sub_forums['id'].'">'.pun_htmlspecialchars($sub_forums['forum_name']).'</a>';
					}

#
#---------[ 13. REPLACE WITH ]-------------------------------------------------
#

				$sub_forums_list = array();
				if (!empty($sfdb) && isset($sfdb[$cur_forum['fid']]))
				{
					foreach ($sfdb[$cur_forum['fid']] as $cur_subforum)
						$sub_forums_list[] = '<a class="subforum_name" href="viewforum.php?id='.$cur_subforum['id'].'">'.pun_htmlspecialchars($cur_subforum['forum_name']).'</a>';

#
#---------[ 14. OPEN ]---------------------------------------------------------
#

viewforum.php

#
#---------[ 15. FIND ]---------------------------------------------
#

$subforum_result = $db->query('SELECT f.forum_desc, f.forum_name, f.id, f.last_post, f.last_post_id, f.last_poster, f.moderators, f.num_posts, f.num_topics, f.redirect_url, p.poster_id AS last_poster_id, t.subject FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'topics AS t ON t.last_post_id=f.last_post_id LEFT JOIN '.$db->prefix.'posts AS p ON (p.id=f.last_post_id) LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND parent_forum_id='.$id.' ORDER BY disp_position') or error('Unable to fetch sub forum info',__FILE__,__LINE__,$db->error());

#
#---------[ 16. REPLACE WITH ]---------------------------------------------
#

$subforum_result = $db->query('SELECT f.forum_desc, f.forum_name, f.id, f.last_post, f.last_post_id, f.last_poster, f.moderators, f.num_posts, f.num_topics, f.redirect_url FROM '.$db->prefix.'forums AS f WHERE parent_forum_id='.$id.' ORDER BY disp_position') or error('Unable to fetch sub forum info',__FILE__,__LINE__,$db->error());

#
#---------[ 17. FIND (line: 191) ]---------------------------------------------
#

	// Display the last topic
	$subject = $cur_subforum['subject'];
	if (pun_strlen($subject) > 26)
	{
		$subject_title = ' title="'.pun_htmlspecialchars($subject).'"';
		$subject = substr($subject, 0, 26).'...';
	} else
		$subject_title = '';

	// If there is a last_post/last_poster.
	if ($cur_subforum['last_post'] != '')
	{
		$last_poster = pun_htmlspecialchars($cur_subforum['last_poster']);
		if ($pun_user['g_view_users'] == 1 && $cur_subforum['last_poster_id'] > 1)
			$last_poster = '<a href="profile.php?id='.$cur_subforum['last_poster_id'].'">'.$last_poster.'</a>';
		$last_post = '<a href="viewtopic.php?pid='.$cur_subforum['last_post_id'].'#p'.$cur_subforum['last_post_id'].'"'.$subject_title.'>'.pun_htmlspecialchars($subject).'</a><br />'.format_time($cur_subforum['last_post']).'<br /><span class="byuser">'.$lang_common['by'].' '.$last_poster.'</span>';
	}
	else
		$last_post = '&nbsp;';

#
#---------[ 18. REPLACE WITH ]---------------------------------------------
#

	// If there is a last_post/last_poster
	if ($cur_subforum['last_post'] != '')
		$last_post = '<a href="viewtopic.php?pid='.$cur_subforum['last_post_id'].'#p'.$cur_subforum['last_post_id'].'">'.format_time($cur_subforum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_subforum['last_poster']).'</span>';
	else if ($cur_subforum['redirect_url'] != '')
		$last_post = '- - -';
	else
		$last_post = $lang_common['Never'];

#
#---------[ 19. NOTE ]---------------------------------------------
#

If you want to display last topic subject, install last_topic_on_index modification.

#
#---------[ 20. SAVE/UPLOAD ]-------------------------------------------------
#
