##
##
##        Mod title:  Ajax Quick Post
##
##      Mod version:  2.0.2
##  Works on FluxBB:  1.4.2, 1.4.1, 1.4.0
##     Release date:  2010-07-31
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Allows quickly post a reply (using ajax)
##
##        Upgrading:  Follow readme_upgrade_from_1.0.txt instead
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##        Changelog:  2.0.2: Close database transaction before exiting script
##                    (needed for other db types than mysql)
##                    2.0.1: Update for FluxBB 1.4.2
##                    2.0: Using orginal FluxBB post.php and viewtopic.php
##                    files instead of modified ones in ajax_quick_post
##                    directory.
##
##   Affected files:  viewtopic.php
##                    header.php
##                    footer.php
##                    include/functions.php
##                    post.php
##
##       Affects DB:  No
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

files/include/ajax_quick_post/aqp.js to include/ajax_quick_post/aqp.js
files/img/ajax_quick_post/loading.gif to img/ajax_quick_post/loading.gif

#
#---------[ 2. OPEN ]---------------------------------------------------------
#

viewtopic.php

#
#---------[ 3. FIND ]---------------------------------------------
#

// Fetch some info about the topic
if (!$pun_user['is_guest'])
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
	
#
#---------[ 4. REPLACE WITH (add t.last_post_id to SELECT) ]-------------------------------------------------
#

// Fetch some info about the topic
if (!$pun_user['is_guest'])
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, t.last_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, t.last_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

#
#---------[ 5. FIND ]---------------------------------------------
#

$post_count = 0; // Keep track of post numbers

// Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$id.' ORDER BY id LIMIT '.$start_from.','.$pun_user['disp_posts']) or error('Unable to fetch post IDs', __FILE__, __LINE__, $db->error());

#
#---------[ 6. REPLACE WITH ]-------------------------------------------------
#

$post_count = isset($_GET['pcount']) ? intval($_GET['pcount']) : 0; // Keep track of post numbers

// Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$id.(isset($_GET['lpid']) ? ' AND id > '.intval($_GET['lpid']) : '').' ORDER BY id LIMIT '.$start_from.','.$pun_user['disp_posts']) or error('Unable to fetch post IDs', __FILE__, __LINE__, $db->error());

#
#---------[ 7. FIND ]---------------------------------------------
#

<div class="postlinksb">

#
#---------[ 8. BEFORE, ADD ]-------------------------------------------------
#

<div id="aqp"></div>

#
#---------[ 9. FIND (If you are using FluxBB older than 1.4.2, remove id="quickpostform" from the following) ]---------------------------------------------
#

		<form id="quickpostform" method="post" action="post.php?tid=<?php echo $id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">

#
#---------[ 10. REPLACE WITH ]-------------------------------------------------
#

		<script type="text/javascript">
			var aqp_last_post_id = <?php echo $cur_topic['last_post_id'] ?>; 
			var aqp_post_count = <?php echo $start_from + $post_count ?>;
			var aqp_tid = <?php echo $id ?>;
		</script>
		<form id="quickpostform" method="post" action="post.php?tid=<?php echo $id ?>" onsubmit="if (aqp_post(this)) {return true;} else {return false;}">

#
#---------[ 11. FIND ]--------------------------------------------
#

			<p class="buttons"><input type="submit" name="submit" tabindex="2" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /></p>

#
#---------[ 12. REPLACE WITH ]-----------------------------------------------
#

			<p class="buttons">
				<input type="submit" name="submit" tabindex="2" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /> 
				<span id="aqp-icon" style="background: url(img/ajax_quick_post/loading.gif) no-repeat; padding: 1px 8px; margin-left: 5px; display: none;"></span>
			</p>

#
#---------[ 13. OPEN ]---------------------------------------------------------
#

header.php

#
#---------[ 14. FIND ]---------------------------------------------
#

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

#
#---------[ 15. AFTER, ADD ]---------------------------------------------------
#

if (isset($_GET['ajax']))
	return;

#
#---------[ 19. FIND ]---------------------------------------------
#

echo '<!--[if lte IE 6]><script type="text/javascript" src="style/imports/minmax.js"></script><![endif]-->'."\n";

#
#---------[ 17. AFTER, ADD ]---------------------------------------------------
#

if (basename($_SERVER['PHP_SELF']) == 'viewtopic.php')
{
	$page_head['jquery'] = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>';
	echo '<script type="text/javascript" src="'.$pun_config['o_base_url'].'/include/ajax_quick_post/aqp.js"></script>'."\n";
}

#
#---------[ 18. OPEN ]---------------------------------------------------------
#

footer.php

#
#---------[ 19. FIND ]---------------------------------------------
#

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

#
#---------[ 20. AFTER, ADD ]---------------------------------------------------
#

if (isset($_GET['ajax']))
{
	$db->end_transaction();
	$db->close();
	return;
}

#
#---------[ 21. OPEN ]---------------------------------------------------------
#

include/functions.php

#
#---------[ 22. FIND ]---------------------------------------------
#

function redirect($destination_url, $message)
{
	global $db, $pun_config, $lang_common, $pun_user;

#
#---------[ 23. AFTER, ADD ]---------------------------------------------------
#

	if (isset($_GET['ajax']))
		return;

#
#---------[ 24. FIND ]---------------------------------------------
#

function message($message, $no_back_link = false)
{

#
#---------[ 25. AFTER, ADD ]---------------------------------------------------
#

	if (isset($_GET['ajax']))
	{
		echo $message;
		exit;
	}

#
#---------[ 26. OPEN ]---------------------------------------------------------
#

post.php

#
#---------[ 27. FIND ]---------------------------------------------
#

		redirect('viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $lang_post['Post redirect']);

#
#---------[ 28. BEFORE, ADD ]---------------------------------------------------
#

		if (isset($_GET['ajax']) && isset($_GET['lpid']))
		{
			$db->end_transaction();
			$db->close();
			header('Location: viewtopic.php?ajax&id='.$tid.'&pcount='.intval($_GET['pcount']).'&lpid='.intval($_GET['lpid']));
		}

#
#---------[ 29. FIND ]---------------------------------------------
#

// If a topic ID was specified in the url (it's a reply)
if ($tid)

#
#---------[ 30. BEFORE, ADD ]---------------------------------------------------
#

if (isset($_GET['ajax']) && !empty($errors))
{
	echo implode("\n", $errors);
	exit;
}