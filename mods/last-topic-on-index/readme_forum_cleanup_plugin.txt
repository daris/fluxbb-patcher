
#
#---------[ 1. OPEN ]-----------------------------------------------------------------------------
#
	
plugins/AP_Forum_cleanup.php

#
#---------[ 2. FIND ]------------------------------------------------------------------------
#

	$db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$db->prefix.'forum_last SELECT p.posted AS n_last_post, p.id AS n_last_post_id, p.poster AS n_last_poster, t.forum_id FROM '.$db->prefix.'posts AS p LEFT JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id ORDER BY p.posted DESC') or error('Creating last posts table failed', __FILE__, __LINE__, $db->error());

#
#---------[ 3. REPLACE WITH ]---------------------------------------------------------------------
#

	$db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$db->prefix.'forum_last SELECT p.posted AS n_last_post, p.id AS n_last_post_id, p.poster AS n_last_poster, t.forum_id, t.subject AS n_last_topic FROM '.$db->prefix.'posts AS p LEFT JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id ORDER BY p.posted DESC') or error('Creating last posts table failed', __FILE__, __LINE__, $db->error());

#
#---------[ 4. FIND ]------------------------------------------------------------------------
#
	
	$db->query('UPDATE '.$db->prefix.'forums, '.$db->prefix.'forum_lastb SET last_post_id=n_last_post_id, last_post=n_last_post, last_poster=n_last_poster WHERE id=forum_id') or error('Could not update last post', __FILE__, __LINE__, $db->error());

#
#---------[ 5. REPLACE WITH ]---------------------------------------------------------------------
#

	$db->query('UPDATE '.$db->prefix.'forums, '.$db->prefix.'forum_lastb SET last_post_id=n_last_post_id, last_post=n_last_post, last_poster=n_last_poster, last_topic=n_last_topic WHERE id=forum_id') or error('Could not update last post', __FILE__, __LINE__, $db->error());
