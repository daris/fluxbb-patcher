<?php


function ua_get_filename($name, $folder)
{
	global $pun_config;
	
	$name = strtolower($name);
	$name = str_replace(' ', '', $name); // remove spaces
	$name = preg_replace('/[^a-z0-9_]/', '', $name); // remove special characters
	$name = pun_htmlspecialchars($name);
	$name = $pun_config['o_base_url'].'/img/user_agent/'.$folder.'/'.$name.'.png';
	return $name;
}

function ua_search_for_item($items, $useragent)
{
	foreach ($items as $item)
	{
		if (strpos($useragent, strtolower($item)) !== false)
			return $item;
	}
}

function get_useragent_names($useragent)
{
	if ($useragent == '')
	{
		$result = array(
			'system'			=> 'Unknown',
			'browser_img'		=> 'Unknown',
			'browser_version'	=> 'Unknown',
			'browser_name'		=> 'Unknown'
		);
		return $result;
	}
	
	$browser_img = '';
	$browser_version = '';
	
	$useragent = strtolower($useragent);
	
	// Browser detection
	$browsers = array('AWeb', 'Camino', 'Epiphany', 'Galeon', 'HotJava', 'iCab', 'MSIE', 'Chrome', 'Safari', 'Konqueror', 'Flock', 'Iceweasel', 'SeaMonkey', 'Firefox', 'Firebird', 'Netscape', 'Mozilla', 'Opera', 'Maxthon', 'PhaseOut', 'SlimBrowser');
	
	$browser = ua_search_for_item($browsers, $useragent);

	preg_match('#'.preg_quote(strtolower(($browser == 'Opera' ? 'Version' : $browser))).'[\s/]*([\.0-9]*)#', $useragent, $matches);
	$browser_version = $matches[1];

	if ($browser == 'MSIE')
	{
		if (intval($browser_version) > 6)
			$browser_img = 'Internet Explorer 7';
		$browser = 'Internet Explorer';
	}
	
	// System detection
	$systems = array('Amiga', 'BeOS', 'FreeBSD', 'HP-UX', 'Linux', 'NetBSD', 'OS/2', 'SunOS', 'Symbian', 'Unix', 'Windows', 'Sun', 'Macintosh', 'Mac');
	
	$system = ua_search_for_item($systems, $useragent);
	
	if ($system == 'Linux')
	{
		$systems = array('CentOS', 'Debian', 'Fedora', 'Freespire', 'Gentoo', 'Katonix', 'KateOS', 'Knoppix', 'Kubuntu', 'Linspire', 'Mandriva', 'Mandrake', 'RedHat', 'Slackware', 'Slax', 'Suse', 'Xubuntu', 'Ubuntu', 'Xandros', 'Arch', 'Ark');

		$system = ua_search_for_item($systems, $useragent);
		if ($system == '')
			$system = 'Linux';
		
		if ($system == 'Mandrake')
			$system = 'Mandriva';
	}
	elseif ($system == 'Windows')
	{
		$version = substr($useragent, strpos($useragent, 'windows nt ') + 11);
		if (substr($version, 0, 1) == 5)
			$system = 'Windows XP';
		elseif (substr($version, 0, 1) == 6)
		{
			if (substr($version, 0, 3) == 6.0)
				$system = 'Windows Vista';
			else
				$system = 'Windows 7';
		}
	}
	elseif ($system == 'Mac')
		$system = 'Macintosh';

	if (!$system)
		$system = 'Unknown';
	if (!$browser)
		$browser = 'Unknown';

	if (!$browser_img)
		$browser_img = $browser;

	$result = array(
		'system'			=> $system,
		'browser_img'		=> $browser_img,
		'browser_version'	=> $browser_version,
		'browser_name'		=> ($browser != 'Unknown') ? $browser.' '.$browser_version : $browser
	);

	return $result;
}

function get_useragent_icons($useragent)
{
	global $pun_user;
	static $user_agent_cache;

	if ($useragent == '')
		return '';

	if (!isset($user_agent_cache))
		$user_agent_cache = array();

	if (isset($user_agent_cache[$useragent]))
		return $user_agent_cache[$useragent];

	$agent = get_useragent_names($useragent);

	$result = '<img src="'.ua_get_filename($agent['system'], 'system').'" title="'.pun_htmlspecialchars($agent['system']).'" alt="'.pun_htmlspecialchars($agent['system']).'" style="margin-right: 1px"/>';
	$result .= '<img src="'.ua_get_filename($agent['browser_img'], 'browser').'" title="'.pun_htmlspecialchars($agent['browser_name']).'" alt="'.pun_htmlspecialchars($agent['browser_name']).'" style="margin-left: 1px"/>';

	$desc = ($pun_user['is_admmod']) ? ' style="cursor: pointer" onclick="alert(\''.pun_htmlspecialchars(addslashes($useragent).'\n\nSystem:\t'.addslashes($agent['system']).'\nBrowser:\t'.addslashes($agent['browser_name'])).'\')"' : '';

	$result = "\t\t\t\t\t\t".'<dd class="usercontacts"><span class="user-agent"'.$desc.'>'.$result.'</span></dd>'."\n";
	$user_agent_cache[$useragent] = $result;
	return $result;
}
