<?php
/**
 * FluxBB Patcher 2.0
 * http://fluxbb.org/forums/viewtopic.php?id=4431
 */


if (!function_exists('json_decode'))
{
	function json_decode($content, $assoc = false)
	{
		require_once PATCHER_ROOT.'JSON.php';
		if ($assoc)
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		else
			$json = new Services_JSON;

		return $json->decode($content);
	}
}

if (!function_exists('json_encode'))
{
	function json_encode($content)
	{
		require_once PATCHER_ROOT.'JSON.php';
		$json = new Services_JSON;
		return $json->encode($content);
	}
}

define('STATUS_UNKNOWN', -1);
define('STATUS_NOT_DONE', 0);
define('STATUS_DONE', 1);
define('STATUS_REVERTED', 3);
define('STATUS_NOTHING_TO_DO', 5);


// Get the list of files to upload if patcher does not understand UPLOAD step
function list_files_to_upload($path, $from_dir = '', $to_dir = '')
{
	$files = array();

	$d = dir($path.'/'.$from_dir);
	while ($f = $d->read())
	{
		if (!in_array($f, array('.', '..', '.svn', 'Thumbs.db', 'LICENSE', 'README')) && !preg_match('/^(readme|update).*?\.txt$/', $f))
		{
			if (is_dir($path.'/'.$from_dir.'/'.$f))
				$files = array_merge($files, list_files_to_upload($path, $from_dir.'/'.$f, $to_dir.'/'.$f));
			else
				$files[ltrim($from_dir.'/'.$f, '/')] = $to_dir.'/';
		}
	}

	return $files;
}


// Sort $mods array by Mod title
function mod_title_compare($a, $b)
{
	return strnatcasecmp($a->title, $b->title);
}


function make_regexp($string)
{
	// Escape special regular expressions
	$string = preg_quote($string, '#');

	// Replace tabs, spaces and newline characters with \s* matching one or more spaces
	return $string;// preg_replace('#\s+#s', '\s*', $string);
}


// Looks for the first occurence of $needle in $haystack and replaces it with $replace.
function str_replace_once($needle, $replace, $haystack)
{
	$pos = strpos($haystack, $needle);
	// Nothing found
	if ($pos === false)
		return $haystack;

	return substr_replace($haystack, $replace, $pos, strlen($needle));
}


/*
	Functions for making difference between strings
*/
function diff($old, $new)
{
	$maxlen = 0;
	foreach($old as $oindex => $ovalue)
	{
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex)
		{
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if ($matrix[$oindex][$nindex] > $maxlen)
			{
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}
	}

	if ($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));

	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
	);
}


function replace_query($first, $second, $org = null)
{
	$res = '';
	$unbuffered = null;

	if (preg_match('/,\s*(true|false)$/i', $first, $m))
	{
		$first = substr($first, 0, strlen($first) - strlen($m[0])); // trim it from query
		$unbuffered = $m[1];
	}
	if (preg_match('/,\s*(true|false)$/i', $second, $m))
	{
		$second = substr($second, 0, strlen($second) - strlen($m[0])); // trim it from query
		$unbuffered = $m[1];
	}

	$first_prep = preparse_query($first); // move select and joins to variable
	$second_prep = preparse_query($second);

	$first = split_query($first);
	$second = split_query($second);

	$diff = diff($first, $second);
//	print_r($diff);

	foreach ($diff as $d)
	{
		if (is_array($d))
		{
			if (!isset($org))
				$res .= implode($d['i']);
			$res .= implode($d['d']);
		}
		else
			$res .= $d;
	}

	// Merge selected values
	$first_select = array_map('trim', explode(',', $first_prep[0]));
	$second_select = array_map('trim', explode(',', $second_prep[0]));

	if (isset($org))
	{
		sort($first_select);
		sort($second_select);
		$select_diff = diff($first_select, $second_select);
		$select = array();
		foreach ($select_diff as $d)
		{
			if (is_array($d) && isset($d['d']))
			{
				$select = array_merge($select, $d['d']);
			}
			else
				$select[] = $d;
		}
//		echo 'selects: <br />';print_r($select_diff);print_r($select);
	}
	else
		$select = array_unique(array_merge($first_select, $second_select));
	$res = str_replace('SELECT', 'SELECT '.implode(', ', $select), $res);

	// Merge joins
	$joins = array_unique(array_merge($first_prep[1], $second_prep[1]));
//	echo 'joins: <br />';print_r(diff($first_prep[1], $second_prep[1]));print_r($first_prep[1]);print_r($second_prep[1]);

	if (strpos($res, 'WHERE') !== false)
		$res = str_replace('WHERE', implode(' ', $joins).' WHERE', $res);
	else
		$res .= ' '.implode(' ', $joins);

	return $res.(isset($unbuffered) ? ', '.$unbuffered : '');
}


