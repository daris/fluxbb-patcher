<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;


$result = $db->query('SELECT question FROM '.$db->prefix.'topics WHERE id='.$id) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());
$poll_question = $db->result($result);


if ($poll_question)
{
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php';
	else
		require PUN_ROOT.'lang/English/poll.php';	
		
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
	
	// Start 2.1
	$edited = '';
	if ($cur_poll['edited'] != '')
		$edited = "\t\t\t\t\t".'<p class="postedit"><em>'.$lang_topic['Last edit'].' '.pun_htmlspecialchars($cur_poll['edited_by']).' ('.format_time($cur_poll['edited']).')</em></p>'."\n";
	// End 2.1
	
	
	$post_actions = array();

	// Generation poll action array (delete, edit, reset etc.)
	if ($pun_user['g_id'] != PUN_ADMIN && $is_admmod)
	{
		if ($pun_config['o_poll_mod_delete_polls'] == '1')
			$poll_actions[] = '<li class="postdelete"><span><a href="poll_misc.php?delete='.$id.'">'.$lang_topic['Delete'].'</a></span></li> ';
		if ($pun_config['o_poll_mod_edit_polls'] == '1')
			$poll_actions[] = '<li class="postedit"><span><a href="poll_misc.php?edit='.$id.'">'.$lang_topic['Edit'].'</a></span></li> ';
		if ($pun_config['o_poll_mod_reset_polls'] == '1')
			$poll_actions[] = '<li class="postedit"><span><a href="poll_misc.php?reset='.$id.'">'.$lang_poll['Reset'].'</a></span></li> ';
	}
	else if ($pun_user['g_id'] == PUN_ADMIN)
		$poll_actions[] = '<li class="postdelete"><span><a href="poll_misc.php?delete='.$id.'">'.$lang_topic['Delete'].'</a></span></li> <li class="postedit"><span><a href="poll_misc.php?edit='.$id.'">'.$lang_topic['Edit'].'</a></span></li> <li class="postedit"><span><a href="poll_misc.php?reset='.$id.'">'.$lang_poll['Reset'].'</a></span></li>';

	$poll_actions_code = '';
	if (!empty($poll_actions))
		$poll_actions_code = '<div class="postfootright" style="padding-right: 0; BACKGROUND-COLOR: transparent; BORDER-LEFT-WIDTH: 0px"><ul>'.implode("\n\t\t\t\t\t\t", $poll_actions).'</ul></div>';

?>
<div class="blockform">
	<h2><span><?php echo $lang_poll['Poll'] ?></span></h2>
	<div class="box blockpost poll">
<?php
	if ((!$pun_user['is_guest']) && (!in_array($pun_user['id'], $voters)) && ($cur_topic['closed'] == '0') && (($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1')) || $is_admmod)) 
	{
?>
		<form id="post" method="post" action="poll_vote.php">
			<div class="inform">
				<fieldset>
					<legend><?php echo pun_htmlspecialchars($poll_question) ?></legend>
					<div class="infldset">
						<input type="hidden" name="poll_id" value="<?php echo $id; ?>" />
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="form_user" value="<?php echo (!$pun_user['is_guest']) ? pun_htmlspecialchars($pun_user['username']) : 'Guest';?>" />
<?php
		foreach ($options as $key => $value)
		{
?>
						<div class="rbox">
							<label>
<?php 		if ($ptype == 1) { ?>
								<input name="vote" <?php if (!$firstcheck) { echo 'checked="checked"'; $firstcheck = true; }; ?> type="radio" value="<?php echo $key ?>" />
<?php 		} else if ($ptype == 2) { ?>
								<input name="options[<?php echo $key ?>]" type="checkbox" value="1" />
<?php 		} else message($lang_common['Bad request']) ?>
								<?php echo pun_htmlspecialchars($value); ?>
							</label>
						</div>
<?php
		}
		echo $edited;
?>
					</div>
				</fieldset>
				<p align="center" class="buttons"><input type="submit" name="submit" tabindex="2" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /> <input type="submit" name="null" tabindex="2" value="<?php echo $lang_poll['Null vote'] ?>" accesskey="n" /></p>
			</div>
		</form>
<?php

    } 
	else 
	{
?>
		<div class="fakeform">
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
			else
				message($lang_common['Bad request']);
		}
?>
							<tr>
								<td colspan="3" style="border: 0; text-align: center"><?php echo $lang_poll['Voters'].': '.$total_votes ?></td>
							</tr>
						</table>
						<?php echo $edited ?>
					</div>
				</fieldset>
				<?php echo $poll_actions_code ?>
			</div>
		</div>
<?php 
	}
?>
	</div>
</div>
<?php
}
?>