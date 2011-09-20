##
##
##        Mod title:  Sub Forum
##
##      Mod version:  1.0.9.6
##  Works on FluxBB:  1.4.5
##     Release date:  2011-03-31
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  pabb
##
##      Description:  This mod lets Administrators add subforums to forums. 
##	  		          If you want to display last topic subject on index, use last_topic_on_index mod.
##
##	 	    Credits:  Smartys for all re-coding the Sub Forum Mod to achieve the profile link, subject area and displaying of the last post on the 
##			          index even in the Sub forums. - Shedrock - http://shedrockonline.com - The idea and additional classes.
##
##   Repository URL:  http://fluxbb.org/resources/mods/sub-forum/
##
##   Affected files:  index.php
##                    viewtopic.php
##                    viewforum.php
##                    admin_forums.php
##                    search.php
##                    include/cache.php
##                    style/Air.css
##
##       Affects DB:  Yes
##
##            Notes:  Um.. none.
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##
##       KNOWN BUGS:  When deleting topics, posts, it does not show the parent forum as it should.
##
##        Changelog:  1.0.9.6
##                      - Update for FluxBB 1.4.5 [daris]
##
##                    1.0.9.5
##                      - index.php and viewforum.php optimalization [daris]
##                      - removing last topic title from index due to performance issues on big forums (you can use last_topic_on_index mod instead) [daris]
##
##                    1.0.9.4
##                      - Updated to FluxBB 1.4.2 [daris]
##
##                    1.0.9.3
##                      - Updated to punBB 1.2.20 [RoadTrain]
##
##                    1.0.9.2
##                      - Square to show new posts for logged in user now lights up when posts are made in sub forums [Smartys]
##
##                    1.0.9.1
##                      - Re-fixed topic and post counts & last post link due to invalid variable brought in from earlier version
##
##                    1.0.9
##                      - Sub forums now display in order of 'disp_position' on Index as set in Admin CP [naitkris]
##
##                    1.0.8
##                      - Option to set sub forums view for 'inline' or 'multi-line' listing on Index [twohawks]
##                      - Option to hide the sub forums listed at top when viewing parent forum [twohawks]
##
##                    1.0.8
##                      - Added Index view from nico_somb's version and modified to work here (was seperate version before) [naitkris]
##
##                    1.0.7
##                      - Optimised code on index.php so query isn't run for every forum processed [binjured]
##
##                    1.0.7
##                      - Fixed "Moderated by" when viewing parent forum of multiple sub-forums with/without moderators [naitkris]
##
##                    1.0.6
##                      - Fixed items in the quickjump so sub forums are indented (like fix in 1.0.4 by NeoTall for search) [naitkris]
##
##                    1.0.5
##                      - Correctly sorting subforums [Timpa]
##                      - Now showing last post as it should [Timpa]
##
##                    1.0.4
##                      - Fixed items in combobox "Select where to search" [NeoTall (Rus)]
##
##                    1.0.3
##                      - Fixed search in SubForum when using "Non-MultiByted" language [NeoTall (Rus)]
##
##                    1.0.2
##                      - Correctly displaying Last Post, Last Poster and Last Posted Time [NeoTall (Rus)]
##                      - Correctly displaying Total Topics and Total Posts [NeoTall (Rus)]
##
##                    1.0.1
##                      - Correctly creating Last Post link [NeoTall (Rus)]
##                      - Correctly displaying word "Topics" on SubForum header [NeoTall (Rus)]
##


#
#---------[ 1. UPLOAD ]-------------------------------------------------------
#

files/install_mod.php to /
files/lang/English/sub_forum.php to lang/English/sub_forum.php

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

index.php

#
#---------[ 5. FIND (line: 37) ]---------------------------------------------
#

define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

#
#---------[ 6. AFTER, ADD ]---------------------------------------------
#

################################################################################
########################### Sub Forum MOD (start) ##############################
################################################################################
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php';
else
	require PUN_ROOT.'lang/English/sub_forum.php';

$sfdb = array();

