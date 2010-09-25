<?php

function patch_files()
{
	global $mod, $file, $uploaded_files;

	$cur_file = $cur_filename = $find = '';
	
	$modified = $tag_open = false;
	$summary = $modified_files = array();
	
	if (!empty($uploaded_files))
		$tag_open = true;
	
	$readme_file = MODS_DIR.$mod.'/readme.txt';
	if (isset($file) && file_exists(MODS_DIR.$mod.'/'.$file))
		$readme_file = MODS_DIR.$mod.'/'.$file;

	$sections = load_readme($readme_file);
	
	$i = 1;
	foreach ($sections as $section)
	{
		$action = $section['action'];
		$code = $section['code'];
		
		$status = 0; // 0 = not done, 1 = done, 2 = already done
		
		$action_str = '';
		$style = '';

		switch ($action)
		{
			case 'OPEN':
			
				$tag_open = true;
				$code = trim($code);
				
				// if some file was opened, save it
				if ($cur_filename != '')
				{
					if ($modified && trim($cur_file) != '')
					{
						file_put_contents(PUN_ROOT.$cur_filename, $cur_file);
						$modified_files[] = $cur_filename;
					}
				}
				
				if (file_exists(PUN_ROOT.$code))
				{
					$cur_filename = $code;
					
					$cur_file = file_get_contents(PUN_ROOT.$code);
					$status = 1;
					$modified = false;
				}
				else
				{
					$cur_file = '';
					$cur_filename = $code;
					$action_str = ' (File does not exist)';
					$style = 'font-weight: bold; color: red';
				}

				break;
			
			case 'FIND':
			case 'FIND LINE':
			case 'SEARCH':

				$status = -1;
				$find = $code;
				break;
			
			case 'REPLACE':

				// Add QUERY ID at end of query line
				if (strpos($code, 'query(') !== false)
				{		
					preg_match_all('#\n\t*.*?query\(.*?\) or error.*\n#', "\n".$find."\n", $first_m, PREG_SET_ORDER);
					preg_match_all('#\n\t*.*?query\(.*?\) or error.*\n#', "\n".$code."\n", $second_m, PREG_SET_ORDER);
					
					foreach ($first_m as $key => $first)
					{
						$query_line = trim($first[0]);
						$replace_line = trim($second_m[$key][0]);

						$code = str_replace($replace_line, $replace_line.' // QUERY ID: '.md5($query_line), $code);
					}
				}

				// If it was already done
				if (strpos($cur_file, trim($code)) !== false)
					$status = 2;
				else
				{
					$cur_file = preg_replace('#'.make_regexp(trim($find)).'#si', trim($code), $cur_file);
					
					if (strpos($cur_file, trim($code)) !== false)
						$status = 1;

					// has query?
					elseif (strpos($find, 'query(') !== false)
					{
						preg_match_all('#\n\t*.*?query\(.*?\) or error.*\n#', "\n".$find."\n", $find_m, PREG_SET_ORDER);
						preg_match_all('#\n\t*.*?query\((.*?)\) or error.*\n#', "\n".$code."\n", $code_m, PREG_SET_ORDER);
						
						foreach ($find_m as $key => $first)
						{
							$first_line = trim($first[0]);
							$second_line = trim($code_m[$key][0]);
							$second_query = $code_m[$key][1];

							$query_id = md5($first_line);

							// Some mod modified this query before
							if (preg_match('#\n\s*.*?query\((.*?)\) or error.*?\/\/ QUERY ID: '.preg_quote($query_id).'#', $cur_file, $matches)) 
							{
								$query_line = trim($matches[0]);
								$modified_query = $matches[1];

								$replace_with = replace_query($modified_query, $second_query);

								if (!$replace_with)
									break;
								
								$line = str_replace($second_query, $replace_with, $second_line);

								if (strpos($cur_file, $line) !== false)
								{
									$status = 2;
									break;
								}
								
								$cur_file = preg_replace('#'.make_regexp($query_line).'#si', $line, $cur_file);
					
								if (strpos($cur_file, $line) !== false)
									$status = 1;
							}
						}
					}
				}
				$find = $code;
				
				break;
			
			case 'AFTER ADD':
			case 'AFTER INSERT':
		
				// If it was already done
				if (preg_match('#'.make_regexp($find).'\s*'.make_regexp($code).'#si', $cur_file))
					$status = 2;
				else
				{			
					$cur_file = preg_replace('#'.make_regexp(trim($find)).'#si', trim($find)."\n\n".$code, $cur_file);
				
					if (preg_match('#'.make_regexp($find).'\s*'.make_regexp($code).'#si', $cur_file))
						$status = 1;
				}
				break;
				
			case 'BEFORE ADD':

				// If it was already done
				if (preg_match('#'.make_regexp($code).'\s*'.make_regexp($find).'#si', $cur_file))
					$status = 2;
				else
				{
					$cur_file = preg_replace('#'.make_regexp(trim($find)).'#si', trim($code)."\n\n".$find, $cur_file);
				
					if (preg_match('#'.make_regexp($code).'\s*'.make_regexp($find).'#si', $cur_file))
						$status = 1;
				}
				break;
				
			case 'ADD AT THE BOTTOM OF THE FILE':
			case 'ADD AT THE BOTTOM OF THE FUNCTION':
			case 'AT THE END OF FILE ADD':
		
				// If it was already done
				if (preg_match('#'.make_regexp($code).'#si', $cur_file))
					$status = 2;
				elseif (trim($cur_file) != '')
				{
					$cur_file .= "\n\n".$code;
					$status = 1;
				}
				break;
				
			case 'RUN':
		
				if (trim($code) == 'install_mod.php')
					$status = 1;
				break;
					
			case 'DELETE':
				
				$code = trim($code);
				if ($code == 'install_mod.php' && file_exists($code) && unlink($code))
					$status = 1;
				elseif (!file_exists($code))
					$status = 2;
				break;
				
			case 'RENAME':
		
				$files = explode('to', $code);
				$file_to_rename = trim($files[0]);
				$new_file = trim($files[1]);
				
				if (file_exists($new_file))
					$status = 2;
				else
				{
					if (rename(PUN_ROOT.$file_to_rename, PUN_ROOT.$new_file))
						$status = 1;
				}
				break;

			default:
				$status = -1;
		}

		switch ($status)
		{
			case 0: $style = 'font-weight: bold; color: red'; break; // not done
			case 1: $style = 'color: green'; $action_str = 'DONE'; break; // done
			case 2: $style = 'color: orange'; $action_str = 'ALREADY DONE'; break; // already done
		}

		if (in_array($action, array('OPEN', 'RUN', 'DELETE')) || strpos($action, 'UPLOAD') !== false)
		{
			echo "\n\t\t\t\t\t\t".'</tbody>'."\n\t\t\t\t\t".'</table></div></div></div>';
			echo '<div class="blocktable"><div class="box"><div class="inbox"><table>'."\n".'<thead>'."\n".'<tr>'."\n".'<th id="action'.$i.'" style="'.($status == 0 ? $style : '').'"><span style="float: right"><a href="#action'.$i.'">#'.$i.'</a></span>'.$action.' '.htmlspecialchars($code).' '.($status == 0 ? $action_str : '').'</th>'."\n".'</tr>'."\n".'</thead>'."\n".'<tbody>';
		}
		else
		{
			if (!empty($action_str))
				$action_str = ' ('.$action_str.')';
					
			echo '<tr><td style="width: 10%"><span style="float: right; margin-right: 1em;"><a href="#action'.$i.'">#'.$i.'</a></span><span id="action'.$i.'" style="'.$style.'; display: block; margin-left: 1em">'.$action.' '.$action_str.'</span>';
			
			if (trim($code) != '')
				echo '<div class="codebox"><pre><code>'.htmlspecialchars($code).'</code></pre></div>';
				
			echo '</td></tr>';
		}
		
		if ($status == 1 && $action != 'OPEN' && !$modified)
			$modified = true;
			
		$summary['actions'][$status][$i] = $action;
		
		$i++;
	}
	
	// if some file was opened, save it
	if ($cur_filename != '' && trim($cur_file) != '' && $modified)
	{
		file_put_contents(PUN_ROOT.$cur_filename, $cur_file);
		$modified_files[] = $cur_filename;
	}

	if ($tag_open)
		echo "\n\t\t\t\t\t\t".'</tbody>'."\n\t\t\t\t\t".'</table></div></div></div>';

	if (file_exists(PUN_ROOT.'mods.php'))
		require PUN_ROOT.'mods.php';
	else
		$inst_mods = array();
		
	$inst_mods_org = $inst_mods;
	
	if (!isset($inst_mods[$mod]))
		$inst_mods[$mod] = array();
	
	if (!in_array($file, $inst_mods[$mod]))
		$inst_mods[$mod][] = $file;
		
	if ($inst_mods_org != $inst_mods)
		file_put_contents(PUN_ROOT.'mods.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'$inst_mods = '.var_export($inst_mods, true).';');
	
	$summary['modified_files'] = $modified_files;

	return $summary;
}


function load_readme($filepath)
{
	if (!file_exists($filepath))
		message('File '.$filepath.' does not exist');

	$readme = file_get_contents($filepath);
	$readme = substr($readme, strpos($readme, '#--'));
	
	$readme .= '#--';
	
	$sections = array();
	
	while (strpos($readme, '#--') !== false)
	{
		$readme = substr($readme, strpos($readme, '#--') + 3);
		
		// End of file
		if (trim($readme) == '')
			break;
			
		
		if (strpos($readme, '#--') !== false)
			$section = substr($readme, 0, strpos($readme, '#--'));
			
		$section = substr($section, strpos($section, '[') + 1);
		
//		$section = substr($section, strpos($section, '.') + 1); // +1 = dot

		$action = substr($section, 0, strpos($section, ']') - 1);
		
		//if (preg_match('/\]-+\s*#-/s', $section))
		
		if (strpos($action, '(') !== false)
			$action = substr($action, 0, strpos($action, '('));

		if (strpos($action, '.') !== false)
			$action = substr($action, strpos($action, '.') + 1);
			
		$action = trim($action);
		$action = str_replace(',', '', $action);
		$action = strtoupper($action);
		$action = preg_replace('#[^A-Z\s]#', '', $action);
		
		if (trim($action) == '')
			continue;

		if (substr($action, 0, 7) == 'REPLACE')
			$action = 'REPLACE';
		if ($action == 'ADD AFTER')
			$action = 'AFTER ADD';
		if ($action == 'ADD BEFORE')
			$action = 'BEFORE ADD';
		if ($action == 'ADD AFTER')
			$action = 'AFTER ADD';
		if ($action == 'OPEN FILE')
			$action = 'OPEN';
			
		if ($action == 'UPLOAD')
			continue;
		
		$code = substr($section, strpos($section, "\n") + 1);
		
		// Gizzmo's syntax - strip out ***** at end
		$code = preg_replace('#\*+$#', '', $code);
		
		// Remove blank string after # at start and at end
		$code = preg_replace('#^\#[ \r\t]*#', '', $code);
		$code = preg_replace('#\s*\#\s*$#s', '', $code);
		
		// Empty lines at start and at end
		$code = preg_replace('#^[\n\r]*[ \t]*[\n\r]+#', '', $code);
		$code = preg_replace('#[\n\r]+[ \t]*[\n\r]*$#', '', $code);

		$sections[] = array(
			'action'	=> $action,
			'code'		=> $code
		);
	}
	
	return $sections;
}

function make_regexp($string)
{
	if (!$string)
		return;
	
	$string = preg_quote($string, '#');

	// Replace tabs, spaces and newline characters with \s* matching one or more spaces
	$string = preg_replace('#[\s]+#s', '\s*', $string);

	return $string;
}


function mod_info($mod, $readme_file = 'readme.txt')
{
	$readme = MODS_DIR.$mod.'/'.$readme_file;
	
	$mod_info = array();

	if (empty($readme_file) || !file_exists(MODS_DIR.$mod.'/'.$readme_file))
		return array('Mod title' => ucfirst(str_replace('_', ' ', $mod)));
	
	$file = file_get_contents($readme);
	$file = substr($file, 0, strpos($file, 'Affect'));
	
	$file = str_replace('##', '', $file);
	$lines = explode("\n", $file);
	$last_info = '';
	foreach ($lines as $line)
	{
		$line = trim($line);
		if ($line == '')
			continue;
		
		if (strpos($line, ':') !== false)
		{
			$last_info = trim(substr($line, 0, strpos($line, ':')));
			$mod_info[$last_info] = trim(substr($line, strpos($line, ':') + 1));
		}
		elseif ($last_info != '' && !in_array($last_info, array('Release date')))
			$mod_info[$last_info] .= ' '.trim($line);

	}

	if (isset($mod_info['Author']) && strpos($mod_info['Author'], '(') !== false)
	{
		if (strpos($mod_info['Author'], '@') !== false)
		{
			$mod_info['Author email'] = substr($mod_info['Author'], strpos($mod_info['Author'], '(') + 1);
			$mod_info['Author email'] = substr($mod_info['Author email'], 0, strpos($mod_info['Author email'], ')'));
		}
		$mod_info['Author'] = substr($mod_info['Author'], 0, strpos($mod_info['Author'], '('));
	}

	if (isset($mod_info['Author']) && strpos($mod_info['Author'], ';') !== false)
		$mod_info['Author'] = substr($mod_info['Author'], 0, strpos($mod_info['Author'], ';'));

	if (!isset($mod_info['Mod title']) || trim($mod_info['Mod title']) == '')
		$mod_info['Mod title'] = ucfirst(str_replace('_', ' ', $mod));

	return $mod_info;
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
			if($matrix[$oindex][$nindex] > $maxlen)
			{ 
				$maxlen = $matrix[$oindex][$nindex]; 
				$omax = $oindex + 1 - $maxlen; 
				$nmax = $nindex + 1 - $maxlen; 
			} 
		}        
	} 
	
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new)); 
	
	return array_merge( 
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)), 
		array_slice($new, $nmax, $maxlen), 
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
	); 
}


