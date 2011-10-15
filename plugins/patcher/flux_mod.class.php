<?php
/**
 * FluxBB Patcher 2.0
 * http://fluxbb.org/forums/viewtopic.php?id=4431
 */


class REPO_MOD
{
	var $id;
	var $title;
	var $version;
	var $repository_url;
	var $is_valid;
	var $description;

	function __construct($id, $cur_mod)
	{
		$this->id = $id;
		$this->title = $cur_mod['name'];
		$this->repository_url = 'http://fluxbb.org/resources/mods/'.urldecode($this->id).'/';
		$this->is_valid = true;
		$this->version = $cur_mod['last_release']['version'];
		if (isset($cur_mod['description']))
			$this->description = $cur_mod['description'];
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
		return $this->$name;
	}


	function __isset($name)
	{
		$value = $this->$name;
		return isset($value) && !empty($value);
	}


	// Looks for readme file
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


	// Gets the readme file list for specified directory
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


	// Returns array with mod information
	function get_mod_info()
	{
		$file = $this->readme_file;

		if (!isset($this->readme_file) || empty($this->readme_file))
			return array();

		$mod_info = array();

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

		$author = $this->mod_info['author'];
		if (preg_match('#^(.*?) \(([^@]+@[^@]+\.[^@]+)\)#', $author, $m) // name (test@gmail.com)
			|| preg_match('#^([^@]+)@([^@]+\.[^@]+)#', $author, $m)) // test@gmail.com
			$author = $m[1];

		if (strpos($author, ';') !== false)
			$author = substr($author, 0, strpos($author, ';'));

		if (strpos($author, ' - ') !== false)
			$author = substr($author, 0, strpos($author, ' - '));

		return trim($author);
	}

	function get_author_email()
	{
		if (!isset($this->mod_info['author']))
			return '';

		if (preg_match('#\(([^@]+@[^@]+\.[^@]+)\)#', $this->mod_info['author'], $m) // name (test@gmail.com)
			|| preg_match('#([^@]+@[^@]+\.[^@]+)#', $this->mod_info['author'], $m)) // test@gmail.com
			return trim($m[1]);
	}

	function get_title()
	{
		if (!isset($this->mod_info['mod title']))
			return ucfirst(str_replace(array('-', '_'), ' ', $this->id));

		return trim($this->mod_info['mod title']);
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
			// Do some fix for current file :)
			$cur_file = str_replace(array('[language]', 'your_lang'), 'English', trim($cur_file));
			$cur_file = str_replace(array('[style]', 'your_style', 'Your_style'), 'Air', $cur_file);

			// Delete everything after ( and [ charachters
			if (strpos($cur_file, ' (') !== false)
				$cur_file = substr($cur_file, 0, strpos($cur_file, ' ('));
			if (strpos($cur_file, ' [') !== false)
				$cur_file = substr($cur_file, 0, strpos($cur_file, ' ['));

			// Does not look like a file?
			if (($pos = strrpos($cur_file, '.')) === false || $pos < strlen($cur_file) - 5 || $pos >= strlen($cur_file) - 1)
				continue;

			// Exclude lines that has Null, None or No word
			if (!empty($cur_file) && !in_array(strtolower($cur_file), array('null', 'none', 'no')))
				$files[] = trim($cur_file);
		}

		if (file_exists($this->readme_file_dir.'/patcher.affected_files.php'))
			$files = array_merge($files, require($this->readme_file_dir.'/patcher.affected_files.php'));