function revert_query($first, $second, $third)
{
	$res = '';
	$first_prep = preparse_query($first);
	$second_prep = preparse_query($second);
	$third_prep = preparse_query($third);

	$first = split_query($first);
	$second = split_query($second);
	$third = split_query($third);

	$diff = diff($first, $second);
/* 	echo '<br />diff = ';print_r($diff);
	echo '<br />third = ';print_r($third); */

	// Merge selected values
	$first_select = array_map('trim', explode(',', $first_prep[0]));
	$second_select = array_map('trim', explode(',', $second_prep[0]));
	$third_select = array_map('trim', explode(',', $third_prep[0]));

	$diff2 = diff($third, $diff);
//	print_r($diff2);
	foreach ($diff2 as $d)
	{
		if (is_array($d))
		{
			if (isset($d['i']))
			{
				foreach ($d['i'] as $key => $val)
				{
					if (is_array($val))
						$res .= implode($val['d']);
/*					else
						$res .= $val;*/
				}
			}
		}
		else
			$res .= $d;
	}

//	print_r($first_select);
//	print_r($second_select);
	$select = array_diff($first_select, $second_select);
	$select = array_merge($third_select, $select);
//	print_r($select);

	$joins = array_diff($first_prep[1], $second_prep[1]);
	$joins = array_merge($third_prep[1], $joins);
//	print_r($joins);

	$res = str_replace('SELECT', 'SELECT '.implode(', ', $select), $res);

	if (strpos($res, 'WHERE') !== false)
		$res = str_replace('WHERE', implode(' ', $joins).' WHERE', $res);
	else
		$res .= ' '.implode(' ', $joins);
//	echo '<br />res = '.$res.'<br />';

	return $res;
}


function split_query($string)
{
	return preg_split('/([\s\(\)])/', $string, 0, PREG_SPLIT_DELIM_CAPTURE);
}


function preparse_query(&$string)
{
	$exp = preg_split('/(SELECT|FROM|WHERE|LEFT JOIN|INNER JOIN|OUTER JOIN|ORDER BY|LIMIT)/i', $string, 0, PREG_SPLIT_DELIM_CAPTURE);
	$joins = array();
	$select = '';
	for ($i = 1; $i < count($exp); $i++)
	{
		if (strpos($exp[$i], 'SELECT') !== false)
		{
			$cur_sel = $exp[$i].$exp[++$i];
			$string = str_replace($cur_sel, 'SELECT ', $string);
			$select = trim($exp[$i]);
		}
		elseif (strpos($exp[$i], 'JOIN') !== false)
		{
			$cur_join = $exp[$i].$exp[++$i];
			$string = str_replace($cur_join, '', $string);
			$joins[] = trim($cur_join);
		}
	}
	return array($select, $joins);
}


/*
	Backup / Upload
*/

function create_backup($backup)
{
	global $lang_admin_plugin_patcher, $fs;

	if (!$fs->is_writable(BACKUPS_DIR))
		message(sprintf($lang_admin_plugin_patcher['Directory not writable'], 'backups'));

	$backup_file = BACKUPS_DIR.$backup.'.zip';

	if (file_exists($backup_file))
		message(sprintf($lang_admin_plugin_patcher['File already exists'], pun_htmlspecialchars($backup.'.zip')));

	$files = array();
	// Add FluxBB root directory to backup
	$dir = dir(PUN_ROOT);
	while ($file = $dir->read())
		if (!in_array($file, array('.', '..', 'config.php', 'install_mod.php', 'gen.php', 'revert_backup.php')) && is_file(PUN_ROOT.$file))
			$files[] = $file;

	// Add include directory to backup
	$dir = dir(PUN_ROOT.'include');
	while ($file = $dir->read())
		if (!in_array($file, array('.', '..')) && is_file(PUN_ROOT.'include/'.$file))
			$files[] = 'include/'.$file;

	// Add lang/English directory to backup
	$dir = dir(PUN_ROOT.'lang/English/');
	while ($file = $dir->read())
		if (!in_array($file, array('.', '..')) && is_file(PUN_ROOT.'lang/English/'.$file))
			$files[] = 'lang/English/'.$file;

	$files[] = 'style/Air.css';

	$tmpfile = $fs->tmpname();
	$zip = new ZIP_ARCHIVE($tmpfile, true);
	if (!$zip->add($files))
		message('Cannot add files to archive');
	$zip->close();

	$fs->move($tmpfile, $backup_file);
}