function replace_query($first, $second)
{
	$res = '';
	$first_prep = preparse_query($first);
	$second_prep = preparse_query($second);

	$first = split_query($first);
	$second = split_query($second);

	$diff = diff($first, $second);

	foreach ($diff as $d)
	{
		if (is_array($d))
		{
			$res .= implode($d['i']);
			$res .= implode($d['d']);
		}
		else
			$res .= $d;
	}

	// Merge selected values
	$first_select = explode(',', $first_prep[0]);
	$first_select = array_map('trim', $first_select);
	
	$second_select = explode(',', $second_prep[0]);
	$second_select = array_map('trim', $second_select);
	
	$select = array_merge($first_select, $second_select);
	$select = array_unique($select);
	
	$res = str_replace('SELECT', 'SELECT '.implode(', ', $select), $res);

	// Merge joins
	$joins = array_merge($first_prep[1], $second_prep[1]);
	$joins = array_unique($joins);
	
	if (strpos($res, 'WHERE') !== false)
		$res = str_replace('WHERE', implode(' ', $joins).' WHERE', $res);
	else
		$res .= ' '.implode(' ', $joins);

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


function scan_dir($path, $basename = '')
{
	global $files;
	
	if (!file_exists($path))
		return;
	
	$d = dir($path);
	while ($f = $d->read())
	{
		if (!in_array($f, array('.', '..', '.svn', 'install_mod.php')))
		{
			if (is_dir($path.'/'.$f))
				scan_dir($path.'/'.$f, ($basename != '' ? $basename.'/' : '').$f);
			else
				$files[] = ($basename != '' ? $basename.'/' : '').$f;
		}
	}
}


/*
	Backup / Export
*/

function create_backup($backup)
{
	if (class_exists('ZipArchive'))
	{
		$backup_file = BACKUPS_DIR.$backup.'.zip';
		
		$list = array(
			'files_exclude' => array('install_mod.php', 'config.php', 'Thumbs.db'),
			'dirs_include' => array('include')
		);
		
		$zip = new ZipArchive();
		if ($zip->open($backup_file, ZIPARCHIVE::CREATE) === true)
		{
			add_dir_to_archive($zip, PUN_ROOT, PUN_ROOT, $list);
			$zip->close();
		}
		else
			message('Can\'t create zip archive.');
	}
	else
		message('Your server doesn\'t support zip archive.');
}

function add_dir_to_archive($zip, $dirpath, $basepath, $list = array())
{
	$dir = dir($dirpath);
	while ($file = $dir->read())
	{
		if ($file != '.' && $file != '..' && $file != '.svn')
		{
			$path = $dirpath.'/'.$file;
			
			if (is_dir($path) && (isset($list['dirs_include']) && in_array($file, $list['dirs_include']) || !isset($list['dirs_include'])))
				add_dir_to_archive($zip, $path, $basepath, $list);
				
			elseif (isset($list['files_exclude']) && !in_array($file, $list['files_exclude']))
				$zip->addFile($path, substr($path, strlen($basepath) + 1));
		}
	}
}

function revert($file)
{
	global $pun_config;
	
	$zip = new ZipArchive;
	if ($zip->open(BACKUPS_DIR.$file) === true)
	{
		$zip->extractTo(PUN_ROOT);
		
		if (!$zip->statName('mods.php'))
			@unlink(PUN_ROOT.'mods.php');
		
		$zip->close();
		redirect($pun_config['o_base_url'].'/patcher.php', 'Files reverted from backup.');
	} 
	else
		message('Can\'t open the backup.');
}

function export($mod, $filename)
{
	global $pun_config;
	
	if (class_exists('ZipArchive'))
	{
		$file = PATCHER_ROOT.'exports/'.$filename.'.zip';
		
		if (file_exists($file))
			@unlink($file);
		
		$zip = new ZipArchive();
		if ($zip->open($file, ZIPARCHIVE::CREATE) === true)
		{
			$list = array(
				'files_exclude' => array('Thumbs.db')
			);
			
			add_dir_to_archive($zip, MODS_DIR.$mod, MODS_DIR.$mod, $list);
			$zip->close();
			redirect($pun_config['o_base_url'].'/patcher.php', 'Exported.');
		}
		else
			message('Can\'t create zip archive.');
	}
	else
		message('Your server doesn\'t support zip archive.');
}

function upload_mod()
{
	global $pun_config;
	
	if (!is_uploaded_file($_FILES['upload_mod']['tmp_name']))
		message('File was not sent');
	
	$file = MODS_DIR.$_FILES['upload_mod']['name'];
	if (!move_uploaded_file($_FILES['upload_mod']['tmp_name'], $file))
		message('Cant move uploaded file');
		
	if (!class_exists('ZipArchive'))
		message('Your server doesn\'t support zip archive.');

	if (substr($file, -4) != '.zip')
		message('You can upload only ZIP archive');
	
	$dirname = substr($file, 0, -4);
	if (is_dir($dirname) && file_exists($dirname))
		message('Directory '.basename($dirname).' already exists');
	
	$zip = new ZipArchive;
	if ($zip->open($file) === true)
	{
		mkdir($dirname);
		$zip->extractTo($dirname);
		
		$zip->close();
		redirect($pun_config['o_base_url'].'/patcher.php?mod='.basename($dirname), 'File uploaded.');
	} 
	else
		message('Can\'t open the backup.');
}


function look_for_readme($mod, $dirpath, $basepath, $recursive = true)
{
	global $readme_files, $readme_file_names, $inst_mods;
	
	$dir = dir($dirpath);
	while ($file = $dir->read())
	{
		if (substr($file, 0, 1) != '.')
		{
			if ($recursive == true && is_dir($dirpath.'/'.$file))
				look_for_readme($mod, $dirpath.'/'.$file, $basepath, false);
		
			else if (strpos(strtolower($file), 'read') !== false && strpos(strtolower($file), 'me') !== false && strpos(strtolower($file), '.txt') !== false)
			{
				$filepath = $dirpath.'/'.$file;
				$filepath = substr($filepath, strpos($filepath, $basepath) + strlen($basepath) + 1);
				
				$span1 = $span2 = '';
				if (isset($inst_mods[$mod]) && in_array($filepath, $inst_mods[$mod]))
				{
					$span1 = '<span style="color: #2EB52A">';
					$span2 = '</span>';
				}
				
				$filepath = pun_htmlspecialchars($filepath);
				
				$readme_files[] = '<a href="patcher.php?mod='.pun_htmlspecialchars($mod).'&file='.$filepath.'">'.$span1.$filepath.$span2.'</a>';
				$readme_file_names[] = $file;
			}
		}
	}
}
