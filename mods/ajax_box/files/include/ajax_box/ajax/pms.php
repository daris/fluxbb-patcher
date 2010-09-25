<?php

define('PUN_ROOT', '../../../');
require PUN_ROOT.'include/common.php';

if (!$pun_user['is_guest'] && $pun_user['g_pm'] == 1 && $pun_config['o_pms_enabled'])
{
	require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';

	// Check for new messages
	$result_messages = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'messages WHERE showed=0 AND owner='.$pun_user['id']) or error('Unable to check for new messages', __FILE__, __LINE__, $db->error());
	
	$messages = '';
	
	if ($new_messages = $db->result($result_messages, 0))
		$messages = '<strong>'.$new_messages.' nowych wiadomości</strong>';
	else
		$messages = 'Prywatne wiadomości';

	echo '<a href="message_list.php">'.$messages.'</a>';
}
