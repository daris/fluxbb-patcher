<?php
/**
 * FluxBB Patcher 2.0
 * http://fluxbb.org/forums/viewtopic.php?id=4431
 */


class REPO_MOD
{
	function __get($name)
	{
		return null;
	}
	
	function __set($name, $value)
	{
		$this->$name = $value;
	}
}


class FLUX_MOD
{
	var $id = null; // mod directory
	var $readme_file_dir = null; // main readme file name
//	var $readme_file_name = null; // main readme file dir
	var $mod_dir = null; // main readme file dir
	private $readme_file = null; // main readme file content
//	var $readme_file_list = null; // list of readme files in current mod directory (including subdirectory)
	
	function __construct($mod_id)
	{
		$this->id = $mod_id;
		$this->mod_dir = MODS_DIR.$this->id.'/';
		if (!is_dir($this->mod_dir) || !isset($this->readme_file_name))
		{
			$this->is_valid = false;
			return false;
		}
		$this->readme_file_dir = $this->mod_dir.dirname($this->readme_file_name);
		$this->readme_file = file_get_contents($this->mod_dir.$this->readme_file_name);
	}
	
	
	// Used for: readme_file_list, files_to_upload, upload_code
	function __get($name)
	{
		$function_name = 'get_'.$name;
		if (!is_callable(array($this, $function_name)))
			return null;

		$this->$name = $this->$function_name();
		
		$config_file = null;
		if (file_exists($this->readme_file_dir.'/patcher.config.php'))
			$config_file = $this->readme_file_dir.'/patcher.config.php';
		elseif (file_exists($this->readme_file_dir.'/files/patcher.config.php'))
			$config_file = $this->readme_file_dir.'/files/patcher.config.php';
		
		if (isset($config_file))
		{
			$config_type = '__get';
			require $config_file;
		}
		
		return $this->$name;
	}
	

	function __isset($name)
	{
		$value = $this->$name;
		return isset($value) && !empty($value);
	}
	

	function get_readme_file_name()
	{
		if (file_exists(MODS_DIR.$this->id.'/readme.txt'))
			return 'readme.txt';

		if (count($this->readme_file_list) == 1)
			return $this->readme_file_list[0];

		foreach ($this->readme_file_list as $key => $cur_readme)
		{
			if (preg_match('#(install|read\s?me).*?\.txt#i', $cur_readme))
				return $cur_readme;
		}

		return false;
	}
	

	function get_readme_file_list($dirpath = '', $subdirectory = true)
	{
		// Load readme file list
		if ($dirpath == '')
			$dirpath = $this->mod_dir;
		
		$result = array();
		$dir = dir($dirpath);
		while ($file = $dir->read())
		{
			if (substr($file, 0, 1) != '.')
			{
				if (is_dir($dirpath.'/'.$file))
				{
					if ($subdirectory)
						$result = array_merge($result, $this->get_readme_file_list($dirpath.'/'.$file, false));
				}
				else if (strpos(strtolower($file), 'read') !== false && strpos(strtolower($file), 'me') !== false && strpos(strtolower($file), '.txt') !== false)
					$result[] = ltrim(str_replace($this->mod_dir, '', $dirpath.'/'.$file), '/');
			}
		}
		$dir->close();

		return $result;
	}
	
	
	function get_mod_info()
	{
		$file = $this->readme_file;
		
		if (!isset($this->readme_file) || empty($this->readme_file))
			return array();
		
		$file = substr($file, 0, strpos($file, '#--'));
		$file = trim($file, '# '."\n\r\t");
		
		// Gizzmo's syntax - strip out *****
		$file = preg_replace('#\*{5,}#', '', $file);

		$lines = explode("\n", $file);
		$transformations = array(
			'title'				=> 'mod title',
			'version mod'		=> 'mod version',
			'version'			=> 'mod version',
			'affected file'		=> 'affected files',
			'works on'			=> 'works on fluxbb',
			'works on punbb'	=> 'works on fluxbb', // this should not be here :)
		);
		$last_info = '';
		foreach ($lines as $line)
		{
			$line = ltrim(trim($line), '#*');
	/*		if ($line == '')
				continue;
	*/
			if (strpos(substr($line, 0, 25), ':') !== false)
			{
				$last_info = trim(strtolower(substr($line, 0, strpos($line, ':'))));
				if (isset($transformations[$last_info]))
					$last_info = $transformations[$last_info];
				$mod_info[$last_info] = trim(substr($line, strpos($line, ':') + 1));
			}
			elseif ($last_info != '')
				$mod_info[$last_info] .= "\n".trim($line);
		}
		
		$this->is_valid = isset($mod_info['mod version']);
		
		return $mod_info;
	}

	
	function get_is_valid()
	{
		return isset($this->version);
	}

