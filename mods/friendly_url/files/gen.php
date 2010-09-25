<?php
define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// As we modified generate_quickjump_cache function before (using readme.txt) we have to regenerate cache
require PUN_ROOT.'include/cache.php';
generate_quickjump_cache();

echo '<style>body {font: 11px Tahoma}</style>';

url_replace_dir('.');
url_replace_dir('include');

function url_replace_dir($directory, $recursive = false)
{	
	$d = dir($directory);
	while ($f = $d->read())
	{
		if (substr($f, 0, 1) != '.')
		{
			if (is_dir($directory.'/'.$f))
			{
				if ($recursive)
					url_replace_dir($directory.'/'.$f, $recursive);
			}
			elseif (substr($f, -4) == '.php')
				url_replace_file($directory.'/'.$f);
		}
	}
}


function url_replace_file($_cur_file)
{
	global $file, $num_changes, $cur_file;
	
	if (in_array(basename($_cur_file), array('gen.php', 'install.php')))
		return;
	
	$cur_file = $_cur_file;
	
	$old_file = $file = file_get_contents($cur_file);
	$num_changes = 0;
	echo '<b>'.$cur_file.'</b><br />';
	
	$file = preg_replace_callback('#(href|src|action)(=")(.*?)(".{0,30})#', 'url_replace', $file);
	$file = preg_replace_callback('#(Location)(: )(.*?)(\).*)#', 'url_replace', $file);
	$file = preg_replace_callback('#(redirect\()(\')(.*?)(\'*[,\)].*)#', 'url_replace', $file);
	
	if (basename($cur_file) != 'functions.php') // do not touch function paginate()
		$file = preg_replace_callback('#(paginate\()(.*?,.*?,\s*\'?)(.*)(\'?\)[;\.].*)#', 'url_replace', $file);
	
	if (basename($cur_file) == 'extern.php')
	{
		// v1.4.2
		$file = str_replace('$pun_config[\'o_base_url\'].($order_posted ? \'/viewtopic.php?id=\'.$cur_topic[\'id\'] : \'/viewtopic.php?id=\'.$cur_topic[\'id\'].\'&amp;action=new\')', 'forum_link($forum_url[\'topic\'.($order_posted ? \'_new_posts\' : \'\')], array($cur_topic[\'id\'], sef_friendly($cur_topic[\'subject\'])))', $file);
		// > v1.4.2
		$file = str_replace('$pun_config[\'o_base_url\'].\'/viewtopic.php?id=\'.$cur_topic[\'id\'].($order_posted ? \'\' : \'&amp;action=new\')', 'forum_link($forum_url[\'topic\'.($order_posted ? \'_new_posts\' : \'\')], array($cur_topic[\'id\'], sef_friendly($cur_topic[\'subject\'])))', $file);
		
		$file = preg_replace_callback('#(\$pun_config\[\'o_base_url\'\]\.\'?/?)()([^"]*?)(\'?[,;])#', 'url_replace', $file);
	}
	
	// change_password_key
	$file = str_replace('$pun_config[\'o_base_url\'].\'/profile.php?id=\'.$cur_hit[\'id\'].\'&action=change_pass&key=\'.$new_password_key', 'forum_link($forum_url[\'change_password_key\'], array($cur_hit[\'id\'], $new_password_key))', $file);
	// change_email_key
	$file = str_replace('$pun_config[\'o_base_url\'].\'/profile.php?action=change_email&id=\'.$id.\'&key=\'.$new_email_key', 'forum_link($forum_url[\'change_email_key\'], array($id, $new_email_key))', $file);

	// Do not define PUN_ROOT twice
	if (basename($cur_file) != 'rewrite.php' && !preg_match('#if \(!defined\(\'PUN_ROOT\'\)\)\s*define\(\'PUN_ROOT\',#si', $file))
		$file = str_replace('define(\'PUN_ROOT\',', 'if (!defined(\'PUN_ROOT\'))'."\n\t".'define(\'PUN_ROOT\',', $file);
	
	// login.php referer fix
	$file = str_replace('(isset($_SERVER[\'HTTP_REFERER\']) && preg_match(\'#^\'.preg_quote($pun_config[\'o_base_url\']).\'/(.*?)\.php#i\', $_SERVER[\'HTTP_REFERER\'])) ? htmlspecialchars($_SERVER[\'HTTP_REFERER\']) : $pun_config[\'o_base_url\'].\'/index.php\'',
		'(isset($_SERVER[\'HTTP_REFERER\']) && preg_match(\'#^\'.preg_quote($pun_config[\'o_base_url\']).\'/.*#i\', $_SERVER[\'HTTP_REFERER\'])) ? htmlspecialchars($_SERVER[\'HTTP_REFERER\']) : $pun_config[\'o_base_url\'].\'/index.php\'', $file);

	echo 'Num changes: '.$num_changes.'<br /><br />';

	// Was the file modified?
	if ($file != '' && $file != $old_file)
		file_put_contents($cur_file, $file);
}

