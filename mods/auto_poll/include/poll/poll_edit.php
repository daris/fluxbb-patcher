<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;


if (!isset($checkboxes) && $can_edit_subject)
{
	// If it's a new poll
	$add_poll = isset($_POST['add_poll']) ? 1 : 0;
	if ($add_poll)
		redirect('poll_add.php?id='.$cur_post['tid'], $lang_post['Edit redirect']);
}

elseif ($can_edit_subject)
{
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php';
	else
		require PUN_ROOT.'lang/English/poll.php';
		
	// See if user can post polls in this forum
	$result = $db->query('SELECT fp.post_polls FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'topics AS t ON t.id='.$cur_post['tid'].' LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.post_polls IS NULL OR fp.post_polls=1) AND t.question=\'\' AND f.id='.$cur_post['fid']) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

	if ($db->num_rows($result) && $pun_user['g_post_polls'] != '0' && $pun_config['o_poll_enabled'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="add_poll" value="1" tabindex="'.($cur_index++).'"'.(isset($_POST['add_poll']) ? ' checked="checked"' : '').' />'.$lang_poll['Add poll'].'<br /></label>';
}

?>