$forums_info = $db->query('SELECT f.num_topics, f.num_posts, f.parent_forum_id, f.last_post_id, f.last_poster, f.last_post, f.id, f.forum_name FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.parent_forum_id <> 0 ORDER BY f.disp_position') or error('Unable to fetch subforum list', __FILE__, __LINE__, $db->error());

while ($current = $db->fetch_assoc($forums_info)) 
{
	if (!isset($sfdb[$current['parent_forum_id']]))
		$sfdb[$current['parent_forum_id']] = array();
		
	$sfdb[$current['parent_forum_id']][] = $current;
}
################################################################################
########################### Sub Forum MOD ( end ) ##############################
################################################################################

#
#---------[ 7. FIND (line: 65) ]---------------------------------------------
#

// Print the categories and forums
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 8. REPLACE WITH ]-------------------------------------------------
#

// Print the categories and forums
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster, f.parent_forum_id FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND (f.parent_forum_id IS NULL OR f.parent_forum_id=0) ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 9. FIND (line: 125) ]---------------------------------------------
#

		$num_topics = $cur_forum['num_topics'];
		$num_posts = $cur_forum['num_posts'];

#
#---------[ 10. AFTER, ADD ]---------------------------------------------
#

################################################################################
########################### Sub Forum MOD (start) ##############################
################################################################################
		if (isset($sfdb[$cur_forum['fid']]))
		{
			foreach ($sfdb[$cur_forum['fid']] as $cur_subforum)
			{
				$num_topics += $cur_subforum['num_topics'];
				$num_posts += $cur_subforum['num_posts'];
				if ($cur_forum['last_post'] < $cur_subforum['last_post'])
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
#---------[ 11. FIND (line: 158) ]---------------------------------------------
#

		$last_post = $lang_common['Never'];

#
#---------[ 15. AFTER, ADD ]-------------------------------------------------
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
#---------[ 16. FIND (line: 188) ]---------------------------------------------
#

				<tr class="<?php echo $item_status ?>">
					<td class="tcl">
						<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo forum_number_format($forum_count) ?></div></div>
						<div class="tclcon">
							<div>
								<?php echo $forum_field."\n".$moderators ?>

#
#---------[ 17. AFTER, ADD ]-------------------------------------------------
#

<?php
				$sub_forums_list = array();
				if (!empty($sfdb) && isset($sfdb[$cur_forum['fid']]))
				{
					foreach ($sfdb[$cur_forum['fid']] as $cur_subforum)
						$sub_forums_list[] = '<a class="subforum_name" href="viewforum.php?id='.$cur_subforum['id'].'">'.pun_htmlspecialchars($cur_subforum['forum_name']).'</a>';

					// EDIT THIS FOR THE DISPLAY STYLE OF THE SUBFORUMS ON MAIN PAGE
					if(!empty($sub_forums_list))
					{
						// Leave one $sub_forums_list commented out to use the other (between the ###..)
						################################
						// This is Single Line Wrap Style
						$sub_forums_list = "\t\t\t\t\t\t\t\t".'<span class="subforum">'.$lang_sub_forum['Sub forums'].':</span> '.implode(', ', $sub_forums_list)."\n";
						// This is List Style
						//$sub_forums_list = "\n".'<b><em>'.$lang_sub_forum['Sub forums'].':</em></b><br />&nbsp; -- &nbsp;'.implode('<br />&nbsp; -- &nbsp;', $sub_forums_list)."\n";
						################################
						/* if ($cur_forum['forum_desc'] != NULL)
						echo "<br />";
						*/
						// TO TURN OFF DISPLAY OF SUBFORUMS ON INDEX PAGE, COMMENT OUT THE FOLLOWING LINE
						echo $sub_forums_list;
					}
				}
?>

#
#---------[ 18. OPEN ]---------------------------------------------------------
#

viewforum.php

#
#---------[ 19. FIND (line: 39) ]---------------------------------------------
#

require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';

#
#---------[ 20. AFTER, ADD ]---------------------------------------------
#

require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';

#
#---------[ 21. FIND (line: 45) ]---------------------------------------------
#

if (!$pun_user['is_guest'])
	$result = $db->query('SELECT f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, s.user_id AS is_subscribed FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_subscriptions AS s ON (f.id=s.forum_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, 0 AS is_subscribed FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

#
#---------[ 22. REPLACE WITH ]---------------------------------------------
#

if (!$pun_user['is_guest'])
	$result = $db->query('SELECT pf.forum_name AS parent_forum, f.parent_forum_id, f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, s.user_id AS is_subscribed FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_subscriptions AS s ON (f.id=s.forum_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') LEFT JOIN '.$db->prefix.'forums AS pf ON f.parent_forum_id=pf.id  WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT pf.forum_name AS parent_forum, f.parent_forum_id, f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, 0 AS is_subscribed FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') LEFT JOIN '.$db->prefix.'forums AS pf ON f.parent_forum_id=pf.id  WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

#
#---------[ 23. FIND (line: 84) ]---------------------------------------------
#

// Get topic/forum tracking data
if (!$pun_user['is_guest'])
	$tracked_topics = get_tracked_topics();

#
#---------[ 24. REPLACE WITH ]---------------------------------------------
#

if (!$pun_user['is_guest'])
{
	$result = $db->query('SELECT t.forum_id, t.id, t.last_post FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.last_post>'.$pun_user['last_visit'].' AND t.moved_to IS NULL') or error('Unable to fetch new topics', __FILE__, __LINE__, $db->error());

	$new_topics = array();
	while ($cur_topic = $db->fetch_assoc($result))
		$new_topics[$cur_topic['forum_id']][$cur_topic['id']] = $cur_topic['last_post'];

	$tracked_topics = get_tracked_topics();
}

#
#---------[ 25. FIND (line: 84) ]---------------------------------------------
#

require PUN_ROOT.'header.php';

#
#---------[ 26. AFTER, ADD ]---------------------------------------------
#

# Option Note: if you do not want the subforums displaying at the top
# when you go into the main forum topic 
# then in the following $sub_forum_result query change  
# - ORDER BY disp_position')        -  to
# - ORDER BY disp_position', true)  -  (without the dashes)
#

if (!isset($_GET['p']) || $_GET['p'] == 1)
{
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php';
	else
		require PUN_ROOT.'lang/English/sub_forum.php';

	$subforum_result = $db->query('SELECT f.forum_desc, f.forum_name, f.id, f.last_post, f.last_post_id, f.last_poster, f.moderators, f.num_posts, f.num_topics, f.redirect_url FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND parent_forum_id='.$id.' ORDER BY disp_position') or error('Unable to fetch sub forum info',__FILE__,__LINE__,$db->error());
	if ($db->num_rows($subforum_result))
	{


?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $id ?>"><strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></a></li>
		</ul>
        <div class="clearer"></div>
    </div>
</div>

<div id="punindex" class="subforumlist">

<div id="vf1" class="blocktable">
    <h2><span><?php echo $lang_sub_forum['Sub forums'] ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table cellspacing="0">
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?php echo $lang_common['Forum'] ?></th>
                    <th class="tc2" scope="col"><?php echo $lang_index['Topics'] ?></th>
                    <th class="tc3" scope="col"><?php echo $lang_common['Posts'] ?></th>
                    <th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
                </tr>
            </thead>
            <tbody>
<?php
		$subforum_count = 0;

		while ($cur_subforum = $db->fetch_assoc($subforum_result))
		{
			++$subforum_count;
			$item_status = '';
			$icon_type = 'icon';

			// Are there new posts?
			if (!$pun_user['is_guest'] && $cur_subforum['last_post'] > $pun_user['last_visit'] && (empty($tracked_topics['forums'][$cur_subforum['id']]) || $cur_subforum['last_post'] > $tracked_topics['forums'][$cur_subforum['id']]))
			{
				// There are new posts in this forum, but have we read all of them already?
				foreach ($new_topics[$cur_subforum['id']] as $check_topic_id => $check_last_post)
				{
					if ((empty($tracked_topics['topics'][$check_topic_id]) || $tracked_topics['topics'][$check_topic_id] < $check_last_post) && (empty($tracked_topics['forums'][$cur_subforum['id']]) || $tracked_topics['forums'][$cur_subforum['id']] < $check_last_post))
					{
						$item_status = 'inew';
						$icon_type = 'icon inew';

						break;
					}
				}
			}

			// Is this a redirect forum?
			if ($cur_forum['redirect_url'] != '')
			{
				$forum_field = '<h3><a href="'.pun_htmlspecialchars($cur_subforum['redirect_url']).'" title="'.$lang_index['Link to'].' '.pun_htmlspecialchars($cur_subforum['redirect_url']).'">'.pun_htmlspecialchars($cur_subforum['forum_name']).'</a></h3>';
				$num_topics = $num_posts = '&nbsp;';
				$item_status = 'iredirect';
				$icon_type = 'icon';
			}
			else
			{
				$forum_field = '<h3><a href="viewforum.php?id='.$cur_subforum['id'].'">'.pun_htmlspecialchars($cur_subforum['forum_name']).'</a></h3>';
				$num_topics = $cur_subforum['num_topics'];
				$num_posts = $cur_subforum['num_posts'];
			}

			if ($cur_subforum['forum_desc'] != '')
				$forum_field .= "\n\t\t\t\t\t\t\t\t".$cur_subforum['forum_desc'];

			// If there is a last_post/last_poster
			if ($cur_subforum['last_post'] != '')
				$last_post = '<a href="viewtopic.php?pid='.$cur_subforum['last_post_id'].'#p'.$cur_subforum['last_post_id'].'">'.format_time($cur_subforum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_subforum['last_poster']).'</span>';
			else if ($cur_subforum['redirect_url'] != '')
				$last_post = '- - -';
			else
				$last_post = $lang_common['Never'];

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
?>
                <tr<?php if ($item_status != '') echo ' class="'.$item_status.'"'; ?>>
                    <td class="tcl">
                        <div class="intd">
                            <div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo forum_number_format($subforum_count) ?></div></div>
                            <div class="tclcon">
                                <?php echo $forum_field;
                                if ($cur_subforum['moderators'] != '') {
                                    echo "\n".$moderators;
                                }
                                ?>
                            </div>
                        </div>
                    </td>
                    <td class="tc2"><?php echo $num_topics ?></td>
                    <td class="tc3"><?php echo $num_posts ?></td>
                    <td class="tcr"><?php echo $last_post ?></td>
                </tr>
<?php
		}
?>
            </tbody>
            </table>
        </div>
    </div>
</div>

</div>
<?php
	}
}

#
#---------[ 27. FIND (line: 191) ]---------------------------------------------
#

			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>

#
#---------[ 28. AFTER, ADD ]---------------------------------------------
#

<?php if($cur_forum['parent_forum']) echo "\t\t".'<li><span>»&#160;</span><a href="viewforum.php?id='.$cur_forum['parent_forum_id'].'">'.pun_htmlspecialchars($cur_forum['parent_forum']).'</a></li> '; ?>

#
#---------[ 33. FIND (line: 285) ]---------------------------------------------
#

			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>

#
#---------[ 34. AFTER, ADD ]---------------------------------------------
#

<?php if($cur_forum['parent_forum']) echo "\t\t".'<li><span>»&#160;</span><a href="viewforum.php?id='.$cur_forum['parent_forum_id'].'">'.pun_htmlspecialchars($cur_forum['parent_forum']).'</a></li> '; ?>

#
#---------[ 35. OPEN ]---------------------------------------------
#

viewtopic.php

#
#---------[ 36. FIND (line: 97) ]---------------------------------------------
#

if (!$pun_user['is_guest'])
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'topic_subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

#
#---------[ 37. REPLACE WITH ]---------------------------------------------
#

if (!$pun_user['is_guest'])
	$result = $db->query('SELECT pf.forum_name AS parent_forum, f.parent_forum_id, t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'topic_subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') LEFT JOIN '.$db->prefix.'forums AS pf ON f.parent_forum_id=pf.id WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT pf.forum_name AS parent_forum, f.parent_forum_id, t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') LEFT JOIN '.$db->prefix.'forums AS pf ON f.parent_forum_id=pf.id WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

#
#---------[ 38. FIND (line: 72) ]---------------------------------------------
#

			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			
#
#---------[ 39. AFTER, ADD ]---------------------------------------------
#

<?php if ($cur_topic['parent_forum']) echo "\t\t".'<li><span>»&#160;</span><a href="viewforum.php?id='.$cur_topic['parent_forum_id'].'">'.pun_htmlspecialchars($cur_topic['parent_forum']).'</a></li> '; ?>

#
#---------[ 40. FIND (same as before, bottom crumbs) ]---------------------------------------------
#

			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			
#
#---------[ 41. AFTER, ADD ]---------------------------------------------
#

<?php if ($cur_topic['parent_forum']) echo "\t\t".'<li><span>»&#160;</span><a href="viewforum.php?id='.$cur_topic['parent_forum_id'].'">'.pun_htmlspecialchars($cur_topic['parent_forum']).'</a></li> '; ?>

#
#---------[ 42. OPEN ]---------------------------------------------
#

admin_forums.php

#
#---------[ 43. FIND (line: 170) ]---------------------------------------------
#

			$redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null;

#
#---------[ 44. AFTER, ADD ]---------------------------------------------
#

			$parent_forum_id = intval($_POST['parent_forum']);

#
#---------[ 45. FIND (line: 182) ]---------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'forums SET forum_name=\''.$db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id.' WHERE id='.$forum_id) or error('Unable to update forum', __FILE__, __LINE__, $db->error());

#
#---------[ 46. REPLACE WITH ]---------------------------------------------
#

		$db->query('UPDATE '.$db->prefix.'forums SET forum_name=\''.$db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id.', parent_forum_id='.$parent_forum_id.' WHERE id='.$forum_id) or error('Unable to update forum', __FILE__, __LINE__, $db->error());

#
#---------[ 47. FIND (line: 232) ]---------------------------------------------
#

		$result = $db->query('SELECT id, forum_name, forum_desc, redirect_url, num_topics, sort_by, cat_id FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

#
#---------[ 48. REPLACE WITH ]---------------------------------------------
#

		$result = $db->query('SELECT id, forum_name, forum_desc, redirect_url, num_topics, sort_by, cat_id, parent_forum_id FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

#
#---------[ 49. FIND (line: 236) ]---------------------------------------------
#

	$cur_forum = $db->fetch_assoc($result);

#
#---------[ 50. AFTER, ADD ]---------------------------------------------
#

	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/sub_forum.php';
	else
		require PUN_ROOT.'lang/English/sub_forum.php';

	$parent_forums = Array();
	$result = $db->query('SELECT DISTINCT parent_forum_id FROM '.$db->prefix.'forums WHERE parent_forum_id != 0');
	while ($r = $db->fetch_row($result))
		$parent_forums[] = $r[0];

#
#---------[ 51. FIND (line: 295) ]---------------------------------------------
#

									<th scope="row"><?php echo $lang_admin_forums['Redirect label'] ?></th>
									<td><?php echo ($cur_forum['num_topics']) ? $lang_admin_forums['Redirect help'] : '<input type="text" name="redirect_url" size="45" maxlength="100" value="'.pun_htmlspecialchars($cur_forum['redirect_url']).'" tabindex="5" />'; ?></td>
								</tr>

#
#---------[ 52. AFTER, ADD ]---------------------------------------------
#

                                <tr>
                                    <th scope="row"><?php echo $lang_sub_forum['Parent forum'] ?></th>
                                    <td>
                                        <select name="parent_forum">
                                            <option value="0"><?php echo $lang_sub_forum['No parent forum'] ?></option>
<?php

    if (!in_array($cur_forum['id'],$parent_forums))
    {
		$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id, f.forum_name, f.redirect_url, f.parent_forum_id FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

		$cur_category = 0;
		while ($forum_list = $db->fetch_assoc($result))
		{
			if ($forum_list['cid'] != $cur_category) // A new category since last iteration?
			{
				if ($cur_category)
					echo "\t\t\t\t\t\t".'</optgroup>'."\n";

				echo "\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($forum_list['cat_name']).'">'."\n";
				$cur_category = $forum_list['cid'];
			}
			
			$selected = ($forum_list['id'] == $cur_forum['parent_forum_id']) ? ' selected="selected"' : '';

            if(!$forum_list['parent_forum_id'] && $forum_list['id'] != $cur_forum['id'])
				echo "\t\t\t\t\t\t\t".'<option value="'.$forum_list['id'].'"'.$selected.'>'.pun_htmlspecialchars($forum_list['forum_name']).'</option>'."\n";
		}
    }

?>
											</optgroup>
                                        </select>
                                    </td>
                                </tr>

#
#---------[ 53. FIND (line: 450) ]---------------------------------------------
#

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 54. REPLACE WITH ]---------------------------------------------
#

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position, f.parent_forum_id FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 55. FIND (line: 495) ]---------------------------------------------
#

									<td class="tcr"><strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></td>

#
#---------[ 56. REPLACE WITH ]---------------------------------------------
#

									<td class="tcr"><strong><?php echo ($cur_forum['parent_forum_id'] == 0 ? '' : '&nbsp;&nbsp;&nbsp;').pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></td>

#
#---------[ 57. OPEN ]---------------------------------------------
#

search.php

#
#---------[ 58. FIND (line: 735) ]---------------------------------------------
#

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 59. REPLACE WITH ]---------------------------------------------
#

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url, f.parent_forum_id FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 60. FIND (line: 749) ]---------------------------------------------
#

	echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";

#
#---------[ 61. REPLACE WITH ]---------------------------------------------
#

	echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.($cur_forum['parent_forum_id'] == 0 ? '' : '&nbsp;&nbsp;&nbsp;').pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";

#
#---------[ 62. OPEN ]---------------------------------------------
#

include/cache.php

#
#---------[ 63. FIND (line: 181) ]---------------------------------------------
#

			$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$group_id.') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 64. REPLACE WITH ]---------------------------------------------
#

			$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url, f.parent_forum_id FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$group_id.') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

#
#---------[ 65. FIND (line: 196) ]---------------------------------------------
#

			$output .= "\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'"<?php echo ($forum_id == '.$cur_forum['fid'].') ? \' selected="selected"\' : \'\' ?>>'.pun_htmlspecialchars($cur_forum['forum_name']).$redirect_tag.'</option>'."\n";

#
#---------[ 66. REPLACE WITH ]---------------------------------------------
#

			$output .= "\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'"<?php echo ($forum_id == '.$cur_forum['fid'].') ? \' selected="selected"\' : \'\' ?>>'.($cur_forum['parent_forum_id'] == 0 ? '' : '&nbsp;&nbsp;&nbsp;').pun_htmlspecialchars($cur_forum['forum_name']).$redirect_tag.'</option>'."\n";

#
#---------[ 67. OPEN (or any Air based style like Fire, Earth) ]---------------------------------------------
#

style/Air.css

#
#---------[ 68. FIND ]---------------------------------------------
#

#punindex #brdmain .blocktable h2, #punsearch #vf h2 {

#
#---------[ 69. REPLACE WITH ]---------------------------------------------
#

#punindex #brdmain .blocktable h2, #punindex.subforumlist .blocktable h2, #punsearch #vf h2 {

#
#---------[ 70. ATTENTION ]-----------------------------------------------
#

!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!!!!!!!!!!!!!! YOU MUST SORT FORUMS POSITION IN "Administration -> Forums"... !!!!!!!!!!!!!!
!!!!!!!!!!!!!!!!!!!!! YOU SHOULD ALSO RE-GENERATE YOUR QUICKJUMP CACHE !!!!!!!!!!!!!!!!!!!!!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

#
#---------[ 71. NOTE ]-----------------------------------------------
#

If you want to display last topic subject, install last_topic_on_index modification.

#
#---------[ 72. SAVE/UPLOAD ]-------------------------------------------------
#
