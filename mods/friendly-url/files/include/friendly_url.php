<?php

// Generate a hyperlink with parameters and anchor
function forum_link($link, $args = null)
{
	global $pun_config;

	$gen_link = $link;
	if ($args == null)
		$gen_link = $link;
	else if (!is_array($args))
		$gen_link = str_replace('$1', $args, $link);
	else
	{
		for ($i = 0; isset($args[$i]); ++$i)
			$gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
	}
	
	if (substr($gen_link, 0, 4) != 'http')
		$gen_link = (function_exists('get_base_url') ? get_base_url(true) : $pun_config['o_base_url']).'/'.$gen_link;

	return $gen_link;
}


// Generate a hyperlink with parameters and anchor and a subsection such as a subpage
function forum_sublink($link, $sublink, $subarg, $args = null)
{
	global $pun_config, $forum_url;

	if ($sublink == $forum_url['page'] && $subarg == 1)
		return forum_link($link, $args);

	$gen_link = $link;
	if (!is_array($args) && $args != null)
		$gen_link = str_replace('$1', $args, $link);
	else
	{
		for ($i = 0; isset($args[$i]); ++$i)
			$gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
	}

	if (isset($forum_url['insertion_find']))
		$gen_link = (function_exists('get_base_url') ? get_base_url(true) : $pun_config['o_base_url']).'/'.str_replace($forum_url['insertion_find'], str_replace('$1', str_replace('$1', $subarg, $sublink), $forum_url['insertion_replace']), $gen_link);
	else
		$gen_link = (function_exists('get_base_url') ? get_base_url(true) : $pun_config['o_base_url']).'/'.$gen_link.str_replace('$1', $subarg, $sublink);

	return $gen_link;
}

// Make a string safe to use in a URL
function sef_friendly($str)
{
	global $pun_config, $pun_user;
	static $lang_url_replace, $forum_reserved_strings;

	if (!isset($lang_url_replace))
	{
		if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/url_replace.php'))
			require PUN_ROOT.'lang/'.$pun_user['language'].'/url_replace.php';
		else
			require PUN_ROOT.'lang/English/url_replace.php';
	}
	
	if (!isset($forum_reserved_strings))
	{
		// Bring in any reserved strings
		if (file_exists(PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/reserved_strings.php'))
			require PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/reserved_strings.php';
		else
			require PUN_ROOT.'include/url/Default/reserved_strings.php';
	}

	$str = strtr($str, $lang_url_replace);
	$str = strtolower(utf8_decode($str));
	$str = pun_trim(preg_replace(array('/[^a-z0-9\s]/', '/\s+/'), array('', '-'), $str), '-');

	foreach ($forum_reserved_strings as $match => $replace)
		if ($str == $match)
			return $replace;

	return $str;
}

// Get topic/forum title
function sef_name($type, $id)
{
	global $db;
	
	if ($type == 'f') // forum
		$result = $db->query('SELECT forum_name FROM '.$db->prefix.'forums WHERE id='.$id) or error('Unable to fetch forum name', __FILE__, __LINE__, $db->error());
	
	else // topic
		$result = $db->query('SELECT subject FROM '.$db->prefix.'topics WHERE id='.$id) or error('Unable to fetch topic subject', __FILE__, __LINE__, $db->error());

	return ($db->num_rows($result)) ? sef_friendly($db->result($result)) : '';
}

// Convert rewritten url back to normal url
function fix_referer()
{
	global $forum_rewrite_rules, $pun_config;
	
	if (!isset($_SERVER['HTTP_REFERER']))
		return '';

	if (!isset($forum_rewrite_rules))
	{
		// Bring in all the rewrite rules
		if (file_exists(PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/rewrite_rules.php'))
			require PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/rewrite_rules.php';
		else
			require PUN_ROOT.'include/url/Default/rewrite_rules.php';
	}
	
	// Revove base_url from referer
	$referer = str_replace(get_base_url(true).'/', '', $_SERVER['HTTP_REFERER']);
	
	// We go through every rewrite rule
	foreach ($forum_rewrite_rules as $rule => $rewrite_to)
	{
		// We have a match!
		if (preg_match($rule, $referer))
			return forum_link(preg_replace($rule, $rewrite_to, $referer));
	}
	return $_SERVER['HTTP_REFERER'];
}