		sort($files);
		return array_unique($files);
	}


	function is_compatible()
	{
		global $pun_config;

		if (!isset($this->works_on))
			return false;

		foreach ($this->works_on as $cur_version)
		{
			if (strpos($cur_version, '*') !== false && preg_match('/'.str_replace('\*', '*', preg_quote($cur_version)).'/', $pun_config['o_cur_version'])
				|| strpos($cur_version, 'x') !== false && preg_match('/'.str_replace('x', '*', preg_quote($cur_version)).'/', $pun_config['o_cur_version'])
				|| $cur_version == $pun_config['o_cur_version']
				|| $cur_version == substr($pun_config['o_cur_version'], 0, strlen($cur_version))
				|| substr($cur_version, 0, strlen($pun_config['o_cur_version'])) == $pun_config['o_cur_version'])
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

		// Get files to upload from mod readme
		if ($this->upload_code)
		{
			// Mod author was too lazy? :P
			if (preg_match('/(upload.+from|all).+files.+/', strtolower($this->upload_code)) || preg_match('/file.+folders?/', strtolower($this->upload_code)))
			{
				if (is_dir($this->readme_file_dir.'/files'))
					$files_to_upload = list_files_to_upload($this->readme_file_dir, 'files');
				else
					$files_to_upload = list_files_to_upload($this->readme_file_dir, '');
			}

			// We have the list of files to upload :)
			else
			{
				$lines = explode("\n", $this->upload_code);
				foreach ($lines as $line)
				{
					// Remove spaces from start or end of line
					$line = trim($line);

					if ($line == '' || strtoupper($line) == 'OR' || substr($line, 0, 2) == '# ')
						continue;

					if (strpos($line, ' (') !== false)
						$line = substr($line, 0, strpos($line, ' ('));

					// directory/filename.php to directory/filename.php
					if (preg_match('/^([a-zA-Z0-9_\-\(\)\/\.]+).*?\s+to\s+([a-zA-Z0-9_\-\(\)\/\.]+)/', $line, $parts))
					{
						$from = $parts[1];
						$to = $parts[2];
					}

					// Only file name
					elseif (preg_match('/^([a-zA-Z0-9_\-\(\)\/\.]+).*/', $line, $parts))
						$from = $to = $parts[1];

					// Evertying else :)
					else
						$from = $to = $line;

					// Some mod uses your_forum_folder or your_forum_file prefix for path
					$to = str_replace(array('/your_forum_folder', '/your_forum_file'), '', $to);

					// We can't the $to variable so it should be / (PUN_ROOT)
					if ($to == '')
						$to = '/';

					// Why should I correct mod author mistakes? :P
					if (!file_exists($this->readme_file_dir.'/'.$from))
					{
						 // Try to find file in files directory
						if (file_exists($this->readme_file_dir.'/files/'.$from))
							$from = 'files/'.$from;

						 // maybe new_files dir?
						elseif (file_exists($this->readme_file_dir.'/new_files/'.$from))
							$from = 'new_files/'.$from;

						// maybe new_files instead of files?
						elseif (file_exists($this->readme_file_dir.'/'.str_replace('files/', 'new_files/', $from)))
							$from = str_replace('files/', 'new_files/', $from);
					}

					// If the current path is a directory, read and add its contents
					if (is_dir($this->readme_file_dir.'/'.$from))
						$files_to_upload = array_merge($files_to_upload, list_files_to_upload($this->readme_file_dir, rtrim($from, '/'), rtrim($to, '/')));
					else
						$files_to_upload[$from] = $to;
				}
			}
		}

		// Look files to upload in the files directory
		elseif (is_dir($this->readme_file_dir.'/files'))
			$files_to_upload = list_files_to_upload($this->readme_file_dir, 'files');

		foreach ($files_to_upload as $from => &$to)
		{
			// Checking that dot character exists in the path is not a good idea for determining file but I don't know better method :)
			if (is_dir(PUN_ROOT.$to) || substr($to, -1) == '/' || strpos(basename($to), '.') === false)
				$to .= (substr($to, -1) == '/' ? '' : '/').basename($from);

			// Strip slash
			$to = ltrim($to, '\\/');

			// Ignore mod installer files
			if (preg_match('/plugins\/.*?\/(mod_config|search_insert|lang\/.*\/mod_admin).php$/', $from))
				unset($files_to_upload[$from]);

			// Do not upload language files when language folder does not exist
			elseif (preg_match('#lang\/(.+?)\/#i', $to, $matches) && strtolower($matches[1]) != 'english' && !is_dir(PUN_ROOT.'lang/'.$matches[1]))
				unset($files_to_upload[$from]);

		}

		// Sort by the $from value
		ksort($files_to_upload);
		return $files_to_upload;
	}


	function check_requirements()
	{
		global $lang_admin_plugin_patcher, $fs;

		$dirs_to_check = array();
		$requirements = array('files_to_upload' => array(), 'directories' => array(), 'affected_files' => array());

		if ($GLOBALS['action'] == 'uninstall')
		{
			foreach ($this->files_to_upload as $from => $to)
			{
				$dir = dirname($to);
				$requirements['directories'][$dir] = array($fs->is_writable(PUN_ROOT.$dir), $lang_admin_plugin_patcher['Found, writable'], $lang_admin_plugin_patcher['Not writable']);
			}
		}
		elseif (in_array($GLOBALS['action'], array('update', 'install')))
		{
			foreach ($this->files_to_upload as $from => $to)
			{
				if (!file_exists($this->readme_file_dir.'/'.$from))
					$requirements['files_to_upload'][$from] = array(false, '', $lang_admin_plugin_patcher['Not exists']);

				$cur_dir = $to;
				// Checking that dot character exists in the path is not a good idea for determining file but I don't know better method :)
				if (strpos($to, '.') !== false)
					$cur_dir = dirname($cur_dir);

				// Add directory if it was not added ealier
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

						// Attempt to create directory
						if (!is_dir(PUN_ROOT.$cur_path))
							$requirements['directories'][$cur_path] = array(@$fs->mkdir(PUN_ROOT.$cur_path), $lang_admin_plugin_patcher['Created'], $lang_admin_plugin_patcher['Can\'t create']);
					}

					if (!isset($requirements['directories'][$cur_dir_to_check]))
						$requirements['directories'][$cur_dir_to_check] = array($fs->is_writable(PUN_ROOT.$cur_dir_to_check), $lang_admin_plugin_patcher['Found, writable'], $lang_admin_plugin_patcher['Not writable']);
				}

				// Check that directory is writable
				else
					$requirements['directories'][$cur_dir_to_check] = array($fs->is_writable(PUN_ROOT.$cur_dir_to_check), $lang_admin_plugin_patcher['Found, writable'], $lang_admin_plugin_patcher['Not writable']);
			}
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
				elseif (!$fs->is_writable(PUN_ROOT.$cur_file))
					$error = $lang_admin_plugin_patcher['Not writable'];

				$requirements['affected_files'][$cur_file] = array(empty($error), $lang_admin_plugin_patcher['Found, writable'], $error);
			}
		}

		// Check if there exist any requirement that fails
		foreach ($requirements as &$cur_requirements)
		{
			ksort($cur_requirements);
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

		while (($pos = strpos($readme, '#--')) !== false)
		{
			$readme = substr($readme, $pos + 3);

			// We've reached end of file
			if (trim($readme) == '')
				break;

			if (($pos = strpos($readme, '#--')) !== false)
				$cur_step = substr($readme, 0, $pos);

			$cur_step = substr($cur_step, strpos($cur_step, '[') + 1);
//			$cur_step = substr($cur_step, strpos($cur_step, '.') + 1); // +1 = dot
			$cur_command = substr($cur_step, 0, strpos($cur_step, ']') - 1);

			$cur_info = null;
			if (($pos = strpos($cur_command, '(')) !== false)
			{
				$cur_info = substr($cur_command, $pos + 1);
				$cur_info = substr($cur_info, 0, strpos($cur_info, ')'));
				$cur_command = substr($cur_command, 0, strpos($cur_command, '('));
			}

			if (($pos = strpos($cur_command, '.')) !== false)
				$cur_command = substr($cur_command, $pos + 1);

			$cur_command = trim(preg_replace('#[^A-Z\s]#', '', strtoupper($cur_command)));

			if (empty($cur_command))
				continue;

			// REPLACE WITH command for example
			if (strpos($cur_command, 'REPLACE') !== false)
				$cur_command = 'REPLACE';

			$command_transformations = array(
				'AFTER ADD'					=> array('ADD AFTER', 'AFTER INSERT'),
				'BEFORE ADD'				=> array('ADD BEFORE'),
				'OPEN'						=> array('OPEN FILE'),
				'FIND'						=> array('FIND LINE', 'SEARCH', 'GO TO LINE'),
				'AT THE END OF FILE ADD'	=> array('ADD AT THE BOTTOM OF THE FILE', 'ADD AT THE BOTTOM OF THE FUNCTION', 'AT THE END ADD'),
				'IN THIS LINE FIND' 		=> array('IN THE SAME LINE FIND', 'IN THESE LINES FIND', 'EVER IN THESE LINES FIND'),
				'NOTE'						=> array('VISIT', 'NOTES'),
				'UPLOAD'					=> array('UPLOAD THE CONTENT OF', 'SEND ON THE SERVER TO THE ROOT OF THE FORUM'),
				'RUN'						=> array('LAUNCH'),
			);
			foreach ($command_transformations as $new_command => $commands_to_fix)
			{
				if (in_array($cur_command, $commands_to_fix))
				{
					$cur_command = $new_command;
					break;
				}
			}

			if (!$do_inline_find && $cur_command == 'IN THIS LINE FIND')
				$do_inline_find = true;

			// We don't want SAVE and END commands
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

		// Support for mod installer
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
				if (substr($f, 0, 1) == '.')
					continue;

				// Mod installer
				if (is_dir($plugins_dir.'/'.$f) && file_exists($plugins_dir.'/'.$f.'/search_insert.php'))
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
						$steps[] = array('action' => 'RUN CODE', 'code' => 'if ($this->install)'."\n{\n".implode("\n", $code_array)."\n}\n");
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
				// Mod installer
				if (is_dir($plugins_dir.'/'.$f) && file_exists($plugins_dir.'/'.$f.'/update_install.php'))
				{
					$code = 'if ($this->install)'."\n{\n?>".file_get_contents($plugins_dir.'/'.$f.'/update_install.php')."<?php\n".'}';
					if (file_exists($plugins_dir.'/'.$f.'/update_uninstall.php'))
						$code .= "\n\n".'if ($this->uninstall)'."\n{\n?>".file_get_contents($plugins_dir.'/'.$f.'/update_uninstall.php')."<?php\n".'}';

					$code = str_replace('?><?php', '', $code);
					$steps[] = array('command' => 'RUN CODE', 'code' => $code);
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