<?php
/**
 * FluxBB Patcher 2.0-dev
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

function loadPatcherConfig()
{
	global $patcherConfig;

	if (!isset($patcherConfig))
	{
		if (file_exists(PUN_ROOT.'patcher_config.php'))
		{
			$patcherConfig = require PUN_ROOT.'patcher_config.php';

			// Convert $patcher_config to $patcherConfig
			if (!is_array($patcherConfig))
				$patcherConfig = $patcher_config;
		}
		else
			$patcherConfig = array('installed_mods' => array(), 'steps' => array());
	}

	return $patcherConfig;
}

function savePatcherConfig($patcherConfig = null)
{
	global $fs;

	if (!isset($patcherConfig))
		global $patcherConfig;

	$fs->put(PUN_ROOT.'patcher_config.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'return '.var_export($patcherConfig, true).';'."\n");
}

// Get the list of files to upload if patcher does not understand UPLOAD step
function listFilesToUpload($path, $fromDir = '', $toDir = '')
{
	$files = array();

	$d = dir($path.'/'.$fromDir);
	while ($f = $d->read())
	{
		if (!in_array($f, array('.', '..', '.svn', 'Thumbs.db', 'LICENSE', 'README')) && !preg_match('/^(readme|update).*?\.txt$/', $f))
		{
			if (is_dir($path.'/'.$fromDir.'/'.$f))
				$files = array_merge($files, listFilesToUpload($path, $fromDir.'/'.$f, $toDir.'/'.$f));
			else
				$files[ltrim($fromDir.'/'.$f, '/')] = $toDir.'/';
		}
	}

	return $files;
}


// Sort $mods array by Mod title
function modTitleCompare($a, $b)
{
	return strnatcasecmp($a->title, $b->title);
}


function makeRegExp($string)
{
	// Escape special regular expressions
	$string = preg_quote($string, '#');

	// Replace tabs, spaces and newline characters with \s* matching one or more spaces
	return $string;// preg_replace('#\s+#s', '\s*', $string);
}


// Looks for the first occurence of $needle in $haystack and replaces it with $replace.
// TODO: used somewhere?
function strReplaceOnce($needle, $replace, $haystack)
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


function replaceQuery($first, $second, $org = null)
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

	$first_prep = preparseQuery($first); // move select and joins to variable
	$second_prep = preparseQuery($second);

	$first = splitQuery($first);
	$second = splitQuery($second);

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


function revertQuery($first, $second, $third)
{
	$res = '';
	$first_prep = preparseQuery($first);
	$second_prep = preparseQuery($second);
	$third_prep = preparseQuery($third);

	$first = splitQuery($first);
	$second = splitQuery($second);
	$third = splitQuery($third);

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


function splitQuery($string)
{
	return preg_split('/([\s\(\)])/', $string, 0, PREG_SPLIT_DELIM_CAPTURE);
}


function preparseQuery(&$string)
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

function createBackup($backup)
{
	global $langPatcher, $fs;

	if (!$fs->isWritable(BACKUPS_DIR))
		message(sprintf($langPatcher['Directory not writable'], 'backups'));

	$backup_file = BACKUPS_DIR.$backup.'.zip';

	if (file_exists($backup_file))
		message(sprintf($langPatcher['File already exists'], pun_htmlspecialchars($backup.'.zip')));

	$files = array();
	// Add FluxBB root directory to backup
	$dir = dir(PUN_ROOT);
	while ($file = $dir->read())
	{
		if (!in_array($file, array('.', '..', 'config.php', 'install_mod.php', 'gen.php', 'revert_backup.php')) && substr($file, -4) == '.php' && is_file(PUN_ROOT.$file))
			$files[] = $file;
	}

	// Add include directory to backup
	$dir = dir(PUN_ROOT.'include');
	while ($file = $dir->read())
	{
		if (!in_array($file, array('.', '..')) && substr($file, -4) == '.php' && is_file(PUN_ROOT.'include/'.$file))
			$files[] = 'include/'.$file;
	}

	// Add lang/English directory to backup
	$dir = dir(PUN_ROOT.'lang/English/');
	while ($file = $dir->read())
	{
		if (!in_array($file, array('.', '..')) && substr($file, -4) == '.php' && is_file(PUN_ROOT.'lang/English/'.$file))
			$files[] = 'lang/English/'.$file;
	}

	$files[] = 'style/Air.css';

	$zip = new Patcher_ZipArchive($fs->tmpname(), true);
	if (!$zip->add($files))
		message('Cannot add files to archive');
	$zip->close();

	$fs->move($zip->file, $backup_file);
}

function revert($file)
{
	global $pun_config, $langPatcher, $fs;

	$dirsToCheck = array('', 'include', 'lang/English');
	foreach ($dirsToCheck as $curDir)
		if (!$fs->isWritable(PUN_ROOT.$curDir))
			message(sprintf($langPatcher['Directory not writable'], $curDir));

	if (file_exists(PUN_ROOT.'patcher_config.php'))
		$fs->delete(PUN_ROOT.'patcher_config.php');

	$zip = new Patcher_ZipArchive(BACKUPS_DIR.$file);
	if (!$zip->extract(PUN_ROOT))
		message($langPatcher['Failed to extract file']);
	$zip->close();

	redirect(PLUGIN_URL, $langPatcher['Files reverted redirect']);
}


function uploadMod()
{
	global $pun_config, $langPatcher, $fs;

	if (!$fs->isWritable(MODS_DIR))
		message(sprintf($langPatcher['Directory not writable'], 'mods'));

	if (!is_uploaded_file($_FILES['upload_mod']['tmp_name']))
		message($langPatcher['File was not sent']);

	$filename = $_FILES['upload_mod']['name'];
/*	if (!move_uploaded_file($_FILES['upload_mod']['tmp_name'], $file))
		message('Cant move uploaded file');
*/
	if (substr($filename, -4) != '.zip')
		message($langPatcher['Upload ZIP archive only']);

	$modId = substr($filename, 0, -4);
	if (strpos($modId, '_v') !== false)
		$modId = substr($modId, 0, strpos($modId, '_v'));

	if (is_dir(MODS_DIR.$modId) && !$fs->isEmptyDir(MODS_DIR.$modId))
		message(sprintf($langPatcher['Directory already exists'], pun_htmlspecialchars($modId)));

	if (!is_dir(MODS_DIR.$modId) && !$fs->mkdir(MODS_DIR.$modId))
		message(sprintf($langPatcher['Can\'t create mod directory'], pun_htmlspecialchars($modId)));

	$zip = new Patcher_ZipArchive(MODS_DIR.$filename);
	if (!$zip->extract(MODS_DIR.$modId))
		message($langPatcher['Failed to extract file']);
	$zip->close();

	redirect(PLUGIN_URL.'&mod_id='.$modId, $langPatcher['Modification uploaded redirect']);
}


