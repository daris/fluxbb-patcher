<?php

define('PUN_ROOT', '../../');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	exit($lang_common['No view']);


$action = $_POST['action'];
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id < 1)
	exit($lang_common['Bad request']);

// Fetch some info about the post, the topic and the forum
$result = $db->query('SELECT f.moderators, t.id AS tid, t.subject, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies, p.edited, p.edited_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	exit($lang_common['Bad request']);

$cur_post = $db->fetch_assoc($result);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

if ($pun_config['o_censoring'] == '1')
	$cur_post['message'] = censor_words($cur_post['message']);

// Do we have permission to edit this post?
if (($pun_user['g_edit_posts'] == '0' ||
	$cur_post['poster_id'] != $pun_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$is_admmod)
	exit($lang_common['No permission']);

// Load the post.php/edit.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/ajax_post_edit.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/ajax_post_edit.php';
else
	require PUN_ROOT.'lang/English/ajax_post_edit.php';

// Start with a clean slate
$errors = array();

require PUN_ROOT.'include/parser.php';

$cur_index = 0;


if ($action == "get" && $id > 0)
{
?>
<form id="ape-edit" class="form-ape" method="post" action="edit.php?id=<?php echo $id ?>&amp;action=edit" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1" />
	<input type="hidden" name="preview" value="1" />
	
	<div class="ape-label">
		<textarea id="ape-message" name="req_message" rows="17" style="width: 98%" tabindex="<?php echo $cur_index++ ?>"><?php echo pun_htmlspecialchars(isset($_POST['req_message']) ? $message : $cur_post['message']) ?></textarea>
	</div>

<?php if ($is_admmod) : ?>

	<div class="ape-label">
		<label><input type="checkbox" name="silent" value="1" id="ape-silent" checked="checked" /> <?php echo $lang_post['Silent edit'] ?></label>
	</div>

<?php endif; ?>

	<div class="ape-label">
		<div style="float:right; display:none" id="edit_info">
			<img src="<?php echo $pun_config['o_base_url'] ?>/img/ajax_post_edit/loading.gif" /> <?php echo $lang_ape['Saving'] ?>
		</div>
		<input type="button" onclick="ape_update_post(<?php echo $id ?>)" value="<?php echo $lang_ape['Update'] ?>" id="btn_updatePost" />
		<input type="button" onclick="ape_cancel_edit(<?php echo $id ?>)" value="<?php echo $lang_ape['Cancel'] ?>" id="btn_cancelUpdate" />
	</div>

</form>
<!-- END FORM -->
		
<parsed_message><?php echo parse_message($cur_post['message'], $cur_post['hide_smilies']) ?></parsed_message>
<?php


}
elseif ($action == "update" && isset($_POST['req_message']) && $id > 0) 
{
	// Clean up message from POST
	$message = pun_linebreaks(pun_trim($_POST['req_message']));

	// Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
	if (strlen($message) > PUN_MAX_POSTSIZE)
		$errors[] = sprintf($lang_post['Too long message'], forum_number_format(PUN_MAX_POSTSIZE));
	else if ($pun_config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$pun_user['is_admmod'])
		$errors[] = $lang_post['All caps message'];

	// Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1')
		$message = preparse_bbcode($message, $errors);

	if (empty($errors))
	{
		if ($message == '')
			$errors[] = $lang_post['No message'];
		else if ($pun_config['o_censoring'] == '1')
		{
			// Censor message to see if that causes problems
			$censored_message = pun_trim(censor_words($message));

			if ($censored_message == '')
				$errors[] = $lang_post['No message after censoring'];
		}
	}
	
	if (empty($errors))
	{
		$edited_sql = '';
		if (!isset($_POST['silent']) || $_POST['silent'] != 1 || !$is_admmod)
		{
			$cur_post['edited'] = time();
			$cur_post['edited_by'] = $pun_user['username'];
			$edited_sql = ', edited='.$cur_post['edited'].', edited_by=\''.$db->escape($pun_user['username']).'\'';
		}
		
		require PUN_ROOT.'include/search_idx.php';
		update_search_index('edit', $id, $message);
		
		// Update the post
		$db->query('UPDATE '.$db->prefix.'posts SET message=\''.$db->escape($message).'\''.$edited_sql.' WHERE id='.$id) or error('Unable to update post info', __FILE__, __LINE__, $db->error());

		echo '<message>'.parse_message($message, $cur_post['hide_smilies']).'</message>';

		if ($cur_post['edited_by'] != '')
			echo '<last_edit><p class="postedit"><em>'.$lang_topic['Last edit'].' '.pun_htmlspecialchars($cur_post['edited_by']).' ('.format_time($cur_post['edited']).')</em></p></last_edit>';
	}
	else
		echo implode("\n", $errors);
}

$db->end_transaction();
$db->close();