function revert($file)
{
	global $pun_config, $lang_admin_plugin_patcher, $fs;

	$dirs_to_check = array('', 'include', 'lang/English');
	foreach ($dirs_to_check as $cur_dir)
		if (!$fs->is_writable(PUN_ROOT.$cur_dir))
			message(sprintf($lang_admin_plugin_patcher['Directory not writable'], $cur_dir));

	if (file_exists(PUN_ROOT.'patcher_config.php'))
		$fs->delete(PUN_ROOT.'patcher_config.php');

	$zip = new ZIP_ARCHIVE(BACKUPS_DIR.$file);
	if (!$zip->extract(PUN_ROOT))
		message($lang_admin_plugin_patcher['Failed to extract file']);
	$zip->close();

	redirect(PLUGIN_URL, $lang_admin_plugin_patcher['Files reverted redirect']);
}


function upload_mod()
{
	global $pun_config, $lang_admin_plugin_patcher, $fs;

	if (!$fs->is_writable(MODS_DIR))
		message(sprintf($lang_admin_plugin_patcher['Directory not writable'], 'mods'));

	if (!is_uploaded_file($_FILES['upload_mod']['tmp_name']))
		message($lang_admin_plugin_patcher['File was not sent']);

	$filename = $_FILES['upload_mod']['name'];
/*	if (!move_uploaded_file($_FILES['upload_mod']['tmp_name'], $file))
		message('Cant move uploaded file');
*/
	if (substr($filename, -4) != '.zip')
		message($lang_admin_plugin_patcher['Upload ZIP archive only']);

	$mod_id = substr($filename, 0, -4);
	if (strpos($mod_id, '_v') !== false)
		$mod_id = substr($mod_id, 0, strpos($mod_id, '_v'));

	if (is_dir(MODS_DIR.$mod_id) && !$fs->is_empty_directory(MODS_DIR.$mod_id))
		message(sprintf($lang_admin_plugin_patcher['Directory already exists'], pun_htmlspecialchars($mod_id)));

	if (!is_dir(MODS_DIR.$mod_id) && !$fs->mkdir(MODS_DIR.$mod_id))
		message(sprintf($lang_admin_plugin_patcher['Can\'t create mod directory'], pun_htmlspecialchars($mod_id)));

	$zip = new ZIP_ARCHIVE(MODS_DIR.$filename);
	if (!$zip->extract(MODS_DIR.$mod_id))
		message($lang_admin_plugin_patcher['Failed to extract file']);
	$zip->close();

	redirect(PLUGIN_URL.'&mod_id='.$mod_id, $lang_admin_plugin_patcher['Modification uploaded redirect']);
}


function download_update($mod_id, $version)
{
	global $lang_admin_plugin_patcher, $fs;

	if (!$fs->is_writable(MODS_DIR))
		message(sprintf($lang_admin_plugin_patcher['Directory not writable'], 'mods'));

	if (!$fs->is_writable(MODS_DIR.$mod_id))
		message(sprintf($lang_admin_plugin_patcher['Directory not writable'], 'mods/'.pun_htmlspecialchars($mod_id)));

//	$mod_id = preg_replace('/-+v[\d\.]+$/', '', str_replace('_', '-', $mod_id)); // strip version number
	$filename = basename($mod_id.'_v'.$version.'.zip');
	$tmpname = $fs->tmpname();
	download_file('http://fluxbb.org/resources/mods/'.urldecode($mod_id).'/releases/'.urldecode($version).'/'.urldecode($filename), $tmpname);

	// Clean modification directory
	if (is_dir(MODS_DIR.$mod_id))
		$fs->remove_directory(MODS_DIR.$mod_id);

	if (!$fs->mkdir(MODS_DIR.$mod_id))
		message(sprintf($lang_admin_plugin_patcher['Can\'t create mod directory'], pun_htmlspecialchars($mod_id)));

	$zip = new ZIP_ARCHIVE($tmpname);
	if (!$zip->extract(MODS_DIR.$mod_id))
		message($lang_admin_plugin_patcher['Failed to extract file']);
	$zip->close();

	$redirect_url = (isset($_GET['update'])) ? '&mod_id='.$mod_id.'&action=update' : '';

	redirect(PLUGIN_URL.$redirect_url, $lang_admin_plugin_patcher['Modification updated redirect']);
}

