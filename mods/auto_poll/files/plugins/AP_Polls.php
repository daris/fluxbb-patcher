<?php
/**
 * Copyright (C) 2005  Connor Dunn (Connorhd@mypunbb.com)
 * Adapted for FluxBB 1.4 by Ishimaru Chiaki (http://ishimaru-design.servhome.org)
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
    exit;

// Load language file
if (file_exists(PUN_ROOT.'lang/'.$admin_language.'/admin_plugin_polls.php'))
	require PUN_ROOT.'lang/'.$admin_language.'/admin_plugin_polls.php';
else
	require PUN_ROOT.'lang/English/admin_plugin_polls.php';
require PUN_ROOT.'lang/'.$admin_language.'/admin_forums.php';
require PUN_ROOT.'lang/'.$admin_language.'/admin_common.php';

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_VERSION', '1.1.0');

if (isset($_POST['save_options']))
{
	// Lazy referer check (in case base_url isn't correct)
	if (!preg_match('#/admin_loader\.php#i', $_SERVER['HTTP_REFERER']))
		message($lang_common['Bad referrer']);

	$form = array_map('trim', $_POST['form']);
	$allow = array_map('trim', $_POST['allow']);

	while (list($key, $input) = @each($form))
	{
		// Only update values that have changed
		if ((isset($pun_config['o_'.$key])) || ($pun_config['o_'.$key] == NULL))
		{
			if ($pun_config['o_'.$key] != $input)
			{
				if ($input != '' || is_int($input))
					$value = '\''.$db->escape($input).'\'';
				else
					$value = 'NULL';

				$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'o_'.$key.'\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());
			}
		}
	}

	while (list($id, $set) = @each($allow))
	{
		$db->query('UPDATE '.$db->prefix.'groups SET g_post_polls='.$set.' WHERE g_id='.$id) or error('Unable to change permissions.', __FILE__, __LINE__, $db->error());
	}

	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();

	redirect('admin_loader.php?plugin=AP_Polls.php', $lang_app['Options updated']);
}


// Add a "default" forum
else if (isset($_POST['add_forum']))
{
	confirm_referrer('admin_loader.php?plugin=AP_Polls.php');

	$add_to_cat = intval($_POST['add_to_cat']);
	if ($add_to_cat < 1)
		message($lang_common['Bad request']);

	$db->query('INSERT INTO '.$db->prefix.'forums (cat_id) VALUES('.$add_to_cat.')') or error('Unable to create forum', __FILE__, __LINE__, $db->error());

	// Regenerate the quickjump cache
	require_once PUN_ROOT.'include/cache.php';
	generate_quickjump_cache();

	redirect('admin_loader.php?plugin=AP_Polls.php', $lang_admin_forums['Forum added redirect']);
}


// Delete a forum
else if (isset($_GET['del_forum']))
{
	confirm_referrer('admin_loader.php?plugin=AP_Polls.php');

	$forum_id = intval($_GET['del_forum']);
	if ($forum_id < 1)
		message($lang_common['Bad request']);

	if (isset($_POST['del_forum_comply']))	// Delete a forum with all posts
	{
		@set_time_limit(0);

		// Prune all posts and topics
		prune($forum_id, 1, -1);

		// Locate any "orphaned redirect topics" and delete them
		$result = $db->query('SELECT t1.id FROM '.$db->prefix.'topics AS t1 LEFT JOIN '.$db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $db->error());
		$num_orphans = $db->num_rows($result);

		if ($num_orphans)
		{
			for ($i = 0; $i < $num_orphans; ++$i)
				$orphans[] = $db->result($result, $i);

			$db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());
		}

		// Delete the forum and any forum specific group permissions
		$db->query('DELETE FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to delete forum', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());

		// Regenerate the quickjump cache
		require_once PUN_ROOT.'include/cache.php';
		generate_quickjump_cache();

		redirect('admin_loader.php?plugin=AP_Polls.php', $lang_admin_forums['Forum deleted redirect']);
	}
	else	// If the user hasn't confirmed the delete
	{
		$result = $db->query('SELECT forum_name FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
		$forum_name = pun_htmlspecialchars($db->result($result));


		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
		require PUN_ROOT.'header.php';

		generate_admin_menu($plugin);

?>
	<div class="blockform">
		<h2><span><?php echo $lang_admin_forums['Confirm delete head']; ?></span></h2>
		<div class="box">
			<form method="post" action="admin_loader.php?plugin=AP_Polls.php&amp;del_forum=<?php echo $forum_id ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_forums['Confirm delete subhead']; ?></legend>
						<div class="infldset">
							<p><?php printf($lang_admin_forums['Confirm delete info'], $forum_name); ?>"?</p>
							<p><?php echo $lang_admin_forums['Confirm delete warn']; ?></p>
						</div>
					</fieldset>
				</div>
				<p><input type="submit" name="del_forum_comply" value="<?php echo $lang_common['Delete']; ?>" /><a href="javascript:history.go(-1)"><?php echo $lang_admin_common['Go back']; ?></a></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php

		require PUN_ROOT.'footer.php';
	}
}


// Update forum positions
else if (isset($_POST['update_positions']))
{
	confirm_referrer('admin_loader.php?plugin=AP_Polls.php');

	while (list($forum_id, $disp_position) = @each($_POST['position']))
	{
		if (!@preg_match('#^\d+$#', $disp_position))
			message($lang_admin_forums['Must be integer message']);

		$db->query('UPDATE '.$db->prefix.'forums SET disp_position='.$disp_position.' WHERE id='.intval($forum_id)) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
	}

	// Regenerate the quickjump cache
	require_once PUN_ROOT.'include/cache.php';
	generate_quickjump_cache();

	redirect('admin_loader.php?plugin=AP_Polls.php', $lang_admin_forums['Forums updated redirect']);
}


else if (isset($_GET['edit_forum']))
{
	$forum_id = intval($_GET['edit_forum']);
	if ($forum_id < 1)
		message($lang_common['Bad request']);

	// Update group permissions for $forum_id
	if (isset($_POST['save']))
	{
		confirm_referrer('admin_loader.php?plugin=AP_Polls.php');

		// Start with the forum details
		$forum_name = trim($_POST['forum_name']);
		$forum_desc = pun_linebreaks(trim($_POST['forum_desc']));
		$cat_id = intval($_POST['cat_id']);
		$sort_by = intval($_POST['sort_by']);
		$redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null;

		if ($forum_name == '')
			message($lang_admin_forums['Must enter name message']);

		if ($cat_id < 1)
			message($lang_common['Bad request']);

		$forum_desc = ($forum_desc != '') ? '\''.$db->escape($forum_desc).'\'' : 'NULL';
		$redirect_url = ($redirect_url != '') ? '\''.$db->escape($redirect_url).'\'' : 'NULL';

		$db->query('UPDATE '.$db->prefix.'forums SET forum_name=\''.$db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id.' WHERE id='.$forum_id) or error('Unable to update forum', __FILE__, __LINE__, $db->error());

		// Now let's deal with the permissions
		if (isset($_POST['read_forum_old']))
		{
			$result = $db->query('SELECT g_id, g_read_board, g_post_replies, g_post_topics, g_post_polls FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN) or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
			while ($cur_group = $db->fetch_assoc($result))
			{
				$read_forum_new = ($cur_group['g_read_board'] == '1') ? isset($_POST['read_forum_new'][$cur_group['g_id']]) ? '1' : '0' : intval($_POST['read_forum_old'][$cur_group['g_id']]);
				$post_replies_new = isset($_POST['post_replies_new'][$cur_group['g_id']]) ? '1' : '0';
				$post_topics_new = isset($_POST['post_topics_new'][$cur_group['g_id']]) ? '1' : '0';
				$post_polls_new = isset($_POST['post_polls_new'][$cur_group['g_id']]) ? '1' : '0';

				// Check if the new settings differ from the old
				if ($read_forum_new != $_POST['read_forum_old'][$cur_group['g_id']] || $post_replies_new != $_POST['post_replies_old'][$cur_group['g_id']] || $post_topics_new != $_POST['post_topics_old'][$cur_group['g_id']] || $post_polls_new != $_POST['post_polls_old'][$cur_group['g_id']])
				{
					// If the new settings are identical to the default settings for this group, delete it's row in forum_perms
					if ($read_forum_new == '1' && $post_replies_new == $cur_group['g_post_replies'] && $post_topics_new == $cur_group['g_post_topics'] && $post_polls_new == $cur_group['g_post_polls'])
						$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());
					else
					{
						// Run an UPDATE and see if it affected a row, if not, INSERT
						$db->query('UPDATE '.$db->prefix.'forum_perms SET read_forum='.$read_forum_new.', post_replies='.$post_replies_new.', post_topics='.$post_topics_new.', post_polls='.$post_polls_new.' WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to insert group forum permissions', __FILE__, __LINE__, $db->error());
						if (!$db->affected_rows())
							$db->query('INSERT INTO '.$db->prefix.'forum_perms (group_id, forum_id, read_forum, post_replies, post_topics, post_polls) VALUES('.$cur_group['g_id'].', '.$forum_id.', '.$read_forum_new.', '.$post_replies_new.', '.$post_topics_new.', '.$post_polls_new.')') or error('Unable to insert group forum permissions', __FILE__, __LINE__, $db->error());
					}
				}
			}
		}

		// Regenerate the quickjump cache
		require_once PUN_ROOT.'include/cache.php';
		generate_quickjump_cache();

		redirect('admin_loader.php?plugin=AP_Polls.php', $lang_admin_forums['Forum updated redirect']);
	}
	else if (isset($_POST['revert_perms']))
	{
		confirm_referrer('admin_loader.php?plugin=AP_Polls.php');

		$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());

		// Regenerate the quickjump cache
		require_once PUN_ROOT.'include/cache.php';
		generate_quickjump_cache();

		redirect('admin_loader.php?plugin=AP_Polls.php&amp;edit_forum='.$forum_id, $lang_admin_forums['Perms reverted redirect']);
	}


	// Fetch forum info
	$result = $db->query('SELECT id, forum_name, forum_desc, redirect_url, num_topics, sort_by, cat_id FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_forum = $db->fetch_assoc($result);


	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Forums']);
	require PUN_ROOT.'header.php';

	generate_admin_menu($plugin);

?>
	<div class="blockform">
		<h2><span><?php echo $lang_admin_forums['Edit forum head']; ?></span></h2>
		<div class="box">
			<form id="edit_forum" method="post" action="admin_loader.php?plugin=AP_Polls.php&amp;edit_forum=<?php echo $forum_id ?>">
				<p class="submittop"><input type="submit" name="save" value="<?php $lang_common['Save changes']; ?>" tabindex="6" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lag_admin_forums['Edit details subhead']; ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_forums['Forum name label']; ?></th>
									<td><input type="text" name="forum_name" size="35" maxlength="80" value="<?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?>" tabindex="1" /></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_forums['Forum description label']; ?></th>
									<td><textarea name="forum_desc" rows="3" cols="50" tabindex="2"><?php echo pun_htmlspecialchars($cur_forum['forum_desc']) ?></textarea></td>
								</tr>
								<tr>
									<th scope="row"><?php $lang_admin_forums['Category subhead']?></th>
									<td>
										<select name="cat_id" tabindex="3">
<?php

	$result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
	while ($cur_cat = $db->fetch_assoc($result))
	{
		$selected = ($cur_cat['id'] == $cur_forum['cat_id']) ? ' selected="selected"' : '';
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'"'.$selected.'>'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
	}

?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php $lang_admin_forums['Sort by label']; ?></th>
									<td>
										<select name="sort_by" tabindex="4">
											<option value="0"<?php if ($cur_forum['sort_by'] == '0') echo ' selected="selected"' ?>><?php echo $lang_admin_forums['Last post']; ?></option>
											<option value="1"<?php if ($cur_forum['sort_by'] == '1') echo ' selected="selected"' ?>><?php echo $lang_admin_forumg['Topic start']; ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_forums['Redirect label']; ?></th>
									<td><?php echo ($cur_forum['num_topics']) ? $lang_admin_forums['Redirect help'] : '<input type="text" name="redirect_url" size="45" maxlength="100" value="'.pun_htmlspecialchars($cur_forum['redirect_url']).'" tabindex="5" />'; ?></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Edit group permissions for this forum</legend>
						<div class="infldset">
							<p><?php printf($lang_admin_forums['Group permissions info'], '<a href="admin_groups.php">'.$lang_admin_common['User groups'].'</a>'); ?></p>
							<table id="forumperms" cellspacing="0">
							<thead>
								<tr>
									<th class="atcl">&nbsp;</th>
									<th><?php echo $lang_admin_forums['Read forum label']; ?></th>
									<th><?php echo $lang_admin_forums['Post replies label']; ?></th>
									<th><?php echo $lang_admin_forums['Post topics label']; ?></th>
									<th><?php echo $lang_app['Post polls labol']; ?></th>
								</tr>
							</thead>
							<tbody>
<?php

	$result = $db->query('SELECT g.g_id, g.g_title, g.g_read_board, g.g_post_replies, g.g_post_topics, g.g_post_polls, fp.read_forum, fp.post_replies, fp.post_topics, fp.post_polls FROM '.$db->prefix.'groups AS g LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (g.g_id=fp.group_id AND fp.forum_id='.$forum_id.') WHERE g.g_id!='.PUN_ADMIN.' ORDER BY g.g_id') or error('Unable to fetch group forum permission list', __FILE__, __LINE__, $db->error());

	while ($cur_perm = $db->fetch_assoc($result))
	{
		$read_forum = ($cur_perm['read_forum'] != '0') ? true : false;
		$post_replies = (($cur_perm['g_post_replies'] == '0' && $cur_perm['post_replies'] == '1') || ($cur_perm['g_post_replies'] == '1' && $cur_perm['post_replies'] != '0')) ? true : false;
		$post_topics = (($cur_perm['g_post_topics'] == '0' && $cur_perm['post_topics'] == '1') || ($cur_perm['g_post_topics'] == '1' && $cur_perm['post_topics'] != '0')) ? true : false;
		$post_polls = (($cur_perm['g_post_polls'] == '0' && $cur_perm['post_polls'] == '1') || ($cur_perm['g_post_polls'] == '1' && $cur_perm['post_polls'] != '0')) ? true : false;

		// Determine if the current sittings differ from the default or not
		$read_forum_def = ($cur_perm['read_forum'] == '0') ? false : true;
		$post_replies_def = (($post_replies && $cur_perm['g_post_replies'] == '0') || (!$post_replies && ($cur_perm['g_post_replies'] == '' || $cur_perm['g_post_replies'] == '1'))) ? false : true;
		$post_topics_def = (($post_topics && $cur_perm['g_post_topics'] == '0') || (!$post_topics && ($cur_perm['g_post_topics'] == '' || $cur_perm['g_post_topics'] == '1'))) ? false : true;
		$post_polls_def = (($post_polls && $cur_perm['g_post_polls'] == '0') || (!$post_polls && ($cur_perm['g_post_polls'] == '' || $cur_perm['g_post_polls'] == '1'))) ? false : true;

?>
								<tr>
									<th class="atcl"><?php echo pun_htmlspecialchars($cur_perm['g_title']) ?></th>
									<td<?php if (!$read_forum_def) echo ' class="nodefault"'; ?>>
										<input type="hidden" name="read_forum_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($read_forum) ? '1' : '0'; ?>" />
										<input type="checkbox" name="read_forum_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($read_forum) ? ' checked="checked"' : ''; ?><?php echo ($cur_perm['g_read_board'] == '0') ? ' disabled="disabled"' : ''; ?> />
									</td>
									<td<?php if (!$post_replies_def && $cur_forum['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="post_replies_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($post_replies) ? '1' : '0'; ?>" />
										<input type="checkbox" name="post_replies_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($post_replies) ? ' checked="checked"' : ''; ?><?php echo ($cur_forum['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> />
									</td>
									<td<?php if (!$post_topics_def && $cur_forum['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="post_topics_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($post_topics) ? '1' : '0'; ?>" />
										<input type="checkbox" name="post_topics_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($post_topics) ? ' checked="checked"' : ''; ?><?php echo ($cur_forum['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> />
									</td>
									<td<?php if (!$post_polls_def && $cur_forum['redirect_url'] == '') echo ' class="nodefault"'; ?>>
										<input type="hidden" name="post_polls_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($post_polls) ? '1' : '0'; ?>" />
										<input type="checkbox" name="post_polls_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($post_polls) ? ' checked="checked"' : ''; ?><?php echo ($cur_forum['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> />
									</td>
								</tr>
<?php

	}

?>
							</tbody>
							</table>
							<div class="fsetsubmit"><input type="submit" name="revert_perms" value="<?php echo $lang_admin_forums['Revert to default']; ?>" /></div>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes']; ?>" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>

<?php

	require PUN_ROOT.'footer.php';
}


else
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);
?>
	<div class="block">
		<h2><span><?php echo $lang_app['Plugin title']; ?> - v<?php echo PLUGIN_VERSION ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_app['Plugin description']; ?></p>
			</div>
		</div>
	</div>
	<div class="blockform">
		<h2 class="block2"><span><?php echo $lang_admin_common['Options']; ?></span></h2>
		<div class="box">
			<form method="post" action="admin_loader.php?plugin=AP_Polls.php">
				<p class="submittop"><input type="submit" name="save_options" value="<?php echo $lang_admin_common['Save changes']; ?>" /></p>
				<div class="inform">
					<input type="hidden" name="form_sent" value="1" />
					<fieldset>
						<legend><?php echo $lang_app['Settings']; ?></legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row"><?php echo $lang_app['Enable polls label']; ?></th>
								<td>
									<input type="radio" name="form[poll_enabled]" value="1"<?php if ($pun_config['o_poll_enabled'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['Yes']; ?></strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[poll_enabled]" value="0"<?php if ($pun_config['o_poll_enabled'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['No']; ?></strong>
									<span><?php echo $lang_app['Enable polls explain']; ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_app['Number fields label']; ?></th>
								<td>
									<input type="text" name="form[poll_max_fields]" size="4" value="<?php echo $pun_config['o_poll_max_fields'] ?>" />
									<span><?php echo $lang_app['Number fields explain']; ?></span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_common['Permissions']; ?></legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<?php
							$result = $db->query('SELECT g_id, g_title, g_post_polls FROM '.$db->prefix.'groups WHERE g_id != '.PUN_ADMIN.' ORDER BY g_id') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
							while ($cur_group = $db->fetch_assoc($result))
							{
							?>
							<tr> 
								<th scope="row"><?php echo $cur_group['g_title'] ?></th>
								<td>
									<input type="radio" name="allow[<?php echo $cur_group['g_id'] ?>]" value="1"<?php if ($cur_group['g_post_polls'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="allow[<?php echo $cur_group['g_id'] ?>]" value="0"<?php if ($cur_group['g_post_polls'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
									<span><?php echo $lang_app['allow group explain']; ?></span>
								</td>
							</tr>
							<?php
							}
							?>
							
						</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_app['Moderator permissions']; ?></legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row"><?php echo $lang_app['Delete polls label']; ?></th>
								<td>
									<input type="radio" name="form[poll_mod_delete_polls]" value="1"<?php if ($pun_config['o_poll_mod_delete_polls'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['Yes']; ?></strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[poll_mod_delete_polls]" value="0"<?php if ($pun_config['o_poll_mod_delete_polls'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['No']; ?></strong>
									<span><?php echo $lang_app['Delete polls explain']; ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_app['Edit polls label']; ?></th>
								<td>
									<input type="radio" name="form[poll_mod_edit_polls]" value="1"<?php if ($pun_config['o_poll_mod_edit_polls'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['Yes']; ?></strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[poll_mod_edit_polls]" value="0"<?php if ($pun_config['o_poll_mod_edit_polls'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['No']; ?></strong>
									<span><?php echo $lang_app['Edit polls explain']; ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_app['Reset polls label']; ?></th>
								<td>
									<input type="radio" name="form[poll_mod_reset_polls]" value="1"<?php if ($pun_config['o_poll_mod_reset_polls'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['Yes']; ?></strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[poll_mod_reset_polls]" value="0"<?php if ($pun_config['o_poll_mod_reset_polls'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_admin_common['No']; ?></strong>
									<span><?php echo $lang_app['Reset polls explain'];?></span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
			<p class="submitend"><input type="submit" name="save_options" value="<?php echo $lang_admin_common['Save changes']; ?>" /></p>
			</form>
		</div>
	</div>
	
	
<?php
// Display all the categories and forums
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result) > 0)
{

?>
	<div class="blockform">
		<h2 class="block2"><span><?php echo $lang_admin_forums['Edit forums head']; ?></span></h2>
		<div class="box">
			<form id="edforum" method="post" action="admin_loader.php?plugin=AP_Polls.php&amp;action=edit">
				<p class="submittop"><input type="submit" name="update_positions" value="<?php echo $lang_admin_forums['Update positions']; ?>" tabindex="3" /></p>
<?php

$tabindex_count = 4;

$cur_category = 0;
while ($cur_forum = $db->fetch_assoc($result))
{
	if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
	{
		if ($cur_category != 0)
			echo "\t\t\t\t\t\t\t".'</table>'."\n\t\t\t\t\t\t".'</div>'."\n\t\t\t\t\t".'</fieldset>'."\n\t\t\t\t".'</div>'."\n";

?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_forums['Category subhead']; ?> <?php echo pun_htmlspecialchars($cur_forum['cat_name']) ?></legend>
						<div class="infldset">
							<table cellspacing="0">
<?php

		$cur_category = $cur_forum['cid'];
	}

?>
								<tr>
									<th><a href="admin_loader.php?plugin=AP_Polls.php&amp;edit_forum=<?php echo $cur_forum['fid'] ?>"><?php echo $lang_admin_forums['Edit link']; ?></a> - <a href="admin_loader.php?plugin=AP_Polls.php&amp;del_forum=<?php echo $cur_forum['fid'] ?>"><?php echo $lang_admin_forums['Delete link']; ?></a></th>
									<td><?php echo $lang_admin_forum['Position']; ?>&nbsp;&nbsp;<input type="text" name="position[<?php echo $cur_forum['fid'] ?>]" size="3" maxlength="3" value="<?php echo $cur_forum['disp_position'] ?>" tabindex="<?php echo $tabindex_count ?>" />
									&nbsp;&nbsp;<strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></td>
								</tr>
<?php

	$tabindex_count += 2;
}

?>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="update_positions" value="<?php echo $lang_admin_forums['Update positions']; ?>" tabindex="<?php echo $tabindex_count ?>" /></p>
			</form>
		</div>
	</div>
<?php

}

?>

<?php
}
?>