	function get_author()
	{
		if (!isset($this->mod_info['author']))
			return '';
	
		$author = '';
		if (preg_match('#(.*?) \(([^@]+@[^@]+\.[^@]+)\)#', $this->mod_info['author'], $m) // name (test@gmail.com)
			|| preg_match('#([^@]+)@([^@]+\.[^@]+)#', $this->mod_info['author'], $m)) // test@gmail.com
			$author = $m[1];
		
		if (strpos($author, ';') !== false)
			return substr($author, 0, strpos($author, ';'));
	}
	
	function get_author_email()
	{
		if (!isset($this->mod_info['author']))
			return '';
		
		if (preg_match('#\(([^@]+@[^@]+\.[^@]+)\)#', $this->mod_info['author'], $m) // name (test@gmail.com)
			|| preg_match('#([^@]+@[^@]+\.[^@]+)#', $this->mod_info['author'], $m)) // test@gmail.com
			return $m[1];
	}
	
	function get_title()
	{
		if (!isset($this->mod_info['mod title']))
			return ucfirst(str_replace(array('-', '_'), ' ', $this->id));
		
		return $this->mod_info['mod title'];
	}

	function get_version()
	{
		if (!isset($this->mod_info['mod version']))
			return '';
		
		return $this->mod_info['mod version'];
	}
	
	function get_description()
	{
		if (!isset($this->mod_info['description']))
			return '';
		
		return $this->mod_info['description'];
	}
	
	function get_affects_db()
	{
		if (!isset($this->mod_info['affects db']))
			return '';
		
		return $this->mod_info['affects db'];
	}

	function get_important()
	{
		if (!isset($this->mod_info['important']))
			return '';
		
		return $this->mod_info['important'];
	}
	
	function get_release_date()
	{
		if (!isset($this->mod_info['release date']))
			return '';
		
		return $this->mod_info['release date'];
	}

	function get_works_on()
	{
		if (!isset($this->mod_info['works on fluxbb']))
			return '';
		
		$this->mod_info['works on fluxbb'] = str_replace(' and ', ', ', $this->mod_info['works on fluxbb']);
		return array_map('trim', explode(',', $this->mod_info['works on fluxbb']));
	}
	
	function get_repository_url()
	{
		if (!isset($this->mod_info['repository url']) || strpos($this->mod_info['repository url'], '(Leave unedited)') !== false)
			return '';
		
		return $this->mod_info['repository url'];
	}

