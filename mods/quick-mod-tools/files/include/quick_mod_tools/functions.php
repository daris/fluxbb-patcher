<?php

function quick_mod_tools()
{
	global $pun_user, $cur_topic, $lang_misc, $lang_common, $lang_topic, $id, $is_admmod;

	$mod_tools = array();
	
	if ($is_admmod || ($pun_user['g_edit_posts'] == '1' && $pun_user['username'] == $cur_topic['poster']) && $cur_topic['closed'] == '0') // TODO: user could change their username
		$mod_tools[] = '<span class="t-edit" title="'.$lang_topic['Edit'].'" onclick="edit_subject('.$id.', '.$cur_topic['id'].', \''.$lang_common['Submit'].'\')"></span>';

	if ($is_admmod || ($pun_user['g_delete_topics'] == '1' && $pun_user['username'] == $cur_topic['poster']) && $cur_topic['closed'] == '0') // TODO: user could change their username
		$mod_tools[] = '<span class="t-delete" title="'.$lang_misc['Delete'].'" onclick="moderate_topic('.$id.', '.$cur_topic['id'].', \'delete\')"></span>';

	if ($is_admmod)
	{
		if ($cur_topic['sticky'] == '0')
			$mod_tools[] = '<span class="t-stick" title="'.$lang_common['Stick topic'].'" onclick="moderate_topic('.$id.', '.$cur_topic['id'].', \'stick\')"></span>';
		else
			$mod_tools[] = '<span class="t-unstick" title="'.$lang_common['Unstick topic'].'" onclick="moderate_topic('.$id.', '.$cur_topic['id'].', \'unstick\')"></span>';

		if ($cur_topic['closed'] == '0')
			$mod_tools[] = '<span class="t-close" title="'.$lang_misc['Close'].'" onclick="moderate_topic('.$id.', '.$cur_topic['id'].', \'close\')"></span>';
		else
			$mod_tools[] = '<span class="t-open" title="'.$lang_misc['Open'].'" onclick="moderate_topic('.$id.', '.$cur_topic['id'].', \'open\')"></span>';	
	}
	
	return $mod_tools;
}