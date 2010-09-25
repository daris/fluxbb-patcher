<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;


if (empty($cur_topic))
{
	if ($pun_user['is_guest'] || $pun_config['o_show_dot'] == '0')
		$sql = str_replace('moved_to', 'moved_to, question', $sql);
	else
		$sql = str_replace('t.moved_to', 't.moved_to, t.question', $sql);
}
else
{
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/poll.php';
	else
		require PUN_ROOT.'lang/English/poll.php';	

	if ($cur_topic['question'] != '')
	{
		if ($pun_config['o_censoring'] == '1')
			$cur_topic['question'] = censor_words($cur_topic['question']);

		$subject = $lang_poll['Poll'].': '.$subject;
	}
}

?>
