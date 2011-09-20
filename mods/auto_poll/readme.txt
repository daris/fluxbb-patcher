##
##        Mod title:  Auto Poll
##
##      Mod version:  1.1.0
##   Works on PunBB:  1.4.*
##     Release date:  2009-09-07
##           Author:  Koos (pampoen10@yahoo.com)
##  Original Author:  Mediator (med_mediator@hotmail.com)
##     Contributors:  BN (http://la-bnbox.fr), Ishimaru Chiaki (http://ishimaru-design.servhome.org)
##
##      Description:  Adds poll functionality to your forum.
##
##   Affected files:  moderate.php
##                    post.php
##                    edit.php
##                    search.php
##                    viewforum.php
##                    viewtopic.php
##                    include/functions.php
##
##       Affects DB:  New tables:
##                       'polls'
##                    New column in 'topics' table:
##                       'question'
##                    New column in 'forum_perms' table:
##                       'post_polls'
##                    New options in 'config' table:
##                       'o_poll_enabled'
##                       'o_poll_max_fields'
##                       'o_poll_mod_delete_polls'
##                       'o_poll_mod_edit_polls'
##                       'o_poll_mod_reset_polls'
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    PunBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##


#
#---------[ 1. UPLOAD ]-------------------------------------------------
#

files/install_mod.php to /install_mod.php
files/poll_add.php to /poll_add.php
files/poll_misc.php to /poll_misc.php
files/poll_vote.php to /poll_vote.php
files/include/poll to /include/poll
files/plugins/AP_Polls.php to /plugins/AP_Polls.php
files/lang/English/poll.php to /lang/English/poll.php

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

moderate.php


#
#---------[ 5. FIND (line: 431 - 580 for 1.4) ]---------------------------------------------
#

		// Delete any subscriptions
		$db->query('DELETE FROM '.$db->prefix.'topic_subscriptions WHERE topic_id IN('.implode(',', $topics).') AND topic_id != '.$merge_to_tid) or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());

#
#---------[ 6. BEFORE, ADD ]---------------------------------------------------
#

		// POLL MOD: Delete polls
		$db->query('DELETE FROM '.$db->prefix.'polls WHERE pollid IN('.$topics.')') or error('Unable to delete poll', __FILE__, __LINE__, $db->error());


#
#---------[ 7. FIND (line: 606 - 870 for 1.4) ]---------------------------------------------
#

	$result = $db->query('SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC') or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());


#
#---------[ 8. REPLACE WITH ]---------------------------------------------------
#

	$result = $db->query('SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to, question FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC') or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());

#
#---------[ 9. FIND (line: 660 - 898 for 1.4) ]---------------------------------------------
#

		if ($cur_topic['sticky'] == '1')


#
#---------[ 10. BEFORE, ADD ]---------------------------------------------------
#

		// POLL MOD:
		if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php'))
			require PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php';
		else
			require PUN_ROOT.'lang/English/poll.php';
			
		if ($cur_topic['question'] != '')
			$subject = $lang_poll['Poll'] . ': '.$subject;


#
#---------[ 11. OPEN ]---------------------------------------------------------
#

post.php


