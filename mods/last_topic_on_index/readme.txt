##
##
##        Mod title:  Last Topic on Index
##
##      Mod version:  1.2
##  Works on FluxBB:  1.4.2, 1.4.1, 1.4.0, 1.4-rc3
##     Release date:  2010-08-20
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  daris (daris91@gmail.com)
##
##      Description:  Shows the title of the topic with the latest post on the index page of the forum.
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##   Affected files:  include/functions.php
##					  index.php
##                    edit.php
##
##       Affects DB:  Yes
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##
##

#
#---------[ 1. UPLOAD ]----------------------------------------------------------------------------
#

install_mod.php to /

#
#---------[ 2. RUN ]-------------------------------------------------------------------------------
#

install_mod.php

#
#---------[ 3. DELETE ]----------------------------------------------------------------------------
#

install_mod.php

#
#---------[ 4. OPEN ]------------------------------------------------------------------------------
#

include/functions.php

#
#---------[ 5. FIND ]-------------------------------------------------------------------------
#

	$result = $db->query('SELECT last_post, last_post_id, last_poster FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());

#
#---------[ 6. REPLACE WITH ]----------------------------------------------------------------------
#

	$result = $db->query('SELECT last_post, last_post_id, last_poster, subject FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());

#
#---------[ 7. FIND ]-------------------------------------------------------------------------
#

	list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);

#
#---------[ 8. REPLACE WITH ]----------------------------------------------------------------------
#

	list($last_post, $last_post_id, $last_poster, $last_topic) = $db->fetch_row($result);

#
#---------[ 9. FIND ]-------------------------------------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\' WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());

#		
#---------[ 10. REPLACE WITH ]---------------------------------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\', last_topic=\''.$db->escape($last_topic).'\' WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());

#
#---------[ 11. FIND ]------------------------------------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());

#
#---------[ 12. REPLACE WITH ]---------------------------------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post=NULL, last_post_id=NULL, last_poster=NULL, last_topic=NULL WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster/last_topic', __FILE__, __LINE__, $db->error());

#
#---------[ 13. OPEN ]-----------------------------------------------------------------------------
#

index.php

#
#---------[ 14. FIND (If you installed subforum, this query may be different so you need to manually modify it :) ) ]------------------------------------------------------------------------
#

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 15. REPLACE WITH (Add ", f.last_topic" (without quotes) to SELECT) ]---------------------------------------------------------------------
#

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster, f.last_topic FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 16. FIND ]------------------------------------------------------------------------
#

	$last_post = '<a href="viewtopic.php?pid='.$cur_forum['last_post_id'].'#p'.$cur_forum['last_post_id'].'">'.format_time($cur_forum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_forum['last_poster']).'</span>';

#
#---------[ 17. REPLACE WITH ]---------------------------------------------------------------------

	{
		if (pun_strlen($cur_forum['last_topic']) > 30)
			$cur_forum['last_topic'] = utf8_substr($cur_forum['last_topic'], 0, 30).'...';

		$last_post = '<a href="viewtopic.php?pid='.$cur_forum['last_post_id'].'#p'.$cur_forum['last_post_id'].'">'.pun_htmlspecialchars($cur_forum['last_topic']).'</a><br />'.format_time($cur_forum['last_post']).'<span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_forum['last_poster']).'</span>';
	}

#
#---------[ 18. OPEN ]-----------------------------------------------------------------------------
#

edit.php

#
#---------[ 19. FIND ]------------------------------------------------------------------------
#

$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

#
#---------[ 20. REPLACE WITH ]---------------------------------------------------------------------
#

$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, f.last_post_id, t.id AS tid, t.subject, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

#
#---------[ 21. FIND ]------------------------------------------------------------------------
#

			// Update the topic and any redirect topics
			$db->query('UPDATE '.$db->prefix.'topics SET subject=\''.$db->escape($subject).'\' WHERE id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

#
#---------[ 22. AFTER, ADD ]---------------------------------------------------------------------

			// Is the current topic last?
			$result = $db->query('SELECT 1 FROM '.$db->prefix.'posts WHERE id='.$cur_post['last_post_id'].' AND topic_id='.$cur_post['tid']);
			if ($db->num_rows($result))
				$db->query('UPDATE '.$db->prefix.'forums SET last_topic=\''.$db->escape($subject).'\' WHERE id='.$cur_post['fid']) or error('Unable to update last topic', __FILE__, __LINE__, $db->error());

#
#---------[ 23. SAVE AND UPLOAD ]----------------------------------------------------------------------
#

If you have Subforum mod installed, follow readme_subforum.txt
If you have Forum cleanup plugin installed, follow readme_forum_cleanup_plugin.txt