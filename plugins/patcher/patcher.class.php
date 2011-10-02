<?php
/**
 * FluxBB Patcher 2.0
 * http://fluxbb.org/forums/viewtopic.php?id=4431
 */

class PATCHER
{
	var $flux_mod = null;
	
	var $config = array();
	var $config_org = array();

	var $cur_file = null; // Currently patched file
	var $cur_file_path = null; // Path to currently patched file
	var $cur_file_modified = false;
	var $find = null;
	var $start_pos = 0;
	var $global_step = 1;
	
	var $action = null;
	
	var $steps = null;
	var $log = array();
	
	function __construct($flux_mod, $action = 'install')
	{
		$this->flux_mod = $flux_mod;
		
		if (file_exists(PUN_ROOT.'patcher_config.php'))
			require PUN_ROOT.'patcher_config.php';
		else
			$patcher_config = array('installed_mods' => array(), 'steps' => array());
		$this->config = $this->config_org = $patcher_config;
		
		$this->action = $action;
		$this->steps = $this->get_steps();
	}
	
	
	function __get($name)
	{
		$function_name = 'get_'.$name;
		return $this->$name = $this->$function_name();
	}
	
	
	function get_steps()
	{
		$steps = array();
		
		if (in_array($this->action, array('install', 'update')))
		{
			// Load steps for current mod
			$steps[$this->flux_mod->id.'/'.$this->flux_mod->readme_file_name] = $this->flux_mod->get_steps();
			
			// Load steps for related mods (readme_mod_name.txt)
			foreach ($this->flux_mod->readme_file_list as $cur_readme_file)
			{
				$cur_readme_file = ltrim($cur_readme_file, '/');
				if (strpos($cur_readme_file, '_') === false)
					continue;

				$mod_key = substr($cur_readme_file, strpos($cur_readme_file, '_') + 1);
				$mod_key = substr($mod_key, 0, strpos($mod_key, '.txt'));
				$mod_key = str_replace('_', '-', $mod_key);

				if (isset($this->config['installed_mods'][$mod_key]) && (!isset($this->config['installed_mods'][$this->flux_mod->id]) || !in_array($cur_readme_file, $this->config['installed_mods'][$this->flux_mod->id])))
					$steps[$this->flux_mod->id.'/'.$cur_readme_file] = $this->flux_mod->get_steps($cur_readme_file);
			}

			foreach ($this->config['installed_mods'] as $cur_mod_id => $inst_mods_readme_files)
			{
				$flux_mod = new FLUX_MOD($cur_mod_id);
				foreach ($flux_mod->readme_file_list as $cur_readme_file)
				{
					$cur_readme_file = ltrim($cur_readme_file, '/');
		
					// skip if readme was already installed
					if (in_array($cur_readme_file, $inst_mods_readme_files))
						continue;

					$mod_key = substr($cur_readme_file, strpos($cur_readme_file, '_') + 1);
					$mod_key = substr($mod_key, 0, strpos($mod_key, '.txt'));
					$mod_key = str_replace('_', '-', $mod_key);

					if ($mod_key == $this->flux_mod->id)
						$steps[$flux_mod->id.'/'.$cur_readme_file] = $flux_mod->get_steps($cur_readme_file);
				}
			}
		}
		
		// Uninstall, disable, enable
		else
		{
			// Load cached steps
			foreach ($this->config['steps'] as $cur_readme_file => $step_list)
			{
				if (strpos($cur_readme_file, $this->flux_mod->id) !== false || strpos($cur_readme_file, str_replace('-', '_', $this->flux_mod->id)) !== false)
					$steps[$cur_readme_file] = $step_list;
			}

			if (in_array($this->action, array('uninstall', 'disable')))
			{
				// Reverse readme list
				$steps = array_reverse($steps);
				
				// Correct the order of steps
				foreach ($steps as $cur_readme_file => &$step_list)
				{
					$run_steps_start = $run_steps_end = $upload_steps_end =  array();
					foreach ($step_list as $key => $cur_step)
					{
						if (in_array($cur_step['command'], array('RUN', 'DELETE')))
						{
							$code = trim($cur_step['code']);
							
							// Uninstall mod at the end
							if ($code == 'install_mod.php')
							{
								$run_steps_end[] = $cur_step;
								unset($step_list[$key]);
							}
							
							// Other files (eg. gen.php) move to start
							else
							{
								$run_steps_start[] = $cur_step;
								unset($step_list[$key]);
							}
						}
						
						// Delete files at the end
						elseif ($cur_step['command'] == 'UPLOAD')
						{
							$upload_steps_end[] = $cur_step;
							unset($step_list[$key]);
						}
					}
					$step_list = array_merge($run_steps_start, $step_list, $run_steps_end, $upload_steps_end);
				}
			}
		}

		return $steps;
	}