function downloadUpdate($modId, $version)
{
	global $langPatcher, $fs;

	if (!$fs->isWritable(MODS_DIR))
		message(sprintf($langPatcher['Directory not writable'], 'mods'));

	if (!$fs->isWritable(MODS_DIR.$modId))
		message(sprintf($langPatcher['Directory not writable'], 'mods/'.pun_htmlspecialchars($modId)));

//	$modId = preg_replace('/-+v[\d\.]+$/', '', str_replace('_', '-', $modId)); // strip version number
	$filename = basename($modId.'_v'.$version.'.zip');
	$tmpname = $fs->tmpname();
	downloadFile('http://fluxbb.org/resources/mods/'.urldecode($modId).'/releases/'.urldecode($version).'/'.urldecode($filename), $tmpname);

	// Clean modification directory
	if (is_dir(MODS_DIR.$modId))
		$fs->rmDir(MODS_DIR.$modId);

	if (!$fs->mkdir(MODS_DIR.$modId))
		message(sprintf($langPatcher['Can\'t create mod directory'], pun_htmlspecialchars($modId)));

	$zip = new Patcher_ZipArchive($tmpname);
	if (!$zip->extract(MODS_DIR.$modId))
		message($langPatcher['Failed to extract file']);
	$zip->close();

	$redirect_url = (isset($_GET['update'])) ? '&mod_id='.$modId.'&action=update' : '';

	redirect(PLUGIN_URL.$redirect_url, $langPatcher['Modification updated redirect']);
}