function url_replace($matches)
{
	global $forum_url, $file, $num_changes, $cur_file;

	$ending = $tmp_action = '';
	$rewrite = true;
		
	$url = $matches[3];

	// Nothing to do?
	if (preg_match('/^[a-z]+:/', $url) || // http:// 
		strpos($matches[0], 'forum_link') !== false || // forum_link()
		strpos($matches[0], '[\'forum_url\']') !== false || // $GLOBALS['forum_url'] (paginate function)
		$matches[3] == '$pun_config[\'o_base_url\'].\'/' || // base_url (eg. used in help.php)
		$matches[3] == '$pun_config[\'o_base_url\']' || // base_url
		basename($cur_file) == 'common.php' && $matches[1] == 'href' && $matches[3] == 'install.php') // exclude install.php link in include/common.php
		return $matches[0];
		
	if (strpos($matches[1], '$pun_config[\'o_base_url\'].') !== false)
		$matches[1] = '';
	
	$url = str_replace('$pun_config[\'o_base_url\'].\'/', '\'', $url);
	$url = str_replace('$pun_config[\'o_base_url\']', '', $url);
	
	$url = preg_replace('/<\?php echo\s*/', '\'.', $url);
	$url = preg_replace('/\s*\?>/', '.\'', $url);
	$url = ltrim($url, '\'./');
	$url = trim($url);

	if (strpos($url, '#') !== false)
		$url = substr($url, 0, strpos($url, '#'));

	if ($matches[1] == 'Location' && substr($url, -1) == '\'')
		$url = substr($url, 0, -1);
	
	// Determine that current url is inside html tags
	$is_html = false;
	
	// We are checking orginal line from $file
	preg_match('#\n.*?'.preg_quote($matches[0], '#').'#', $file, $l_matches);

	if (substr(trim($l_matches[0]), 0, 1) == '<')
		$is_html = true;
	
	if ($is_html)
	{
		$tags = preg_split('/(<\?php|\?>)/', $l_matches[0], -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($tags as $key => $tag)
		{
			$str = $matches[3];
			if (strpos($matches[3], '<'))
				$str = substr($matches[3], 0, strpos($matches[3], '<'));

			if ($str != '' && strpos($tag, $str) !== false) // We got it :)
			{
				if ($key - 1 > 0 && isset($tags[$key - 1]))
					$is_html = ($tags[$key - 1] == '?>');
			}
		}
	}

	$args = array();
	$query = array();
	
	$url_parts = explode('?', $url);
	
	$link = $url_parts[0];

	$link = substr($link, 0, strpos($link, '.php'));
	$link = str_replace('view', '', $link);

	// Parse url for $_GET values
	if (isset($url_parts[1]))
	{
		$query_string = $url_parts[1];

		$params = array();
		$query_string = str_replace('&amp;', '&', $query_string);

		$query_items = explode('&', $query_string);
		foreach ($query_items as $item)
		{
			$value = explode('=', $item);
			$query[$value[0]] = isset($value[1]) ? $value[1] : '';
		}
	}
	// Convert file name to its equivalent of $forum_url variable
	if ($link == 'profile')
	{
		if (isset($query['section']))
		{
			$link = 'profile_'.$query['section'];
			unset($query['section']);
		}
		elseif (isset($query['action']))
		{
			if ($query['action'] == 'change_pass')
				$link = 'change_password';
			elseif (in_array($query['action'], array('change_email', 'upload_avatar', 'delete_avatar')))
				$link = $query['action'];

			if (isset($query['key']))
				$link .= '_key';
			
			unset($query['action']);
		}
		elseif (isset($query['id']) && count($query) == 1)
			$link = 'user';
	}
	
	elseif ($link == 'post')
	{
		if (isset($query['tid']))
		{
			if (isset($query['qid']))
				$link = 'quote';
			else
				$link = 'new_reply';
		}
		elseif (isset($query['fid']))
			$link = 'new_topic';
			
		if (isset($query['action'])) // don't rewrite post.php?action=*
		{
			$tmp_action = $query['action'];
			unset($query['action']);
		}
	}
	
	elseif ($link == 'topic')
	{
		if (count($query) > 2)
			$rewrite = false;
		elseif (isset($query['pid']))
			$link = 'post';
		else
		{
			$var = '$cur_topic';
			// Get variable name from id (eg. $cur_search['id'])
			if (isset($query['id']) && preg_match('#(\$[a-zA-Z0-9_]+)\[#', $query['id'], $m))
				$var = $m[1];
			elseif (basename($cur_file) == 'post.php')
				$var = '$cur_posting';

			$var .= '[\'subject\']';
			$query['subject'] = '(isset('.$var.') ? sef_friendly('.$var.') : sef_name(\'t\', '.printable_var($query['id']).'))';
		}
		
		if (isset($query['action']))
		{
			if ($query['action'] == 'new')
				$link = 'topic_new_posts';
			if (trim($query['action'], '\'') == 'last') // ?action=last'
				$link = 'topic_last_post';
			
			unset($query['action']);
		}
	}
	
	elseif ($link == 'forum')
	{
		$var = '$cur_forum[\'forum_name\']';
		// Get variable name from id (eg. $cur_search['id'])
		if (isset($query['id']) && preg_match('#(\$[a-zA-Z0-9_]+)(\[\'.*?\'\])#', $query['id'], $m))
		{
			if (isset($m[2]) && $m[2] == '[\'parent_forum_id\']')
				$var = $m[1].'[\'parent_forum\']';
			else
				$var = $m[1].'[\'forum_name\']';
		}

		$query['name'] = '(isset('.$var.') ? sef_friendly('.$var.') : sef_name(\'f\', '.printable_var($query['id']).'))';
	}
	
	elseif ($link == 'search')
	{
		if (isset($query['search_id']))
			$link .= '_results';
		
		elseif (isset($query['action']))
		{
			if ($query['action'] == 'new' || $query['action'] == 'show_new')
				$link .= '_new';
			elseif ($query['action'] == 'show_24h')
				$link .= '_24h';
			elseif ($query['action'] == 'show_unanswered')
				$link .= '_unanswered';
			elseif ($query['action'] == 'show_subscriptions')
			{
				$link .= '_subscriptions';
				if (!isset($query['user_id']))
					$query['user_id'] = '$pun_user[\'id\']';
			}
			elseif ($query['action'] == 'show_user')
				$link .= '_user';

			unset($query['action']);
		}
	}
	elseif ($link == 'misc')
	{
		if (isset($query['action']))
		{
			if ($query['action'] == 'markread')
				$link = 'mark_read';
			elseif ($query['action'] == 'markforumread')
				$link = 'mark_forum_read';
			elseif ($query['action'] == 'rules')
				$link = 'rules';
				
			unset($query['action']);
		}
		
		elseif (isset($query['report']))
			$link = 'report';
			
		elseif (isset($query['subscribe']))
			$link = 'subscribe';
		
		elseif (isset($query['unsubscribe']))
			$link = 'unsubscribe';
		
		elseif (isset($query['email']))
			$link = 'email';	
	}
	
	elseif ($link == 'userlist')
	{
		$link = 'users';
		if (count($query) > 0)
		{
			// We need orginal order
			$query = array(
				'username' => isset($query['username']) ? $query['username'] : '',
				'show_group' => isset($query['show_group']) ? $query['show_group'] : -1,
				'sort_by' => isset($query['sort_by']) ? $query['sort_by'] : 'username',
				'sort_dir' => isset($query['sort_dir']) ? $query['sort_dir'] : 'ASC',
			);
			$link .= '_browse';
		}
	}

	elseif ($link == 'login' && isset($query['action']))
	{
		if ($query['action'] == 'in')
			$link = 'login';
		elseif ($query['action'] == 'out')
			$link = 'logout';
		elseif ($query['action'] == 'forget')
			$link = 'request_password';
		
		if ($matches[1] == 'action') // don't rewrite login.php?action=in for <form action="'.forum_link('*').'"> because there isn't such link in $forum_url
			$tmp_action = $query['action'];
		
		unset($query['action']);
	}
	
	elseif ($link == 'register' && isset($query['action']))
	{
		if ($matches[1] == 'action') // don't rewrite register.php?action=in for <form action="'.forum_link('*').'"> because there isn't such link in $forum_url
		{
			$tmp_action = $query['action'];
			unset($query['action']);
		}
	}
	
	elseif ($link == 'extern' && isset($query['action']) && $query['action'] == 'feed')
	{
		if (isset($query['fid']))
			$link = 'forum';
		elseif (isset($query['tid']))
			$link = 'topic';
		else
			$link = 'index';
			
		$link .= '_'.$query['type'];
		
		unset($query['action']);
		unset($query['type']);	
	}
	
	elseif ($link == 'moderate')
	{
		$link = 'moderate';
		if (isset($query['get_host']))
			$link = 'get_host';
		elseif (isset($query['move_topics']))
			$link = 'move';
		elseif (isset($query['open']))
			$link = 'open';
		elseif (isset($query['close']))
			$link = 'close';
		elseif (isset($query['stick']))
			$link = 'stick';
		elseif (isset($query['unstick']))
			$link = 'unstick';
			
		elseif (isset($query['tid']))
			$link .= '_topic';
		elseif (isset($query['fid']))
			$link .= '_forum';
	}
	
	elseif ($link == 'help' && $matches[3] != '')
		$ending = substr($matches[3], strpos($matches[3], '#'));


	foreach ($query as $value)
	{
		$value = trim($value, "'.");
		$value = trim($value);
		
		if (substr($value, 0, 1) != '$' && strpos($value, '(') === false)
			$value = '\''.$value.'\'';

		$args[] = $value;
	}
	
	if ((isset($forum_url[$link]) || strpos($link, 'profile_') !== false) && $rewrite) // $link = profile_'.$section
	{
		// Using $GLOBALS, cause the link could be inside function
		$link_p = '$GLOBALS[\'forum_url\'][\''.$link.'\']';
		
		if (strpos($link, 'profile_') !== false) // there might be .'' at end
			$link_p = str_replace('.\'\'', '', $link_p);
		
		if ($tmp_action != '' && $rewrite)
			$link_p .= '.\'?action='.rtrim($tmp_action, '\'').'\'';
		
		if (count($args) == 1)
			$link_p .= ', '.$args[0];
		elseif (count($args) > 1)
			$link_p .= ', array('.implode(', ', $args).')';
	}
	else
	{
		$link_p = correct_apostr($url);
		
		// Add temp action (after #)
		if ($tmp_action != '' && $rewrite)
			$link_p .= '.\'?action='.rtrim($tmp_action, '\'').'\'';
	}

	$num_changes++;

	if ($matches[1] == 'paginate(')
		$matches[2] = rtrim($matches[2], "'.");
		
	elseif ($matches[1] == '')
		$link_p = 'forum_link('.$link_p.')';
	
	else
	{
		if ($is_html) // html
			$link_p = '<?php echo forum_link('.$link_p.') ?>';
		else // php
			$link_p = '\'.forum_link('.$link_p.').\'';
	}

	if ($matches[1] == 'Location') // header(Location: '.forum_link('*')) function
		$link_p = rtrim($link_p, "'.");
		
	if ($matches[1] == 'redirect(') // redirect function
	{
		$link_p = trim($link_p, "'.");
		if ($matches[2] == '\'')
			$matches[2] = '';
			
		if (substr($matches[4], 0, 1) == '\'')
			$matches[4] = substr($matches[4], 1);
	}
	if ($matches[1] == '' && substr($matches[4], 0, 1) == '\'')
		$matches[4] = substr($matches[4], 1);

	$result = $matches[1].$matches[2].$link_p.$ending.$matches[4];
	
	// Display results
	echo '<span style="color: #AA0000">'.htmlspecialchars($matches[0]).'</span><br />'."\n".'<span style="color: #00AA00">'.htmlspecialchars($result).'</span><br /><br />'."\n\n";
	
	return $result;
}

function printable_var($var)
{
	return (strpos($var, '$') !== false) ? trim($var, "'.") : $var;
}

function correct_apostr($str)
{
	$str = trim($str, '\'.');
	$parts = preg_split('#([\'"]+|(\$[a-zA-Z0-9_\[\]\']+)|PUN_[^\.]+|[a-zA-Z0-9_]+\(.*?\))\s*\.\s*([\'"]+|(\$[a-zA-Z0-9_\[\]\']+)|PUN_[^\.]+|[a-zA-Z0-9_]+\(.*?\))#i', $str, -1, PREG_SPLIT_DELIM_CAPTURE);

	if (count($parts) == 0 || trim($str) == '')
		return $str;
	
	$first = $parts[0];
	// Remove first value if empty
	if (trim($parts[0]) == '')
	{
		unset($parts[0]);
		$first = count($parts) > 0 ? $parts[1] : '';
	}
	
	// Remove last value if empty
	if (trim($parts[count($parts) - 1]) == '')
		unset($parts[count($parts) - 1]);

	if (substr($first, 0, 1) != '$' && // variable
		substr($first, 0, 1) != '\'' && // string
		substr($first, 0, 4) != 'PUN_' && // constant
		strpos($first, '(') === false) // function
		$str = '\''.$str;

	$last = $parts[count($parts) - 1];
	if ((count($parts) > 1 && substr($parts[count($parts) - 2], 0, 1) == '\'') || // is string (apostrof in last-1 index)
		(substr($last, 0, 1) != '$' && // variable
		substr($last, 0, 4) != 'PUN_' && // constant
		strpos($last, '(') === false)) // function
		$str .= '\'';
	
	return $str;
}