	function get_affected_files()
	{
		if (!isset($this->mod_info['affected files']))
			return '';
		
		$files = array();
		$delimiter = (strpos($this->mod_info['affected files'], ', ') !== false) ? ',' : "\n";
		$affected_files = explode($delimiter, $this->mod_info['affected files']);
		foreach ($affected_files as $cur_file)
		{
			$cur_file = str_replace(array('[language]', 'your_lang'), 'English', trim($cur_file));
			$cur_file = str_replace(array('[style]', 'your_style', 'Your_style'), 'Air', $cur_file);
			if (strpos($cur_file, ' (') !== false)
				$cur_file = substr($cur_file, 0, strpos($cur_file, ' ('));
			if (strpos($cur_file, ' [') !== false)
				$cur_file = substr($cur_file, 0, strpos($cur_file, ' ['));
			
			if (!empty($cur_file) && !in_array($cur_file, array('Null', 'None')))
				$files[] = trim($cur_file);
		}
		
		sort($files);
		
		return $files;
	}

	
	function is_compatible()
	{
		global $pun_config;

		if (!isset($this->works_on))
			return false;

		foreach ($this->works_on as $cur_version)
		{
			if (strpos($cur_version, '*') !== false)
			{
				if (preg_match('/'.str_replace('\*', '*', preg_quote($cur_version)).'/', $pun_config['o_cur_version']))
					return true;
			}
			elseif (strpos($cur_version, 'x') !== false)
			{
				if (preg_match('/'.str_replace('x', '*', preg_quote($cur_version)).'/', $pun_config['o_cur_version']))
					return true;
			}
			elseif ($cur_version == $pun_config['o_cur_version'])
				return true;
	
			elseif ($cur_version == substr($pun_config['o_cur_version'], 0, strlen($cur_version)))
				return true;
			
			elseif (preg_match('#([>=<]+)\s*(.*)#', $this->works_on[0], $matches))
				return version_compare($pun_config['o_cur_version'], $matches[2], $matches[1]);
		}

		return false;
	}

	
	function get_upload_code()
	{
		if (strpos($this->readme_file, 'UPLOAD ]--') === false)
			return false;
	
		$upload_code = substr($this->readme_file, strpos($this->readme_file, 'UPLOAD ]--'));
		
		// Mpok's style (first line - English, second - translation)
		if (preg_match('/\]-+\s*\n#-+\[/si', $upload_code))
			$upload_code = preg_replace('/(\]-+\r?\n)#-+.*?\n/si', '$1', $upload_code, 1);
		
		$upload_code = substr($upload_code, strpos($upload_code, "\n") + 1);
		$upload_code = substr($upload_code, 0, strpos($upload_code, '#--'));
		return trim($upload_code, '#'."\n\r");
	}
	