	function patch()
	{
		$i = 1;
//		$_SESSION['patcher_files'] = array();
		$failed = false;
		
		if (in_array($this->action, array('uninstall', 'disable')))
		{
			foreach ($this->flux_mod->files_to_upload as $from => $to)
			{
				// Copy install mod file as we want to uninstall mod
				if ($this->action == 'uninstall' && strpos($from, 'install_mod.php') !== false)
					copy($this->flux_mod->readme_file_dir.'/'.$from, PUN_ROOT.'install_mod.php');
				elseif (strpos($from, 'gen.php') !== false) // TODO: make this relative to RUN commands
					copy($this->flux_mod->readme_file_dir.'/'.$from, PUN_ROOT.'gen.php');
				elseif ($this->action == 'disable')
					continue;
			}
		}

		foreach ($this->steps as $cur_readme_file => &$step_list)
		{
			foreach ($step_list as $key => $cur_step)
			{
				$cur_step['status'] = STATUS_UNKNOWN;
				
				$function = 'step_'.str_replace(' ', '_', strtolower($cur_step['command']));
				if (is_callable(array($this, $function)))
				{
					$this->command = $cur_step['command'];
					$this->code = $cur_step['code'];
					$this->comments = array();
					$this->result = '';
					
					// Execute current step
					$cur_step['status'] = $this->$function();
					
					// Replace STATUS_DONE with STATUS_REVERTED when uninstalling mod
					if (in_array($this->action, array('uninstall', 'disable')) && $cur_step['status'] == STATUS_DONE)
						$cur_step['status'] = STATUS_REVERTED;

					if ($this->result != '')
						$cur_step['result'] = $this->result;

					// Skip if mod is disabled and we want to uninstall it (as file changes has been already reverted)
					if ($cur_step['status'] == STATUS_NOTHING_TO_DO)
						continue;
					
					$cur_step['comments'] = $this->comments;
				}
				
				// Don't display Note message when uninstalling mod
				if (in_array($this->action, array('uninstall', 'disable')) && $cur_step['command'] == 'NOTE')
					continue;
				
				if (in_array($cur_step['command'], array('OPEN', 'RUN', 'DELETE', 'RENAME', 'UPLOAD', 'NOTE')))
				{
					$this->global_step = $i; // it is a global action
					
					if ($cur_step['command'] == 'UPLOAD')
					{
						$code = array();
						foreach ($this->flux_mod->files_to_upload as $from => $to)
							$code[] = $from.' to '.$to;
						$cur_step['substeps'][0] = array('code' => implode("\n", $code));
						unset($cur_step['code']);
					}
					
					if (!isset($this->log[$cur_readme_file]))
						$this->log[$cur_readme_file] = array();
					$this->log[$cur_readme_file][$i] = $cur_step;
				}
				else
				{
					if (!isset($this->log[$cur_readme_file][$this->global_step]['substeps']))
						$this->log[$cur_readme_file][$this->global_step]['substeps'] = array();
					
					$this->log[$cur_readme_file][$this->global_step]['substeps'][$i] = $cur_step;
				}
				
				// Delete step if it fails
				if ($cur_step['status'] == STATUS_NOT_DONE)
				{
					if (in_array($cur_step['command'], array('BEFORE ADD', 'AFTER ADD', 'REPLACE')) && $key > 0 && isset($step_list[$key-1]) && $step_list[$key-1]['command'] == 'FIND')
						unset($step_list[$key-1]);
					unset($step_list[$key]);
				}
				
				// If some step fail, make whole mod install fail
				if (!$failed && $cur_step['status'] == STATUS_NOT_DONE)
					$failed = true;
				
				if (($cur_step['status'] == STATUS_DONE || $cur_step['status'] == STATUS_REVERTED) && $cur_step['command'] != 'OPEN' && !$this->cur_file_modified)
					$this->cur_file_modified = true;

				$i++;
			}
		}
		// if some file was opened, save it
		if ($this->cur_file_path != '' && trim($this->cur_file) != '' && $this->cur_file_modified)
			$this->step_save();

		// Update patcher_config.php file
		foreach ($this->steps as $cur_readme_file => $step_list)
		{
			$cur_mod = substr($cur_readme_file, 0, strpos($cur_readme_file, '/'));
			$cur_readme = substr($cur_readme_file, strpos($cur_readme_file, '/') + 1);

			if ($this->action == 'uninstall')
			{
				if (isset($this->config['installed_mods'][$cur_mod]) && in_array($cur_readme, $this->config['installed_mods'][$cur_mod]))
					$this->config['installed_mods'][$cur_mod] = array_diff($this->config['installed_mods'][$cur_mod], array($cur_readme)); // delete an element
				
				if (isset($this->config['installed_mods'][$cur_mod]['disabled']))
					unset($this->config['installed_mods'][$cur_mod]['disabled']);
	
				if (isset($this->config['installed_mods'][$cur_mod]['version']))
					unset($this->config['installed_mods'][$cur_mod]['version']);
					
				if (empty($this->config['installed_mods'][$cur_mod]))
					unset($this->config['installed_mods'][$cur_mod]);

				unset($this->config['steps'][$cur_readme_file]);
			}
			elseif (in_array($this->action, array('install', 'update')))
			{
				if (!isset($this->config['installed_mods'][$cur_mod]))
					$this->config['installed_mods'][$cur_mod] = array();
				
				$this->config['installed_mods'][$cur_mod]['version'] = $this->flux_mod->version;

				if (!in_array($cur_readme, $this->config['installed_mods'][$cur_mod]))
					$this->config['installed_mods'][$cur_mod][] = $cur_readme;
					
				if ($this->action == 'update' && isset($this->config['installed_mods'][$cur_mod]['disabled']))
					unset($this->config['installed_mods'][$cur_mod]['disabled']);

				$this->config['steps'][$cur_readme_file] = $step_list;
			}
			elseif ($this->action == 'enable' && isset($this->config['installed_mods'][$cur_mod]['disabled']))
				unset($this->config['installed_mods'][$cur_mod]['disabled']);
			elseif ($this->action == 'disable')
				$this->config['installed_mods'][$cur_mod]['disabled'] = 1;

		}
			
		if (!defined('PATCHER_NO_SAVE') && $this->config != $this->config_org)
			file_put_contents(PUN_ROOT.'patcher_config.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'$patcher_config = '.var_export($this->config, true).';');

		return !$failed;
	}
	
	
	function check_code(&$code)
	{
		$reg = preg_quote($code, '#');
		if (preg_match('#'.$reg.'#si', $this->cur_file))
			return;
			
		// Code was not found
		// Ignore multiple tab characters
		$reg = preg_replace("#\t+#", '\t*', $reg);
		$this->comments[] = 'Tabs ignored';
		if (preg_match('#'.$reg.'#si', $this->cur_file, $matches))
		{
			$code = $matches[0];
			return;
		}
		
		// Ignore spaces
		$reg = preg_replace('#\s+#', '\s*', $reg);
		$this->comments[] = 'Spaces ignored';
		if (preg_match('#'.$reg.'#si', $this->cur_file, $matches))
		{
			$code = $matches[0];
			return;
		}
	}
	
	
	function replace_code($find, $replace)
	{
		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']))
			return STATUS_DONE;
	
		// Undo changes?
		if (in_array($this->action, array('uninstall', 'disable')))
		{
			$tmp = $find;
			$find = $replace;
			$replace = $tmp;
		}
		
//		$replace = preg_replace('#([\$\\\\]\d+)#', '\\\$1', $replace);

		$first_part = substr($this->cur_file, 0, $this->start_pos); // do not touch this
		$second_part = substr($this->cur_file, $this->start_pos); // only replace this

		// not done yet
		$second_part = str_replace_once($find, $replace, $second_part);
		$this->cur_file = $first_part.$second_part;
		
		$check_code = $replace;
		if (in_array($this->action, array('uninstall', 'disable')) && $this->command != 'REPLACE')
			$check_code = $this->code;
		
		$pos = strpos($second_part, $check_code);
		
		if ((in_array($this->action, array('uninstall', 'disable')) && $this->command != 'REPLACE' && $pos === false) // Code shouldn't be in current file
			|| $pos !== false) // done
		{
			$this->start_pos = $this->start_pos + $pos + strlen($check_code);
			return STATUS_DONE;
		}

		$this->cur_file = str_replace_once($find, $replace, $this->cur_file);
		
		$pos = strpos($this->cur_file, $check_code);
		if ((in_array($this->action, array('uninstall', 'disable')) && $this->command != 'REPLACE' && $pos === false) // Code shouldn't be in current file
			|| $pos !== false) // done
		{
	//		$this->start_pos = $pos + strlen(trim($this->code));
			return STATUS_DONE;
		}

		return STATUS_NOT_DONE;
	}

	
	function step_upload()
	{
		global $lang_admin_plugin_patcher;

		// Should never happen
		if (in_array($this->action, array('enable', 'disable')))
			return STATUS_NOTHING_TO_DO;
		
		if (in_array($this->action, array('uninstall')))
		{
			$directories = array();
			foreach ($this->flux_mod->files_to_upload as $from => $to)
			{
				if (is_dir(PUN_ROOT.$to) || substr($to, -1) == '/' || strpos(basename($to), '.') === false) // as a comment above
					$to .= (substr($to, -1) == '/' ? '' : '/').basename($from);
			
				if (file_exists(PUN_ROOT.$to))
					unlink(PUN_ROOT.$to);
				
				$cur_path = '';
				$dir_structure = explode('/', trim($to, '/'));
				foreach ($dir_structure as $cur_dir)
				{
					$cur_path .= '/'.$cur_dir;
					if (is_dir(PUN_ROOT.$cur_path) && !in_array($cur_path, $directories))
						$directories[] = $cur_path;
				}
			}
			rsort($directories);
			foreach ($directories as $cur_dir)
			{
				// Checking that current directory is empty as we can't remove directory that have some files/directories
				$is_empty = true;
				$d = dir(PUN_ROOT.$cur_dir);
				while ($f = $d->read())
				{
					if ($f != '.' && $f != '..')
					{
						$is_empty = false;
						break;
					}
				}
				$d->close();
				
				if ($is_empty)
					rmdir(PUN_ROOT.$cur_dir);
			}
			
			return STATUS_REVERTED;
		}
		
		foreach ($this->flux_mod->files_to_upload as $from => $to)
		{
			if (is_dir($this->flux_mod->readme_file_dir.'/'.$from))
				copy_dir($this->flux_mod->readme_file_dir.'/'.$from, PUN_ROOT.$to);
			else
			{
				if (is_dir(PUN_ROOT.$to) || substr($to, -1) == '/' || strpos(basename($to), '.') === false) // as a comment above
					$to .= (substr($to, -1) == '/' ? '' : '/').basename($from);
				
				if (!copy($this->flux_mod->readme_file_dir.'/'.$from, PUN_ROOT.$to))
					message(sprintf($lang_admin_plugin_patcher['Can\'t copy file'], pun_htmlspecialchars($from), pun_htmlspecialchars($to))); // TODO: move message somewhere :)
			}
		}
		return STATUS_DONE;
	}
	
	function step_open()
	{
		global $lang_admin_plugin_patcher, $friendly_url_changes;
		
		// if some file was opened, save it
		if (!empty($this->cur_file_path))
			$this->step_save();

		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

		$this->code = trim($this->code);
		
		if (!file_exists(PUN_ROOT.$this->code))
		{
			// Language file that is not English does not exist?
			if (strpos(strtolower($this->code), 'lang/') !== false && strpos(strtolower($this->code), '/english') === false)
			{
				$this->cur_file = '';
				$this->cur_file_path = '';
				return STATUS_NOTHING_TO_DO;
			}
		
			$this->cur_file = '';
			$this->cur_file_path = $this->code;
			$this->result = $lang_admin_plugin_patcher['File does not exist error'];
			return STATUS_NOT_DONE;
		}
		
		$this->cur_file_path = $this->code;
		
		if (!is_writable(PUN_ROOT.$this->code))
			message(sprintf($lang_admin_plugin_patcher['File not writable'], pun_htmlspecialchars($this->code)));
		
		$this->cur_file = file_get_contents(PUN_ROOT.$this->code);
		
		// Convert EOL to Unix style
		$this->cur_file = str_replace("\r\n", "\n", $this->cur_file);
		
		// If friendly url mod is installed revert its changes from current file (apply again while saving this file)
		if (isset($this->config['installed_mods']['friendly-url']) && !isset($this->config['installed_mods']['friendly-url']['disabled']) && file_exists(PUN_ROOT.'friendly_url_changes.php'))
		{
			require_once PUN_ROOT.'friendly_url_changes.php';
			if (isset($friendly_url_changes[$this->code]))
			{
				$search = $replace = array();
				foreach ($friendly_url_changes[$this->code] as $key => $cur_change)
				{
					$search[$key] = $cur_change[1];
					$replace[$key] = $cur_change[0];
				}
				$this->cur_file = str_replace($search, $replace, $this->cur_file);
			}
		}

		$this->start_pos = 0;
		$this->cur_file_modified = false;
		return STATUS_DONE;
	}
	
	
	function step_save()
	{
		global $friendly_url_changes;

		if ($this->cur_file_modified && !empty($this->cur_file))
		{
			// If friendly url mod is installed apply its changes again (as patcher reverted them in open step)
			if (isset($this->config['installed_mods']['friendly-url']) && !isset($this->config['installed_mods']['friendly-url']['disabled']) && file_exists(PUN_ROOT.'friendly_url_changes.php'))
			{
				require_once PUN_ROOT.'friendly_url_changes.php';
				if (isset($friendly_url_changes[$this->cur_file_path]))
				{
					if (file_exists(MODS_DIR.'friendly-url/files/gen.php'))
					{
						global $changes;
						require_once MODS_DIR.'friendly-url/files/gen.php';
						$this->cur_file = url_replace_file($this->cur_file_path, $this->cur_file);
						$friendly_url_changes[$this->cur_file_path] = $changes[$this->cur_file_path];
						file_put_contents(PUN_ROOT.'friendly_url_changes.php', '<?php'."\n\n".'// Do not delete this file as you won\'t be able to uninstall this mod'."\n\n".'$friendly_url_changes = '.var_export($friendly_url_changes, true).';');
//						print_r($changes);
					}
					else
					{
						$search = $replace = array();
						foreach ($friendly_url_changes[$this->cur_file_path] as $key => $cur_change)
						{
							$search[$key] = $cur_change[0];
							$replace[$key] = $cur_change[1];
						}
						$this->cur_file = str_replace($search, $replace, $this->cur_file);
					}
				}
			}

			if (!defined('PATCHER_NO_SAVE'))
				file_put_contents(PUN_ROOT.$this->cur_file_path, $this->cur_file);
			elseif (isset($GLOBALS['patcher_debug']['save']) && in_array($this->cur_file_path, $GLOBALS['patcher_debug']['save']))
				file_put_contents(PATCHER_ROOT.'debug/'.basename($this->cur_file_path), $this->cur_file);

//			$_SESSION['patcher_files'][$this->cur_file_path] = $this->cur_file;
			$this->cur_file = '';
			$this->cur_file_path = '';
			$this->cur_file_modified = false;
		}
	}
	
	
	function step_find()
	{
		$this->find = $this->code;

		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']) || $this->cur_file_path == '')
			return STATUS_NOTHING_TO_DO;
		
		$this->check_code($this->find);
		return STATUS_UNKNOWN;
	}
	
	
	function step_replace()
	{
		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']) || $this->cur_file_path == '')
			return STATUS_NOTHING_TO_DO;

		if (empty($this->find) || empty($this->cur_file))
			return STATUS_NOT_DONE;

		// Add QUERY ID at end of query line
		if (strpos($this->code, 'query(') !== false)
		{		
			preg_match_all('#\n\t*.*?query\(.*?\) or error.*\n#', "\n".$this->find."\n", $first_m, PREG_SET_ORDER);
			preg_match_all('#\n\t*.*?query\(.*?\) or error.*\n#', "\n".$this->code."\n", $second_m, PREG_SET_ORDER);

			foreach ($first_m as $key => $first)
			{
				$query_line = trim($first[0]);
				$replace_line = trim($second_m[$key][0]);

				$this->code = str_replace($replace_line, $replace_line.' // QUERY ID: '.md5($query_line), $this->code);
			}
		}

		$status = $this->replace_code(trim($this->find), trim($this->code));

		// has query?
		if (in_array($status, array(STATUS_NOT_DONE, STATUS_REVERTED)) && strpos($this->find, 'query(') !== false)
		{
			preg_match_all('#\n\t*.*?query\((.*?)\) or error.*\n#', "\n".$this->find."\n", $find_m, PREG_SET_ORDER);
			preg_match_all('#\n\t*.*?query\((.*?)\) or error.*\n#', "\n".$this->code."\n", $code_m, PREG_SET_ORDER);

			foreach ($find_m as $key => $cur_find_m)
			{
				$find_line = trim($cur_find_m[0]);
				$find_query = trim($cur_find_m[1]);
				$code_line = trim($code_m[$key][0]);
				$code_query = $code_m[$key][1];

				$query_id = md5($find_line);

				// Some mod modified this query before
				if (preg_match('#\n\t*.*?query\((.*?)\) or error.*?\/\/ QUERY ID: '.preg_quote($query_id).'#', $this->cur_file, $matches)) 
				{
					$query_line = trim($matches[0]);
					$cur_file_query = $matches[1];

					if (in_array($this->action, array('uninstall', 'disable')))
					{
						$replace_with = revert_query($cur_file_query, $code_query, $find_query);

						if (!$replace_with)
							break;

						$line = str_replace($find_query, $replace_with, $find_line); // line with query

						// Make sure we have QUERY ID at the end of line
						if ($find_query != $replace_with && strpos($line, '// QUERY ID') === false)
							$line .= ' // QUERY ID: '.$query_id;

						$this->find = str_replace($find_line, $line, $this->find);
						$this->code = str_replace($code_line, $query_line, $this->code);
					}
					else
					{
						$replace_with = replace_query($cur_file_query, $code_query); // query

						if (!$replace_with)
							break;

						$line = str_replace($code_query, $replace_with, $code_line); // line with query
						$this->find = str_replace($find_line, $query_line, $this->find);
						$this->code = str_replace($code_line, $line, $this->code);
					}
				}
			}

			if (in_array($this->action, array('install', 'enable')) || strpos($this->cur_file, $this->code) !== false)
				$status = $this->replace_code(trim($this->find), trim($this->code));
		}
		$this->find = $this->code;
		return $status;
	}

	
	function step_after_add()
	{
		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']) || $this->cur_file_path == '')
			return STATUS_NOTHING_TO_DO;

		//$this->find = trim($this->find);
		if (empty($this->find) || empty($this->cur_file))
			return STATUS_NOT_DONE;

		return $this->replace_code($this->find, $this->find."\n".$this->code);
	}
	
	
	function step_before_add()
	{
		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']) || $this->cur_file_path == '')
			return STATUS_NOTHING_TO_DO;
	
		if (empty($this->find) || empty($this->cur_file))
			return STATUS_NOT_DONE;

		return $this->replace_code($this->find, $this->code."\n".$this->find);
	}
	
	
	function step_at_the_end_of_file_add()
	{
		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']) || $this->cur_file_path == '')
			return STATUS_NOTHING_TO_DO;
			
		if (in_array($this->action, array('uninstall', 'disable')))
		{
			$count = 0;
			$this->cur_file = preg_replace('#'.make_regexp($this->code).'#si', '', $this->cur_file, 1, $count); // TODO: fix to str_replace_once
			if ($count == 1)
				return STATUS_REVERTED;
		}
		else
		{
			$this->cur_file .= "\n\n".$this->code;
			return STATUS_DONE;
		}

		return STATUS_NOT_DONE;
	}
	
	
	function step_add_new_elements_of_array()
	{
		// Mod was already disabled before
		if ($this->action == 'uninstall' && isset($this->config['installed_mods'][$this->flux_mod->id]['disabled']) || $this->cur_file_path == '')
			return STATUS_NOTHING_TO_DO;
	
		$count = 0;
		if (in_array($this->action, array('uninstall', 'disable')))
		{
			$this->cur_file = preg_replace('#'.make_regexp($this->code).'#si', '', $this->cur_file, 1, $count); // TODO: fix to str_replace_once
			if ($count == 1)
				return STATUS_REVERTED;
		}
		else
		{
			$this->cur_file = preg_replace('#,?\s*\);#si', ','."\n\n".$this->code."\n".');', $this->cur_file, 1, $count); // TODO: fix to str_replace_once
			if ($count == 1)
				return STATUS_DONE;
		}

		return STATUS_NOT_DONE;
	}
	
	
	function step_run_code()
	{
		if (defined('PATCHER_NO_SAVE'))
			return STATUS_UNKNOWN;
		
		if ($this->action == 'install')
		{
			eval($this->code);
			return STATUS_DONE; // done
		}
	}
	
	function step_run()
	{
		global $lang_admin_plugin_patcher;

		if (in_array($this->action, array('enable', 'disable')) && $this->code == 'install_mod.php')
			return STATUS_NOTHING_TO_DO;

		if (defined('PATCHER_NO_SAVE'))
			return STATUS_UNKNOWN;
		
		if ($this->code == 'install_mod.php')
		{
			if (!file_exists(PUN_ROOT.$this->code))
			{
				$this->result = $lang_admin_plugin_patcher['File does not exist error'];
				return STATUS_NOT_DONE;
			}

			if (!isset($_GET['skip_install']))
			{
				$install_code = file_get_contents(PUN_ROOT.'install_mod.php');
				$install_code = substr($install_code, strpos($install_code, '<?php') + 5);
				if (strpos($install_code, '// DO NOT EDIT ANYTHING BELOW THIS LINE!') !== false)
					$install_code = substr($install_code, 0, strpos($install_code, '// DO NOT EDIT ANYTHING BELOW THIS LINE!'));
				elseif (($install_pos = strpos($install_code, 'function install(')) !== false && strpos($install_code, '/***', $install_pos) !== false)
					$install_code = substr($install_code, 0, strpos($install_code, '/***', $install_pos));
				
				// Fix for changes in install_mod.php for another private messaging system
				elseif (strpos($install_code, '// Make sure we are running a FluxBB version') !== false)
				{
					$install_code = substr($install_code, 0, strpos($install_code, '// Make sure we are running a FluxBB version'));
					$install_code = str_replace(array('define(\'PUN_TURN_OFF_MAINT\', 1);', 'define(\'PUN_ROOT\', \'./\');', 'require PUN_ROOT.\'include/common.php\';'), '', $install_code);
				}

				$lines = explode("\n", $install_code);
				foreach ($lines as $cur_line)
					if (preg_match('#^\$[a-zA-Z0-9_-]+#', $cur_line, $matches))
						eval('global '.$matches[0].';');

				eval($install_code);
				if ($this->action == 'uninstall')
				{
					restore();
					$this->result = $lang_admin_plugin_patcher['Database restored'];
				}
				elseif (in_array($this->action, array('install', 'update')))
				{
					install();
					$this->result = sprintf($lang_admin_plugin_patcher['Database prepared for'], $mod_title);
				}
			}
			return STATUS_DONE;
		}
		else
		{
			ob_start();
			require PUN_ROOT.$this->code;
			$this->result = ob_get_contents();
			ob_end_clean();
			return STATUS_DONE;
		}
	}
	
	
	function step_delete()
	{
		// Should never happen
		if (in_array($this->action, array('enable', 'disable')))
			return STATUS_NOTHING_TO_DO;

		if (defined('PATCHER_NO_SAVE'))
			return STATUS_UNKNOWN;

		// Delete step is usually for install_mod.php so when uninstalling that file does not exist
		if ($this->action == 'uninstall')
			return STATUS_UNKNOWN;
		
		$this->code = trim($this->code);
		if (!file_exists(PUN_ROOT.$this->code))
			return STATUS_UNKNOWN;

		if (unlink(PUN_ROOT.$this->code))
			return STATUS_DONE; // done

		$this->result = $lang_admin_plugin_patcher['Can\'t delete file error'];
		return STATUS_NOT_DONE;
	}
	
	
	function step_rename()
	{
		if (defined('PATCHER_NO_SAVE'))
			return STATUS_UNKNOWN;

		$this->code = trim($this->code);
		
		$lines = explode("\n", $this->code);
		foreach ($lines as $line)
		{
			$files = explode('to', $line);
			$file_to_rename = trim($files[0]);
			$new_file = trim($files[1]);
			
			// TODO: fix status as it indicates last renamed file
			if (!file_exists($new_file) && rename(PUN_ROOT.$file_to_rename, PUN_ROOT.$new_file))
				$status = STATUS_DONE;
		}
		return $status;
	}
	
}