function download_mod($mod_id)
{
	global $lang_admin_plugin_patcher, $fs;

	if (!$fs->is_writable(MODS_DIR))
		message(sprintf($lang_admin_plugin_patcher['Directory not writable'], 'mods'));

	if (is_dir(MODS_DIR.$mod_id) && !$fs->is_empty_directory(MODS_DIR.$mod_id))
		message(sprintf($lang_admin_plugin_patcher['Directory already exists'], 'mods/'.pun_htmlspecialchars($mod_id)));

//	$mod_id = preg_replace('/-+v[\d\.]+$/', '', str_replace('_', '-', $mod_id)); // strip version number
	$page = trim(@file_get_contents('http://fluxbb.org/api/json/resources/mods/'.urldecode($mod_id).'/'));
	$mod_info = json_decode($page, true);
	$last_release = 0;
	if (isset($mod_info['releases']) && count($mod_info['releases']) > 0)
	{
		reset($mod_info['releases']);
		$last_release = key($mod_info['releases']);
	}

	$filename = basename($mod_id.'_v'.$last_release.'.zip');
	$tmpname = $fs->tmpname();
	download_file('http://fluxbb.org/resources/mods/'.urldecode($mod_id).'/releases/'.urldecode($last_release).'/'.urldecode($filename), $tmpname);

	if (!is_dir(MODS_DIR.$mod_id) && !$fs->mkdir(MODS_DIR.$mod_id))
		message(sprintf($lang_admin_plugin_patcher['Can\'t create mod directory'], pun_htmlspecialchars($mod_id)));

	$zip = new ZIP_ARCHIVE($tmpname);
	if (!$zip->extract(MODS_DIR.$mod_id))
		message($lang_admin_plugin_patcher['Failed to extract file']);
	$zip->close();

	redirect(PLUGIN_URL.'&mod_id='.$mod_id, $lang_admin_plugin_patcher['Modification downloaded redirect']);
}


function get_mod_repo($refresh = false)
{
	global $mod_repo;

	if (defined('PATCHER_NO_DOWNLOAD'))
		return array();

	$mod_repo = array();
	if (!defined('PUN_MOD_REPO_LOADED') && file_exists(FORUM_CACHE_DIR.'cache_mod_repo.php'))
		require FORUM_CACHE_DIR.'cache_mod_repo.php';

	// We have old $mod_repo structure, need to update it
	if (!isset($mod_repo['mods']))
	{
		$mods = $mod_repo;
		unset($mods['last_checked']);
		unset($mods['last_check_failed']);
		$mod_repo['mods'] = $mods;
	}

	$mod_repo_before = $mod_repo;

	if (isset($mod_repo['last_checked']) && !$refresh && time() < $mod_repo['last_checked'] + 3600) // one hour
		return $mod_repo;

	$page = trim(@file_get_contents('http://fluxbb.org/api/json/resources/mods/'));
	if (!empty($page))
	{
		$mod_repo['mods'] = json_decode($page, true);
		foreach ($mod_repo['mods'] as $key => $cur_mod)
		{
			$mod_id = $cur_mod['id'];
			unset($cur_mod['id']);
			$mod_repo['mods'][$mod_id] = $cur_mod;
			unset($mod_repo['mods'][$key]);
		}
	}
	$mod_repo['last_checked'] = time();

	// Something changed? Update cache file
	if ($mod_repo != $mod_repo_before)
	{
		// Output updates as PHP code
		$fh = @fopen(FORUM_CACHE_DIR.'cache_mod_repo.php', 'wb');
		if (!$fh)
			error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \''.pun_htmlspecialchars(FORUM_CACHE_DIR).'\'', __FILE__, __LINE__);
		fwrite($fh, '<?php'."\n\n".'define(\'PUN_MOD_REPO_LOADED\', 1);'."\n\n".'$mod_repo = '.var_export($mod_repo, true).';'."\n\n".'?>');
		fclose($fh);
	}

	return $mod_repo;
}