	function get_files_to_upload()
	{
		$files_to_upload = array();

		if ($this->upload_code)
		{
			if (preg_match('/(upload.+from|all).+files.+/', strtolower($this->upload_code)) || preg_match('/file.+folders?/', strtolower($this->upload_code)))
			{
				if (is_dir($this->readme_file_dir.'/files'))
					$files_to_upload = list_files_to_upload($this->readme_file_dir, 'files');
				else
					$files_to_upload = list_files_to_upload($this->readme_file_dir, '');
			}
			else
			{
				$lines = explode("\n", $this->upload_code);
				foreach ($lines as $line)
				{
					$line = trim($line);
					
					if ($line == '' || strtoupper($line) == 'OR' || substr($line, 0, 2) == '# ')
						continue;
						
					if (strpos($line, ' (') !== false)
						$line = substr($line, 0, strpos($line, ' ('));

					if (preg_match('/^([a-zA-Z0-9_\-\(\)\/\.]+).*?\s+to\s+([a-zA-Z0-9_\-\(\)\/\.]+)/', $line, $parts))
					{
						$from = $parts[1];
						$to = $parts[2];
					}
					elseif (preg_match('/^([a-zA-Z0-9_\-\(\)\/\.]+).*/', $line, $parts))
						$from = $to = $parts[1];
					else
						$from = $to = $line;
					
					$to = str_replace(array('/your_forum_folder', '/your_forum_file'), '', $to);

					if ($to == '')
						$to = '/';
					
					// Why should I correct mod author mistakes? :P
					if (!file_exists($this->readme_file_dir.'/'.$from))
					{
						if (file_exists($this->readme_file_dir.'/files/'.$from)) // Try to find it in files directory
							$from = 'files/'.$from;
						elseif (file_exists($this->readme_file_dir.'/new_files/'.$from)) // maybe new_files dir?
							$from = 'new_files/'.$from;
						elseif (file_exists($this->readme_file_dir.'/'.str_replace('files/', 'new_files/', $from))) // maybe new_files instead of files?
							$from = str_replace('files/', 'new_files/', $from);
					}
					
					if (is_dir($this->readme_file_dir.'/'.$from))
						$files_to_upload = array_merge($files_to_upload, list_files_to_upload($this->readme_file_dir, rtrim($from, '/'), rtrim($to, '/')));
					else
						$files_to_upload[$from] = $to;
				}
			}
		}
		elseif (is_dir($this->readme_file_dir.'/files'))
			$files_to_upload = list_files_to_upload($this->readme_file_dir, 'files');

		// Ignore mod installer files
		foreach ($files_to_upload as $from => $to)
		{
			if (preg_match('/plugins\/.*?\/(mod_config|search_insert|lang\/.*\/mod_admin).php$/', $from))
				unset($files_to_upload[$from]);

			// Do not upload language files when language folder does not exist
			elseif (preg_match('#lang\/(.+?)\/#i', $to, $matches) && strtolower($matches[1]) != 'english' && !is_dir(PUN_ROOT.'lang/'.$matches[1]))
				unset($files_to_upload[$from]);
		}

		ksort($files_to_upload);
		return $files_to_upload;
	}

	
	function check_requirements()
	{
		global $lang_admin_plugin_patcher;
		
		$dirs_to_check = array();
		$requirements = array('files_to_upload' => array(), 'directories' => array(), 'affected_files' => array());
		
		foreach ($this->files_to_upload as $from => $to)
		{
			if (!file_exists($this->readme_file_dir.'/'.$from))
				$requirements['files_to_upload'][$from] = array(false, '', $lang_admin_plugin_patcher['Not exists']);
			
			$cur_dir = $to;
			if (strpos($to, '.') !== false) // it is not good way to determine file but works :)
				$cur_dir = dirname($cur_dir);

			if (!in_array($cur_dir, $dirs_to_check))
				$dirs_to_check[] = $cur_dir;
		}
		sort($dirs_to_check);
		foreach ($dirs_to_check as $cur_dir_to_check)
		{
			if (!is_dir(PUN_ROOT.$cur_dir_to_check))
			{
				$directories = explode('/', $cur_dir_to_check);
				$cur_path = '';
				foreach ($directories as $cur_dir)
				{
					$cur_path .= $cur_dir.'/';
					
					if (!is_dir(PUN_ROOT.$cur_path))
						$requirements['directories'][$cur_path] = array(mkdir(PUN_ROOT.$cur_path), $lang_admin_plugin_patcher['Created'], $lang_admin_plugin_patcher['Can\'t create']);
				}
				
				if (!isset($requirements['directories'][$cur_dir_to_check]))
					$requirements['directories'][$cur_dir_to_check] = array(is_writable(PUN_ROOT.$cur_dir_to_check), $lang_admin_plugin_patcher['Found, writable'], $lang_admin_plugin_patcher['Not writable']);
			}
			else
				$requirements['directories'][$cur_dir_to_check] = array(is_writable(PUN_ROOT.$cur_dir_to_check), $lang_admin_plugin_patcher['Found, writable'], $lang_admin_plugin_patcher['Not writable']);
		}

		if (count($this->affected_files) > 0)
		{
			foreach ($this->affected_files as $cur_file)
			{
				// Language file that is not English does not exist?
				if (!file_exists(PUN_ROOT.$cur_file) && strpos(strtolower($cur_file), 'lang/') !== false && strpos(strtolower($cur_file), '/english') === false)
					continue;

				$error = '';
				if (!file_exists(PUN_ROOT.$cur_file))
					$error = $lang_admin_plugin_patcher['Not exists'];
				elseif (!is_writable(PUN_ROOT.$cur_file))
					$error = $lang_admin_plugin_patcher['Not writable'];
	
				$requirements['affected_files'][$cur_file] = array(empty($error), $lang_admin_plugin_patcher['Found, writable'], $error);
			}
		}
		
		// Check if there exist any step that fails
		foreach ($requirements as $cur_requirements)
		{
			foreach ($cur_requirements as $cur_requirement)
			{
				if (!$cur_requirement[0])
				{
					$requirements['failed'] = true;
					break;
				}
			}
			
			if (isset($requirements['failed']))
				break;
		}
		
		return $requirements;
	}

	
	function get_steps($readme_file = null)
	{
		if ($readme_file == null)
			$readme = $this->readme_file;
		else
			$readme = file_get_contents(MODS_DIR.$this->id.'/'.$readme_file);

		$readme = substr($readme, strpos($readme, '#--'));
		
		// Mpok's style (first line - English, second - translation)
		if (preg_match('/\]-+\s*\n#-+\[/si', $readme))
			$readme = preg_replace('/(\]-+\r?\n)#-+.*?\n/si', '$1', $readme);
			
		// Convert EOL to Unix style
		$readme = str_replace("\r\n", "\n", $readme);
		
		$readme .= '#--';
		$do_inline_find = false;
		
		$steps = array();
		
		while (strpos($readme, '#--') !== false)
		{
			$readme = substr($readme, strpos($readme, '#--') + 3);
			
			// End of file
			if (trim($readme) == '')
				break;

			if (strpos($readme, '#--') !== false)
				$cur_step = substr($readme, 0, strpos($readme, '#--'));
				
			$cur_step = substr($cur_step, strpos($cur_step, '[') + 1);
	//		$cur_step = substr($cur_step, strpos($cur_step, '.') + 1); // +1 = dot
			$cur_command = substr($cur_step, 0, strpos($cur_step, ']') - 1);
			
			$cur_info = null;
			if (strpos($cur_command, '(') !== false)
			{
				$cur_info = substr($cur_command, strpos($cur_command, '(') + 1);
				$cur_info = substr($cur_info, 0, strpos($cur_info, ')'));
				$cur_command = substr($cur_command, 0, strpos($cur_command, '('));
			}

			if (strpos($cur_command, '.') !== false)
				$cur_command = substr($cur_command, strpos($cur_command, '.') + 1);
				
			$cur_command = trim(preg_replace('#[^A-Z\s]#', '', strtoupper($cur_command)));
			
			if (empty($cur_command))
				continue;

			if (strpos($cur_command, 'REPLACE') !== false)
				$cur_command = 'REPLACE';
			
			$correct_action = array(
				'ADD AFTER'		=> 'AFTER ADD',
				'AFTER INSERT'	=> 'AFTER ADD',
				'ADD BEFORE'	=> 'BEFORE ADD',
				'OPEN FILE'		=> 'OPEN',
				'FIND LINE'		=> 'FIND',
				'SEARCH'		=> 'FIND',
				'ADD AT THE BOTTOM OF THE FILE' => 'AT THE END OF FILE ADD',
				'ADD AT THE BOTTOM OF THE FUNCTION' => 'AT THE END OF FILE ADD',
				'AT THE END ADD' => 'AT THE END OF FILE ADD',
				'IN THE SAME LINE FIND' => 'IN THIS LINE FIND',
				'IN THESE LINES FIND'	=> 'IN THIS LINE FIND',
				'GO TO LINE'	=> 'FIND',
				'VISIT'			=> 'NOTE',
				'NOTES'			=> 'NOTE',
				'UPLOAD THE CONTENT OF' => 'UPLOAD',
			);
			foreach ($correct_action as $before => $after)
			{
				if ($cur_command == $before)
				{
					$cur_command = $after;
					break;
				}
			}
			
			if (!$do_inline_find && $cur_command == 'IN THIS LINE FIND')
				$do_inline_find = true;
			
			if (strpos($cur_command, 'SAVE') !== false || $cur_command == 'END')
				continue;

			$cur_code = substr($cur_step, strpos($cur_step, "\n") + 1);
			
			// Gizzmo's syntax - strip out ***** at end
			$cur_code = preg_replace('#\*{5,}$#', '', $cur_code);
			
			// Remove blank string after # at start and at end
			$cur_code = preg_replace('#^\#[ \r\t]*#', '', $cur_code);
			$cur_code = preg_replace('#\s*\#\s*$#s', '', $cur_code);

			// Empty lines at start and at end
			$cur_code = preg_replace('#^\n*[ \t]*\n+#', '', $cur_code);
			$cur_code = preg_replace('#\n+[ \t]*\n*$#', '', $cur_code);

			if ($cur_command == 'OPEN')
			{
				$cur_code = str_replace(array('[language]', 'your_language'), 'English', $cur_code);
				$cur_code = str_replace(array('[style]', 'Your_style'), 'Air.css', $cur_code);
				$cur_code = ltrim(trim($cur_code), '/');
				
				if (!file_exists(PUN_ROOT.$cur_code) && preg_match('#[a-zA-Z0-9-_\/\\\\]+\.php#i', $cur_code, $matches) && file_exists(PUN_ROOT.$matches[0]))
					$cur_code = $matches[0];
			}
			elseif ($cur_command == 'NOTE')
			{
				if (strpos(strtolower($cur_code), 'launch mod installer') !== false)
					continue;

				if (isset($cur_info) && strpos($cur_info, 'server') !== false && isset($_SERVER['SERVER_SOFTWARE']))
				{
					$server_soft = $_SERVER['SERVER_SOFTWARE'];
					if (strpos($server_soft, '/') !== false)
						$server_soft = substr($server_soft, 0, strpos($server_soft, '/'));
					if (strpos(strtolower($cur_info), 'for '.strtolower($server_soft).' server') === false)
						continue;
				}
			}
			
			$new_step = array('command' => $cur_command);
			if ($cur_command == 'NOTE')
				$new_step['result'] = $cur_code;
			else
				$new_step['code'] = $cur_code;
			
			if (isset($cur_info))
				$new_step['info'] = $cur_info;
			$steps[] = $new_step;
		}
		
		$plugins_dir = null;
		if (is_dir($this->readme_file_dir.'/plugins/'))
			$plugins_dir = $this->readme_file_dir.'/plugins/';
		elseif (is_dir($this->readme_file_dir.'/files/plugins/'))
			$plugins_dir = $this->readme_file_dir.'/files/plugins/';

		if (isset($plugins_dir))
		{
			$d = dir($plugins_dir);
			while ($f = $d->read())
			{
				// Mod installer
				if ($f{0} != '.' && is_dir($plugins_dir.'/'.$f) && file_exists($plugins_dir.'/'.$f.'/search_insert.php'))
				{
					require $plugins_dir.'/'.$f.'/search_insert.php';
					$list_files = array();
					$list_base = array();
					// Do not modify the order below, otherwise some mods cannot be installed
					// 1st files_to_insert - 2nd files_to_add - 3rd files_to_replace - 4th files_to_move
					if(isset($files_to_insert)) $list_files[] = "files_to_insert";
					if(isset($files_to_add)) $list_files[] = "files_to_add";
					if(isset($files_to_replace)) $list_files[] = "files_to_replace";
					if(isset($files_to_move)) {
						$list_files[] = "files_to_move";
						$move_start = "//modif oto - mod "/*.$mod_config['mod_name']*/." - Beginning of the block moved\n";
						$move_end = "//modif oto - mod "/*.$mod_config['mod_name']*/." - End of the block moved\n";
					}
					//Database to modify
					if(isset($fields_to_add)) $list_tables[] = "fields_to_add";
					if(isset($config_to_insert)) $list_tables[] = "config_to_insert";
					
					//is there database modifications to do?
					if(!empty($list_tables))
					{
						$code_array = array();
						global $db;

						foreach($list_tables as $base_name)
						{
							foreach($$base_name as $table_value)
							{//$table_value is name of table for modifications
								if($base_name == "fields_to_add")
								{
									for($i=0;$i<count($add_field_name[$table_value]);$i++)
									{
										//If the field already exist there is no error.
										$code_array[] = '$db->add_field(\''.$table_value.'\', \''.$add_field_name[$table_value][$i].'\', \''.$add_field_type[$table_value][$i].'\', \''.$add_allow_null[$table_value][$i].'\', \''.$add_default_value[$table_value][$i].'\') or error(\'Unable to add column '.$add_field_name[$table_value][$i].' to table '.$table_value.'\', __FILE__, __LINE__, $db->error());';
									}
								}
								elseif($base_name == "config_to_insert")
								{
									$sql = "REPLACE INTO `".$db->prefix.$table_value."` (`conf_name`, `conf_value`) VALUES ";
									for($i = 0;$i < count($values[$table_value]);$i = $i + 2) {
										$sql .= "(\'".$db->escape($values[$table_value][$i])."\', \'".$db->escape($values[$table_value][$i+1])."\'),";
										}
									$sql = substr($sql,0,-1);
									$code_array[] = '$db->query(\''.$sql.'\') or error(\'Unable to INSERT values INTO '.$table_value.'\', __FILE__, __LINE__, $db->error());';
								}
							}
						}
						$steps[] = array('action' => 'RUN CODE', 'code' => implode("\n", $code_array));
					}

					foreach($list_files as $file_name) 
					{
						foreach($$file_name as $file_value)
						{
							$steps[] = array('command' => 'OPEN', 'code' => $file_value);
							
							list($name_file,$ext_file) = explode('.',$file_value);

							if($file_name == "files_to_insert")
							{
								//Inserting the code before an existing line.
								for($i=0;$i<count($insert_file[$name_file]);$i++)
								{
									$steps[] = array('command' => 'FIND', 'code' => $search_file[$name_file][$i]);
									$steps[] = array('command' => 'BEFORE ADD', 'code' => $insert_file[$name_file][$i]);
								}
							}
							elseif($file_name == "files_to_add")
							{
								//Adding the code after an existing line.
								for($i=0;$i<count($insert_add_file[$name_file]);$i++)
								{
									$steps[] = array('command' => 'FIND', 'code' => $search_add_file[$name_file][$i]);
									$steps[] = array('command' => 'AFTER ADD', 'code' => $insert_add_file[$name_file][$i]);
								}
							}
							elseif($file_name == "files_to_replace")
							{
								//Replacing an existing code by another one.
								for($i=0;$i<count($insert_replace_file[$name_file]);$i++)
								{
									$steps[] = array('command' => 'FIND', 'code' => $search_replace_file[$name_file][$i]);
									$steps[] = array('command' => 'REPLACE', 'code' => $insert_replace_file[$name_file][$i]);
								}
							}
							// currently unsupported
	/*						elseif($file_name == "files_to_move")
							{
								// Move code between two lines to another location
								for($i=0;$i<count($move_get_start[$name_file]);$i++) {
									$pos_start = strpos($file_content, $move_get_start[$name_file][$i]) + strlen($move_get_start[$name_file][$i]);
								$pos_end = strpos($file_content, $move_get_end[$name_file][$i]);
									$move_string = substr($file_content, $pos_start, $pos_end - $pos_start);
						
									$searching[] = $move_get_start[$name_file][$i].$move_string.$move_get_end[$name_file][$i];
									$replacement[] = $move_get_start[$name_file][$i].$move_get_end[$name_file][$i];
									$searching[] = $move_to_start[$name_file][$i].$move_to_end[$name_file][$i];
									$replacement[] = $move_to_start[$name_file][$i].$move_start.$move_string.$move_end.$move_to_end[$name_file][$i];
								}
							}*/
						}
					}
				}
			}
		}
		
		// Correct action IN THIS LINE FIND
		if ($do_inline_find)
		{
			$find = $replace = $inline_find = $inline_replace = '';
			$last_find_key = 0;
			$modified = false;
			foreach ($steps as $key => $cur_step)
			{
				if ($cur_step['command'] == 'OPEN')
					$inline_find = '';
				elseif ($cur_step['command'] == 'FIND')
				{
					if ($inline_replace != '')
						$steps[$last_find_key + 1] = array('command' => 'REPLACE', 'code' => $inline_replace);
						
					$find = $cur_step['code'];
					$inline_find = $inline_replace = '';
					$last_find_key = $key;
				}
				elseif ($cur_step['command'] == 'IN THIS LINE FIND')
				{
					if ($inline_replace == '')
						$inline_replace = $find;
					else
						unset($steps[$key]);

					$inline_find = trim($cur_step['code'], "\t");
				}
				elseif ($cur_step['command'] == 'AFTER ADD' && $inline_find != '')
				{
					$inline_replace = str_replace($inline_find, $inline_find.trim($cur_step['code'], "\t"), $inline_replace);
					unset($steps[$key]);
				}
				elseif ($cur_step['command'] == 'REPLACE' && $inline_find != '')
					$inline_replace = str_replace($inline_find, $inline_find.$inline_replace, $inline_replace);
			}
			
			// Fix section numbering
			$steps = array_values($steps);
		}
		
		return $steps;
	}
	
	
}