#
#---------[ 12. FIND (line: 323 - 410 for 1.4) ]---------------------------------------------
#

		redirect('viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $lang_post['Post redirect']);


#
#---------[ 13. BEFORE, ADD ]---------------------------------------------------
#

		require PUN_ROOT.'include/poll/poll_post.php';


#
#---------[ 14. FIND (line: 509 - 633 for 1.4) ]---------------------------------------------
#

if (!$pun_user['is_guest'])
{
	if ($pun_config['o_smilies'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'].'<br /></label>';


#
#---------[ 15. AFTER, ADD ]---------------------------------------------------
#

	require PUN_ROOT.'include/poll/poll_post.php';

#
#---------[ 11. OPEN ]---------------------------------------------------------
#

edit.php

#
#---------[ 12. FIND (line: 323 - 133 for 1.4) ]---------------------------------------------
#

		redirect('viewtopic.php?pid='.$id.'#p'.$id, $lang_post['Edit redirect']);


#
#---------[ 13. BEFORE, ADD ]---------------------------------------------------
#

		require PUN_ROOT.'include/poll/poll_edit.php';


#
#---------[ 14. FIND (line: 509 - 248 for 1.4) ]---------------------------------------------
#

		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'" />'.$lang_post['Hide smilies'].'<br /></label>';
}

#
#---------[ 15. AFTER, ADD ]---------------------------------------------------
#

require PUN_ROOT.'include/poll/poll_edit.php';

#
#---------[ 16. OPEN ]---------------------------------------------------------
#

search.php


#
#---------[ 17. FIND (line: 34 - 15 for 1.4) ]---------------------------------------------
#

// Load the search.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';


#
#---------[ 18. AFTER, ADD ]---------------------------------------------------
#

// POLL MOD: Load the poll.php language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php';
else
	require PUN_ROOT.'lang/English/poll.php';


#
#---------[ 19. FIND (line: 468 - 496 for 1.4) ]---------------------------------------------
#

		if ($show_as == 'posts')
			$result = $db->query('SELECT p.id AS pid, p.poster AS pposter, p.posted AS pposted, p.poster_id, p.message, p.hide_smilies, t.id AS tid, t.poster, t.subject, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id, f.forum_name FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE p.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
		else
			$result = $db->query('SELECT t.id AS tid, t.poster, t.subject, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.closed, t.sticky, t.forum_id, f.forum_name FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE t.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());


#
#---------[ 20. REPLACE WITH ]---------------------------------------------------
#

		if ($show_as == 'posts')
			$result = $db->query('SELECT p.id AS pid, p.poster AS pposter, p.posted AS pposted, p.poster_id, p.message, p.hide_smilies, t.id AS tid, t.poster, t.subject, t.question, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id, f.forum_name FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE p.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
		else
			$result = $db->query('SELECT t.id AS tid, t.poster, t.subject, t.question, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.closed, t.sticky, t.forum_id, f.forum_name FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE t.id IN('.implode(',', $search_ids).') ORDER BY '.$sort_by_sql.' '.$sort_dir) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());


#
#---------[ 21. FIND (line: 554 - 692 for 1.4) ]---------------------------------------------
#

				$subject = '<a href="viewtopic.php?id='.$cur_search['tid'].'">'.pun_htmlspecialchars($cur_search['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_search['poster']).'</span>';


#
#---------[ 22. AFTER, ADD ]---------------------------------------------------
#

				// POLL MOD:
				if ($cur_search['question'] != '')
					$subject = $lang_poll['Poll'].': '.$subject;


#
#---------[ 23. FIND (line: 634 - 717 for 1.4) ]---------------------------------------------
#

					$subject_new_posts = null;


#
#---------[ 24. AFTER, ADD ]---------------------------------------------------
#

				// POLL MOD:
				if ($cur_search['question'] != '')
					$subject =  $lang_poll['Poll'].':  '.$subject;


#
#---------[ 25. OPEN ]---------------------------------------------------------
#

viewforum.php


#
#---------[ 26. FIND (line: 136 - 181 for 1.4) ]---------------------------------------------
#

$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());


#
#---------[ 27. BEFORE, ADD ]---------------------------------------------------
#

require PUN_ROOT.'include/poll/poll_viewforum.php';


#
#---------[ 28. FIND (line: 188 - 231 for 1.4) ]---------------------------------------------
#

		// Insert the status text before the subject
		$subject = implode(' ', $status_text).' '.$subject;

#
#---------[ 29. BEFORE, ADD ]---------------------------------------------------
#

		require PUN_ROOT.'include/poll/poll_viewforum.php';


#
#---------[ 30. OPEN ]---------------------------------------------------------
#

viewtopic.php


#
#---------[ 31. FIND (line: 186 - 223 for 1.4) ]---------------------------------------------
#

// Retrieve the posts (and their respective poster/online status)


#
#---------[ 32. BEFORE, ADD ]---------------------------------------------------
#

require PUN_ROOT.'include/poll/poll_viewtopic.php';


#
#---------[ 33. OPEN ]---------------------------------------------------------
#

include/functions.php


#
#---------[ 34. FIND (line: 388 - 693 for 1.4) ]---------------------------------------------
#

	// Create a list of the post IDs in this topic


#
#---------[ 35. BEFORE, ADD ]---------------------------------------------------
#

	// POLL MOD: Delete the poll
	$db->query('DELETE FROM '.$db->prefix.'polls WHERE pollid='.$topic_id) or error('Unable to delete poll', __FILE__, __LINE__, $db->error());

