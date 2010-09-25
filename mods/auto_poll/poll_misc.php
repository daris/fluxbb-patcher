<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)

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

// Load the poll.php language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php';
else
	require PUN_ROOT.'lang/English/poll.php';

// Load the post.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

// Load the delete.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/delete.php';



if (isset($_GET['edit']))
	$id = intval($_GET['edit']);
else if (isset($_GET['delete']))
	$id = intval($_GET['delete']);
else if (isset($_GET['reset']))
	$id = intval($_GET['reset']);
else
	$id = 0;

if ($id < 1)
	message($lang_common['Bad request']);


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


// Fetch some info about the topic and the forum
$result = $db->query('SELECT f.moderators FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.question!=\'\' AND t.id='.$id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_topic = $db->fetch_assoc($result);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

unset($cur_topic);



if (isset($_GET['edit']))
{
	// Do we have permission to edit this poll?
	if (!(($is_admmod && $pun_config['o_poll_mod_edit_polls'] == '1') || $pun_user['g_id'] == PUN_ADMIN))
		message($lang_common['No permission']);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_poll['Edit poll']);
	require PUN_ROOT.'header.php';

	// Mark it if there is an error
	$found_error = false;

	// When there is a modification
	if (isset($_POST['add_edit']) && isset($_POST['poll_id']))
	{
		confirm_referrer('poll_misc.php');

		$id = intval($_POST['poll_id']);
		$ptype = intval($_POST['ptype']);

		if ($ptype != '1' && $ptype != '2')
			message($lang_poll['Poll type unknown']);

		$question = pun_trim($_POST['req_question']);
        if ($question == '')
            $errors[] = $lang_poll['No question'];
        else if (pun_strlen($question) > 70)
            $errors[] = $lang_poll['Too long question'];
        else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($question) == $question && ($pun_user['g_id'] > PUN_MOD && !$pun_user['g_global_moderation']))
            $question = ucwords(strtolower($question)); 

        // This isn't exactly a good way to do it, but it works. I may rethink this code later
        $option = array();
        $lastoption = "null";
        while (list($key, $value) = each($_POST['poll_option'])) 
		{
			$value = pun_trim($value);
            if ($value != "") 
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

		
		// If there are errors, we display them
	    if (!empty($errors)) 
		{
			$found_error = true;
			$cur_index = 1;
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
		else
		{
			$edited_sql = (!isset($_POST['silent']) || !$is_admmod) ? $edited_sql = ', edited='.time().', edited_by=\''.$db->escape($pun_user['username']).'\'' : '';

			$db->query('UPDATE ' . $db->prefix . 'topics SET question=\'' . $db->escape($question) . '\' WHERE id='.$id) or error('Unable to update poll in topic table', __FILE__, __LINE__, $db->error());
			$db->query('UPDATE ' . $db->prefix . 'polls SET options=\'' . $db->escape(serialize($option)) . '\''.$edited_sql.' WHERE pollid='.$id) or error('Unable to update poll in poll table', __FILE__, __LINE__, $db->error());
			redirect('viewtopic.php?id='.$id, $lang_poll['Poll updated redirect']);
		}
	}
	
	// If found_error is false:
	// - then either someone has just clicked on modify
	// - or someone has already modified the poll, without finding any errors, and has already been redirected by the redirect above
	if ($found_error == false)
	{
		// Request SELECT
		$result = $db->query('SELECT t.question, p.options, p.ptype, f.moderators FROM '.$db->prefix.'polls AS p INNER JOIN '.$db->prefix.'topics AS t ON p.pollid=t.id INNER JOIN '.$db->prefix.'forums AS f ON t.forum_id=f.id WHERE p.pollid='.$id.' LIMIT 1') or error('Unable to find poll', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_poll = $db->fetch_assoc($result);

		$ptype = $cur_poll['ptype'];
		$option = unserialize($cur_poll['options']);
		$question = $cur_poll['question'];
		$cur_index = 1;
	}

		// Required to copy the Javascript of the header because the header was already called and $required_fields was not defined
		$required_fields = array('req_question' => $lang_poll['Question']);
		$focus_element = array('req_question');
		?>
			<script type="text/javascript">
			<!--
			function process_form(the_form)
			{
				var element_names = new Object()
			<?php

				// Output a JavaScript array with localised field names
				while (list($elem_orig, $elem_trans) = @each($required_fields))
					echo "\t".'element_names["'.$elem_orig.'"] = "'.addslashes(str_replace('&nbsp;', ' ', $elem_trans)).'"'."\n";

			?>

				if (document.all || document.getElementById)
				{
					for (i = 0; i < the_form.length; ++i)
					{
						var elem = the_form.elements[i]
						if (elem.name && elem.name.substring(0, 4) == "req_")
						{
							if (elem.type && (elem.type == "text" || elem.type == "textarea" || elem.type == "password" || elem.type == "file") && elem.value == '')
							{
								alert("\"" + element_names[elem.name] + "\" <?php echo $lang_common['required field'] ?>")
								elem.focus()
								return false
							}
						}
					}
				}

				return true
			}
			// -->
			</script>
		<?php

		// Fetch some info about the post, the topic and the forum
		$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.closed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_topic = $db->fetch_assoc($result);

		?>
		
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_topic['fid'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>

<div class="blockform">
	<h2><span><?php echo $lang_poll['Edit poll'] ?></span></h2>
	<div class="box">
		<form id="poll_edit" method="post" action="<?php echo 'poll_misc.php?edit='.$id; ?>" onsubmit="return process_form(this)">
			<input type="hidden" name="poll_id" value="<?php echo $id; ?>" />
			<input type="hidden" name="ptype" value="<?php echo $ptype; ?>" />
			<div class="inform">
				<fieldset>
				<?php
				// Regular poll type
				if ($ptype == 1)
				{
				?>
					<legend><?php echo $lang_poll['New poll legend'] ?></legend>
					<div class="infldset">
							<label><strong><?php echo $lang_poll['Question'] ?></strong><br /><input type="text" name="req_question" value="<?php echo pun_htmlspecialchars($question); ?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /><br /></label>
							<?php
							for ($x = 1; $x <= $pun_config['o_poll_max_fields'] ;$x++) 
							{
							?>
								<label><strong><?php echo $lang_poll['Option'] ?></strong><br /> <input type="text" name="poll_option[<?php echo $x; ?>]" value="<?php echo pun_htmlspecialchars($option[$x]); ?>" size="60" maxlength="55" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
							<?php
							} 
							?></div></fieldset><?php
				} 
				// Multiselect poll type
				else if ($ptype == 2)
				{
				?>
					<legend><?php echo $lang_poll['New poll legend multiselect'] ?></legend>
					<div class="infldset">
						<label><strong><?php echo $lang_poll['Question'] ?></strong><br /><input type="text" name="req_question" value="<?php echo pun_htmlspecialchars($question); ?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /><br /></label>
						<?php
						for ($x = 1; $x <= $pun_config['o_poll_max_fields']; $x++) 
						{
							?>
							<label><strong><?php echo $lang_poll['Option'] ?></strong><br /> <input type="text" name="poll_option[<?php echo $x; ?>]" value="<?php echo pun_htmlspecialchars($option[$x]); ?>" size="60" maxlength="55" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
							<?php
						}
						?></div></fieldset><?php
				}
				?>
						
<?php

$checkboxes = array();

if ($is_admmod)
{
	if ((isset($_POST['form_sent']) && isset($_POST['silent'])) || !isset($_POST['form_sent']))
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" checked="checked" />&nbsp;'.$lang_post['Silent edit'];
	else
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" />&nbsp;'.$lang_post['Silent edit'];
}

if (!empty($checkboxes))
{

?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode('</label>'."\n\t\t\t\t\t\t\t", $checkboxes).'</label>'."\n" ?>
						</div>
					</div>
				</fieldset>
<?php

}

?>
						
						
			</div>
			<p class="buttons"><input type="submit" name="add_edit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>

		<?php
}
else if (isset($_GET['delete']))
{
	// Do we have permission to delete this poll?
	if (!(($is_admmod && $pun_config['o_poll_mod_delete_polls'] == '1') || $pun_user['g_id'] == PUN_ADMIN))
		message($lang_common['No permission']);


	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_poll['Delete poll']);
	require PUN_ROOT.'header.php';



	if (isset($_POST['del_poll']))
	{
		confirm_referrer('poll_misc.php');

		// Delete the poll
		$db->query('DELETE FROM '.$db->prefix.'polls WHERE pollid='.$id) or error('Unable to delete poll in poll table', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE '.$db->prefix.'topics SET question=\'\' WHERE id='.$id) or error('Unable to delete poll in topic table', __FILE__, __LINE__, $db->error());

		redirect('viewtopic.php?id='.$id, $lang_poll['Poll deleted redirect']);
	}
	else
	{
		// Fetch some info about the post, the topic and the forum
		$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.closed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_topic = $db->fetch_assoc($result);

		$result = $db->query('SELECT question FROM '.$db->prefix.'topics WHERE id='.$id) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());
		$poll_question = $db->result($result);

		if (!$poll_question)
			message($lang_common['Bad request']);

		// get the poll data, query modified by 2.1
		$result = $db->query('SELECT ptype, options, voters, votes, created, edited, edited_by  FROM '.$db->prefix.'polls WHERE pollid='.$id) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());

		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_poll = $db->fetch_assoc($result);

		$options = unserialize($cur_poll['options']);
		if (!empty($cur_poll['voters']))
			$voters = unserialize($cur_poll['voters']);
		else
			$voters = array();

		$ptype = $cur_poll['ptype']; 
		// yay memory!
		// $cur_poll = null;
		$firstcheck = false;

	?>

	
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_topic['fid'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>

<div class="blockform">
	<h2><span><?php echo $lang_poll['Delete poll'] ?></span></h2>
	<div class="box blockpost">
		<form method="post" action="poll_misc.php?delete=<?php echo $id ?>">
			<input type="hidden" name="del_poll" value="1" />
			<div class="inform">
				<fieldset>
					<legend class="warntext"><?php echo $lang_poll['Confirm delete legend'] ?></legend>
					<div class="infldset">
						<div class="postmsg">
							<p><?php echo $lang_poll['Delete poll comply'] ?></p>
						</div>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo pun_htmlspecialchars($poll_question) ?></legend>
					<div class="infldset">
						<table>
<?php
		if (!empty($cur_poll['votes']))
				$votes = unserialize($cur_poll['votes']);
		else
			$votes = array();

		if ($ptype == 1 || $ptype == 2)
		{
			$total = 0;
			$total_votes = 0;
			$percent = 0;
			$percent_int = 0;
			foreach ($options as $key => $val)
			{
				if (isset($votes[$key]))
					$total += $votes[$key];
			}
			reset($options);
			if (!empty($cur_poll['voters']))
				$total_votes = count(unserialize($cur_poll['voters']));
		}

		foreach ($options as $key => $value)
		{
			if ($ptype == 1 || $ptype == 2)
			{
				if (isset($votes[$key]))
				{
					$percent =  $votes[$key] * 100 / $total;
					$percent_int = floor($percent);
				}
?>
							<tr>
								<td style="border: 0;"><?php echo pun_htmlspecialchars($value); ?></td>
								<td style="border: 0; width: 40%; padding-left: 6px; padding-right: 6px;"><h2 style="width: <?php if (isset($votes[$key]) && $percent_int != 0) echo ($percent_int).'%'; else echo "1%"; ?>; font-size: 1px; height: 2px; margin-bottom: 3px; PADDING-LEFT: 0px;PADDING-RIGHT: 0px; border-style: solid; border-width: 1px;"></h2></td>
								<td style="border: 0;"><?php if (isset($votes[$key])) echo $percent_int . "% (" . $votes[$key].')'; else echo "0% (0)"; ?></td>
							</tr>
<?php
			}
		}
?>
							<tr>
								<td colspan="3" style="border: 0; text-align: center"><?php echo $lang_poll['Voters'].': '.$total_votes ?></td>
							</tr>
						</table>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="delete" value="<?php echo $lang_delete['Delete'] ?>" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
	<?php
	}
}
else if (isset($_GET['reset']))
{
	// Do we have permission to reset this poll?
	if (!(($is_admmod && $pun_config['o_poll_mod_reset_polls'] == '1') || $pun_user['g_id'] == PUN_ADMIN))
		message($lang_common['No permission']);


	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_poll['Reset poll']);
	require PUN_ROOT.'header.php';



	if (isset($_POST['reset_poll']))
	{
		confirm_referrer('poll_misc.php');

		$edited_sql = (!isset($_POST['silent']) || !$is_admmod) ? $edited_sql = ', edited='.time().', edited_by=\''.$db->escape($pun_user['username']).'\'' : '';

		// Reset the poll
		$db->query('UPDATE '.$db->prefix.'polls SET voters=\'\', votes=\'\''.$edited_sql.' WHERE pollid='.$id) or error('Unable to reset poll votes', __FILE__, __LINE__, $db->error());

		redirect('viewtopic.php?id='.$id, $lang_poll['Poll reset redirect']);
	}
	else
	{
		// Fetch some info about the post, the topic and the forum
		$result = $db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.closed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_topic = $db->fetch_assoc($result);

		$result = $db->query('SELECT question FROM '.$db->prefix.'topics WHERE id='.$id) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());
		$poll_question = $db->result($result);

		if (!$poll_question)
			message($lang_common['Bad request']);

		// get the poll data, query modified by 2.1
		$result = $db->query('SELECT ptype, options, voters, votes, created, edited, edited_by  FROM '.$db->prefix.'polls WHERE pollid='.$id) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());

		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$cur_poll = $db->fetch_assoc($result);

		$options = unserialize($cur_poll['options']);
		if (!empty($cur_poll['voters']))
			$voters = unserialize($cur_poll['voters']);
		else
			$voters = array();

		$ptype = $cur_poll['ptype']; 
		// yay memory!
		// $cur_poll = null;
		$firstcheck = false;

	?>
	
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_topic['fid'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>

<div class="blockform">
	<h2><span><?php echo $lang_poll['Reset poll'] ?></span></h2>
	<div class="box blockpost">
		<form method="post" action="poll_misc.php?reset=<?php echo $id ?>">
			<input type="hidden" name="reset_poll" value="1" />
			<div class="inform">
				<fieldset>
					<legend class="warntext"><?php echo $lang_poll['Confirm reset legend'] ?></legend>
					<div class="infldset">
						<div class="postmsg">
							<p><?php echo $lang_poll['Reset poll comply'] ?></p>
						</div>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo pun_htmlspecialchars($poll_question) ?></legend>
					<div class="infldset">
						<table>
<?php
		if (!empty($cur_poll['votes']))
				$votes = unserialize($cur_poll['votes']);
		else
			$votes = array();

		if ($ptype == 1 || $ptype == 2)
		{
			$total = 0;
			$total_votes = 0;
			$percent = 0;
			$percent_int = 0;
			foreach ($options as $key => $val)
			{
				if (isset($votes[$key]))
					$total += $votes[$key];
			}
			reset($options);
			if (!empty($cur_poll['voters']))
				$total_votes = count(unserialize($cur_poll['voters']));
		}

		foreach ($options as $key => $value)
		{
			if ($ptype == 1 || $ptype == 2)
			{
				if (isset($votes[$key]))
				{
					$percent =  $votes[$key] * 100 / $total;
					$percent_int = floor($percent);
				}
?>
							<tr>
								<td style="border: 0;"><?php echo pun_htmlspecialchars($value); ?></td>
								<td style="border: 0; width: 40%; padding-left: 6px; padding-right: 6px;"><h2 style="width: <?php if (isset($votes[$key]) && $percent_int != 0) echo ($percent_int).'%'; else echo "1%"; ?>; font-size: 1px; height: 2px; margin-bottom: 3px; PADDING-LEFT: 0px;PADDING-RIGHT: 0px; border-style: solid; border-width: 1px;"></h2></td>
								<td style="border: 0;"><?php if (isset($votes[$key])) echo $percent_int . "% (" . $votes[$key].')'; else echo "0% (0)"; ?></td>
							</tr>
<?php
			}
		}
?>
							<tr>
								<td colspan="3" style="border: 0; text-align: center"><?php echo $lang_poll['Voters'].': '.$total_votes ?></td>
							</tr>
						</table>
					</div>
				</fieldset>
<?php

$checkboxes = array();

if ($is_admmod)
{
	if ((isset($_POST['form_sent']) && isset($_POST['silent'])) || !isset($_POST['form_sent']))
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" checked="checked" />&nbsp;'.$lang_post['Silent edit'];
	else
		$checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="'.($cur_index++).'" />&nbsp;'.$lang_post['Silent edit'];
}

if (!empty($checkboxes))
{

?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode('</label>'."\n\t\t\t\t\t\t\t", $checkboxes).'</label>'."\n" ?>
						</div>
					</div>
				</fieldset>
<?php

}

?>
			</div>
			<p class="buttons"><input type="submit" name="reset" value="<?php echo $lang_poll['Reset'] ?>" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
		</div>
	</div>
	<?php
	}
}
else
	message($lang_common['Bad request']);


require PUN_ROOT.'footer.php';