function downloadMod($modId)
{
	global $langPatcher, $fs;

	if (!$fs->isWritable(MODS_DIR))
		message(sprintf($langPatcher['Directory not writable'], 'mods'));

	if (is_dir(MODS_DIR.$modId) && !$fs->isEmptyDir(MODS_DIR.$modId))
		message(sprintf($langPatcher['Directory already exists'], 'mods/'.pun_htmlspecialchars($modId)));

//	$modId = preg_replace('/-+v[\d\.]+$/', '', str_replace('_', '-', $modId)); // strip version number
	$page = trim(@file_get_contents('http://fluxbb.org/api/json/resources/mods/'.urldecode($modId).'/'));
	$modInfo = json_decode($page, true);
	$last_release = 0;
	if (isset($modInfo['releases']) && count($modInfo['releases']) > 0)
	{
		reset($modInfo['releases']);
		$last_release = key($modInfo['releases']);
	}

	$filename = basename($modId.'_v'.$last_release.'.zip');
	$tmpname = $fs->tmpname();
	downloadFile('http://fluxbb.org/resources/mods/'.urldecode($modId).'/releases/'.urldecode($last_release).'/'.urldecode($filename), $tmpname);

	if (!is_dir(MODS_DIR.$modId) && !$fs->mkdir(MODS_DIR.$modId))
		message(sprintf($langPatcher['Can\'t create mod directory'], pun_htmlspecialchars($modId)));

	$zip = new Patcher_ZipArchive($tmpname);
	if (!$zip->extract(MODS_DIR.$modId))
		message($langPatcher['Failed to extract file']);
	$zip->close();

	redirect(PLUGIN_URL.'&mod_id='.$modId, $langPatcher['Modification downloaded redirect']);
}


function getModRepo($refresh = false)
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
		foreach ($mod_repo['mods'] as $key => $curMod)
		{
			$modId = $curMod['id'];
			unset($curMod['id']);
			$mod_repo['mods'][$modId] = $curMod;
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


function downloadFile($url, $save_to_file)
{
	global $langPatcher;

	if (defined('PATCHER_NO_DOWNLOAD'))
		return;

	$remote_file = @file_get_contents($url);
	if (!$remote_file)
		message(sprintf($langPatcher['File does not exist'], $url));

	// Save to file
	$file = @fopen($save_to_file, 'wb');
	if ($file === false)
		message(sprintf($langPatcher['Cannot write to'], $save_to_file));

	fwrite($file, $remote_file);
	fclose($file);
}

// TODO: used somewhere?
function doClickableHtml($text)
{
	$text = ' '.$text;

	$text = ucp_preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(https?|ftp|news){1}://([\p{L}\p{N}\-]+\.([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#uie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5://$6\', \'$5://$6\').stripslashes(\'$4$10$11$12\')', $text);
	$text = ucp_preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(www|ftp)\.(([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#uie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5.$6\', \'$5.$6\').stripslashes(\'$4$10$11$12\')', $text);

	return substr($text, 1);
}


function convertModsToConfig()
{
	$inst_mods = $patcherConfig = array();
	require PUN_ROOT.'mods.php';
	$patcherConfig['installed_mods'] = $inst_mods;
	$patcherConfig['steps'] = array();

	foreach ($patcherConfig['installed_mods'] as $curMod => &$readmeFiles)
	{
		$mod = new Patcher_Mod($curMod);
		if (!$mod->isValid)
			continue;

		foreach ($readmeFiles as $curReadmeFile)
			$patcherConfig['steps'][$curMod.'/'.$curReadmeFile] = $mod->getSteps($curReadmeFile);

		$readmeFiles['version'] = $mod->version;
	}

	if (!defined('PATCHER_NO_SAVE'))
		file_put_contents(PUN_ROOT.'patcher_config.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'$patcherConfig = '.var_export($patcherConfig, true).';');
}


function patcherError()
{
	global $patcher;

	if (is_object($patcher) && get_class($patcher) == 'Patcher')
		$patcher->revertModifiedFiles();

	$args = func_get_args();
	call_user_func_array('error', $args);
}
