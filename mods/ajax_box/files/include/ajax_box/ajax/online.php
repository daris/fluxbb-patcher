<?php

define('PUN_ROOT', '../../../');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';


if ($pun_config['o_users_online'] == '1')
{
	// Fetch users online info and generate strings for output
	$num_guests = 0;
	$users = array();
	$result = $db->query('SELECT user_id, ident FROM '.$db->prefix.'online WHERE idle=0 ORDER BY ident', true) or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

	while ($pun_user_online = $db->fetch_assoc($result))
	{
		if ($pun_user_online['user_id'] > 1)
		{
			if ($pun_user['g_view_users'] == '1')
				$users[] = "\n\t\t\t\t".'<dd><a href="profile.php?id='.$pun_user_online['user_id'].'">'.pun_htmlspecialchars($pun_user_online['ident']).'</a>';
			else
				$users[] = "\n\t\t\t\t".'<dd>'.pun_htmlspecialchars($pun_user_online['ident']);
		}
		else
			++$num_guests;
	}

	$num_users = count($users);


	if ($num_users > 0)
		echo '<dt><strong>'.$lang_index['Online'].' </strong></dt>'."\t\t\t\t".implode(',</dd> ', $users).'</dd>';
	else
		echo "\t\t\t".'<div class="clearer"></div>'."\n";

}

