
#
#---------[ 1. OPEN ]---------------------------------------------------------
#

include/quick_mod_tools/topic.php

#
#---------[ 2. FIND ]---------------------------------------------
#

$result = $db->query($sql) or error('Unable to fetch topic', __FILE__, __LINE__, $db->error());

#
#---------[ 3. BEFORE, ADD ]-------------------------------------------------
#

if ($pun_user['is_guest'] || $pun_config['o_show_dot'] == '0')
	$sql = 'SELECT u.id AS uid, u.group_id, up.id AS up_id, up.group_id AS up_group_id, t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'users AS u ON (t.last_poster=u.username) LEFT JOIN '.$db->prefix.'users AS up ON (t.poster=up.username) WHERE t.id = '.$topic_id.' LIMIT 1';
else
{
	$sql = str_replace('SELECT', 'SELECT u.id AS uid, u.group_id, up.id AS up_id, up.group_id AS up_group_id, ', $sql);
	$sql = str_replace('WHERE', ' LEFT JOIN '.$db->prefix.'users AS u ON (t.last_poster=u.username) LEFT JOIN '.$db->prefix.'users AS up ON (t.poster=up.username) WHERE', $sql);
}

#
#---------[ 4. FIND ]---------------------------------------------
#

	// Insert the status text before the subject
	$subject = implode(' ', $status_text).' '.$subject;

#
#---------[ 5. BEFORE, ADD ]-------------------------------------------------
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