function download_file($url, $save_to_file)
{
	global $lang_admin_plugin_patcher;

	if (defined('PATCHER_NO_DOWNLOAD'))
		return;

	$remote_file = @file_get_contents($url);
	if (!$remote_file)
		message(sprintf($lang_admin_plugin_patcher['File does not exist'], $url));

	// Save to file
	$file = @fopen($save_to_file, 'wb');
	if ($file === false)
		message(sprintf($lang_admin_plugin_patcher['Cannot write to'], $save_to_file));

	fwrite($file, $remote_file);
	fclose($file);
}


function do_clickable_html($text)
{
	$text = ' '.$text;

	$text = ucp_preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(https?|ftp|news){1}://([\p{L}\p{N}\-]+\.([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#uie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5://$6\', \'$5://$6\').stripslashes(\'$4$10$11$12\')', $text);
	$text = ucp_preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(www|ftp)\.(([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#uie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5.$6\', \'$5.$6\').stripslashes(\'$4$10$11$12\')', $text);

	return substr($text, 1);
}


function convert_mods_to_config()
{
	$inst_mods = $patcher_config = array();
	require PUN_ROOT.'mods.php';
	$patcher_config['installed_mods'] = $inst_mods;
	$patcher_config['steps'] = array();

	foreach ($patcher_config['installed_mods'] as $cur_mod => &$readme_files)
	{
		$flux_mod = new FLUX_MOD($cur_mod);
		if (!$flux_mod->is_valid)
			continue;

		foreach ($readme_files as $cur_readme_file)
			$patcher_config['steps'][$cur_mod.'/'.$cur_readme_file] = $flux_mod->get_steps($cur_readme_file);

		$readme_files['version'] = $flux_mod->version;
	}

	if (!defined('PATCHER_NO_SAVE'))
		file_put_contents(PUN_ROOT.'patcher_config.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'$patcher_config = '.var_export($patcher_config, true).';');
}



function patcher_error_handler($errno, $errstr, $errfile, $errline)
{
	if (!(error_reporting() & $errno)) {
		// This error code is not included in error_reporting
		return;
	}

	echo '<span style="color: red">';
	switch ($errno)
	{
		case E_USER_ERROR:
			echo "<b>Error</b> [$errno] $errstr<br />\n";
			echo "  Fatal error on line $errline in file $errfile";
			echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
			echo "Aborting...<br />\n";
			break;

		case E_USER_WARNING:
			echo "<b>Warning</b> [$errno] $errstr";
			break;

		case E_USER_NOTICE:
			echo "<b>Notice</b> [$errno] $errstr";
			break;

		case E_WARNING:
			echo "<b>Warning</b> [$errno] $errstr";
			break;

		default:
			echo "Unknown error type: [$errno] $errstr";
			break;
	}

	$path = realpath(PUN_ROOT);
	if (isset($errfile))
	{
		$file = realpath($errfile);
		if (substr($file, 0, strlen($path)) == $path)
			$file = substr($file, strlen($path));
		echo ' in '.htmlspecialchars($file).', '.$errline;
	}

	echo '</span><br />';
	$backtrace = debug_backtrace();
	foreach ($backtrace as $cur_backtrace)
	{
		if (isset($cur_backtrace['file']))
		{
			$file = realpath($cur_backtrace['file']);
			if (substr($file, 0, strlen($path)) == $path)
				$file = substr($file, strlen($path));
			echo htmlspecialchars($file).', '.$cur_backtrace['line'].': ';
;
		}
		if (isset($cur_backtrace['function']))
		{
			if (isset($cur_backtrace['class']))
				echo htmlspecialchars($cur_backtrace['class']).'->';

			echo htmlspecialchars($cur_backtrace['function']);
			echo '(<span style="color: 999">';
		}
		if (isset($cur_backtrace['args']))
		{
			$args = array();
			foreach ($cur_backtrace['args'] as $cur_arg)
			{
				$args[] = var_export($cur_arg, true);
			}
			echo implode(', ', $args);
		}
		echo '</span>);<br />';
	}
	echo '<br />';

	if ($errno == E_USER_ERROR)
		exit(1);
	$GLOBALS['pun_config']['o_redirect_delay'] = 10000; // :)

	/* Don't execute PHP internal error handler */
	return true;
}