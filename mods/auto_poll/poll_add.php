<?php
/***********************************************************************

  Copyright (C) 2002-2008  PunBB

  This file is part of PunBB.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id < 1)
	message($lang_common['Bad request']);



// Fetch some info about the topic and/or the forum
$result = $db->query('SELECT f.forum_name, t.poster, f.moderators FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_posting = $db->fetch_assoc($result);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

// Do we have permission to post polls?
if ($pun_config['o_poll_enabled'] != '1' || (!$is_admmod && ($pun_user['g_post_polls'] == '0' || $cur_posting['poster'] != $pun_user['username'])))
	message($lang_common['No permission']);


// Load the post.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

// Load the poll.php language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php';
else
	require PUN_ROOT.'lang/English/poll.php';	


// Start with a clean slate
$errors = array();


// Did someone just hit "Submit" or "Preview"?
if (isset($_POST['form_sent']))
{
	// ********************************** Mod poll check start

			// Get the question
	        $question = pun_trim($_POST['req_question']);
	        if ($question == '')
	            $errors[] = $lang_poll['No question'];
	        else if (pun_strlen($question) > 70)
	            $errors[] = $lang_poll['Too long question'];
	        else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($question) == $question && ($pun_user['g_id'] > PUN_MOD && !$pun_user['g_global_moderation']))
	            $question = ucwords(strtolower($question)); 

	        // This isn't exactly a good way todo it, but it works. I may rethink this code later
	        $option = array();
	        $lastoption = 'null';
	        while (list($key, $value) = each($_POST['poll_option']))
			{
				$value = pun_trim($value);
	            if ($value != '')
				{
	                if ($lastoption == '')
	                    $errors[] = $lang_poll['Empty option'];

                    $option[$key] = pun_trim($value);
                    if (pun_strlen($option[$key]) > 55)
                        $errors[] = $lang_poll['Too long option'];
					else if ($key > $pun_config['o_poll_max_fields'])
						message($lang_common['Bad request']);
                    else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($option[$key]) == $option[$key] && ($pun_user['g_id'] > PUN_MOD && !$pun_user['g_global_moderation']))
                        $option[$key] = ucwords(strtolower($option[$key]));
	            }
	            $lastoption = pun_trim($value);
	        } 

			// People are naughty
			if (empty($option))
				$errors[] = $lang_poll['No options'];

			if (!array_key_exists(2,$option))
				$errors[] = $lang_poll['Low options'];

	// ********************************** Mod poll check end

	//require PUN_ROOT.'include/search_idx.php';

	$ptype = isset($_POST['ptype']) ? 2 : 1;

	$now = time();

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
			$db->query('UPDATE '.$db->prefix.'topics SET question=\''.$db->escape($question).'\' WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

			// query modified by 2.1
			$db->query('INSERT INTO ' . $db->prefix . 'polls (pollid, options, ptype, created) VALUES(' . $id . ', \'' . $db->escape(serialize($option)) . '\', ' . $ptype . ', '.$now.')') or error('Unable to create poll', __FILE__, __LINE__, $db->error());

			$new_pid = $db->insert_id();

			redirect('viewtopic.php?id='.$id, $lang_post['Post redirect']);
	}
}

// If a forum_id was specified in the url (new topic).
if ($id)
{
	$action = $lang_post['Post new topic'];
	$form = '<form id="post" method="post" action="poll_add.php?action=post&amp;id='.$id.'" onsubmit="return process_form(this)">';

	$forum_name = pun_htmlspecialchars($cur_posting['forum_name']);
}
else
	message($lang_common['Bad request']);


// ------------------------- Mod poll start



$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($action));
$cur_index = 1; 
$required_fields = array('req_email' => $lang_common['E-mail'], 'req_question' => $lang_poll['Question'], 'req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('post');

if (!$pun_user['is_guest'])
	$focus_element[] = 'req_question';
else
{
	$required_fields['req_username'] = $lang_post['Guest name'];
	$focus_element[] = 'req_question';
}

require PUN_ROOT . 'header.php';

?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $forum_name ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>

<?php 

// If there are errors, we display them
if (!empty($errors))
{
?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<p><?php echo $lang_post['Post errors info'] ?></p>
			<ul>
<?php
while (list(, $cur_error) = each($errors))
	echo "\t\t\t\t" . '<li><strong>' . $cur_error . '</strong></li>' . "\n";
?>
			</ul>
		</div>
	</div>
</div>

<?php
}
else if (isset($_POST['preview']))
{
	require_once PUN_ROOT . 'include/parser.php';

?>
<div id="postpreview" class="blockpost">
	<h2><span><?php echo $lang_poll['Poll preview'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postright">
				<div class="postmsg">
							<?php
							if ($ptype == 1)
							{
								?>
								<form action="" method="POST">
									<fieldset>
										<legend><?php echo pun_htmlspecialchars($question); ?></legend>
										<?php
										while (list($key, $value) = each($option)) 
										{
											if (!empty($value)) 
											{
												echo '<br /><input name="vote" type="radio" value="'.$key.'" /><span>'.pun_htmlspecialchars($value).'</span><br />';
											}
										}
										?><br />
									</fieldset>
								</form>
								<?php
							}
							else if ($ptype == 2)
							{
								?>
								<form action="" method="POST">
									<fieldset>
										<legend><?php echo pun_htmlspecialchars($question) ?></legend>
										<?php
										while (list($key, $value) = each($option))
										{
											if (!empty($value)) 
											{
												echo '<br /><input type="checkbox" /><span>' . pun_htmlspecialchars($value) . '</span><br />';
											}
										}
										?><br />
									</fieldset>
								</form>
								<?php	
							}
							?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php

}
			?>
			<div class="blockform">
				<h2><span><?php echo $action ?></span></h2>
				<div class="box">
					<?php echo $form . "\n" ?>
						<div class="inform">
							<fieldset>
								<legend><?php echo $lang_poll['New poll legend'] ?></legend>
								<div class="infldset">
									<input type="hidden" name="form_sent" value="1" />
										<label><strong><?php echo $lang_poll['Question'] ?></strong><br /><input type="text" name="req_question" value="<?php if (isset($_POST['req_question'])) echo pun_htmlspecialchars($question); ?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /><br /></label>
										<?php
										for ($x = 1; $x <= $pun_config['o_poll_max_fields'] ;$x++) 
										{
										?>
											<label><strong><?php echo $lang_poll['Option'] ?></strong><br /> <input type="text" name="poll_option[<?php echo $x; ?>]" value="<?php if (isset($_POST['poll_option'][$x])) echo pun_htmlspecialchars($option[$x]); ?>" size="60" maxlength="55" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
										<?php
										} 
										?></div></fieldset></div>
<?php   






// ------------------------- Mod poll start end


if (!isset($_GET['type'])) 
{
	$cur_index = 100;


$checkboxes = array();

$checkboxes[] = '<label><input type="checkbox" name="ptype" value="1" tabindex="'.($cur_index++).'"'.(isset($_POST['ptype']) ? ' checked="checked"' : '').' />'.$lang_poll['Allow multiselect'];


if (!empty($checkboxes))
{

?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode('<br /></label>'."\n\t\t\t\t", $checkboxes).'<br /></label>'."\n" ?>
						</div>
					</div>
				</fieldset>
<?php

}

?>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /><input type="submit" name="preview" value="<?php echo $lang_post['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>

<?php
}



require PUN_ROOT.'footer.php';