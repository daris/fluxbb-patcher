<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

class Patcher
{
	/**
	 * @var Patcher_Mod Instance of the Patcher_Mod class
	 */
	public $mod = null;

	/**
	 * @var array Patcher configuration options (loaded from the patcher_config.php file)
	 */
	public $config = array();

	/**
	 * @var array Orginal Patcher configuration (needed for checking whether something changed in configuration)
	 */
	public $configOrg = array();

	/**
	 * @var string Current file content
	 */
	public $curFile = null;

	/**
	 * @var string Relative path to the currently patched file
	 */
	public $curFilePath = null;

	/**
	 * @var bool Whether or not the current file was modified
	 */
	public $curFileModified = false;

	/**
	 * @var string The text that we want to replace with another in $curFile
	 */
	public $find = null;

	/**
	 * @var integer String position for the $curFile where we start to search specified string
	 */
	public $startPos = 0;

	/**
	 * @var integer Number of the current global step
	 */
	public $globalStep = 1;

	/**
	 * @var string Action we want to execute
	 * 		Possibilities are: install, uninstall, enable, disable, update
	 */
	public $action = null;

	/**
	 * @var array List of steps for each readme file
	 */
	public $steps = array();

	/**
	 * @var array Results of the patching process
	 */
	public $log = array();

	// Determine current action
	public $install = false;
	public $uninstall = false;
	public $update = false;
	public $disable = false;
	public $enable = false;

	/**
	 * @var array Commands that are modifying current file
	 */
	public $modifyFileCommands = array('FIND', 'REPLACE', 'BEFORE ADD', 'AFTER ADD'); // TODO: other commands

	/**
	 * @var array Commands that are not touching current file
	 */
	public $globalCommands = array('OPEN', 'RUN', 'RUN CODE', 'DELETE', 'RENAME', 'UPLOAD', 'NOTE');

	/**
	 * @var bool Only validate modification (without writing changes to the files)
	 */
	public $validate = false;

	/**
	 * @var array Content of all modified files before modification
	 */
	public $orginalFiles = array();

	/**
	 * @var array Content of all modified files after modification
	 */
	public $modifedFiles = array();

	/**
	 * Constructor
	 *
	 * @param type $mod
	 * 		Instance of the Patcher_Mod class
	 *
	 * @return type
	 */
	function __construct($mod)
	{
		$this->mod = $mod;

		$this->config = $this->configOrg = loadPatcherConfig();
		$this->config['patcher_config_rev'] = PATCHER_CONFIG_REV;
	}


	function __get($name)
	{
		$function_name = 'get'.ucfirst($name);
		return $this->$name = $this->$function_name();
	}

	/**
	 * Execute specified action
	 *
	 * @param string $action
	 * @param bool $validateOnly
	 * @return bool
	 */
	function executeAction($action, $validateOnly = false)
	{
		$this->install = $this->uninstall = $this->update = $this->disable = $this->enable = false;
		$this->action = $action;
		$this->$action = true;
		$this->steps = $this->getSteps();

		$this->validate = $validateOnly;
		return $this->patch();
	}

	/**
	 * Write changes to the files
	 *
	 * @return type
	 */
	function makeChanges()
	{
		global $fs;

	//	if (isset($_SESSION['patcher_config']))
//		{
//			$this->config = unserialize($_SESSION['patcher_config']);
//			unset($_SESSION['patcher_config']);
//		}

		if (isset($_SESSION['patcher_steps']))
			$this->steps = unserialize($_SESSION['patcher_steps']);

		// print_r($this->steps);

		if (isset($_SESSION['patcher_files']))
		{
			$files = unserialize($_SESSION['patcher_files']);
			foreach ($files as $curFile => $contents)
				$fs->put(PUN_ROOT.$curFile, $contents);
		}

		$this->validate = false;
		return $this->patch();
	}

	/**
	 * Return unmet requirements for all modifications affected by this patch
	 *
	 * @return type
	 */
	function unmetRequirements()
	{
		global $langPatcher;
		$requirements = array();

		foreach ($this->log as $curAction => $readmeFiles)
		{
			foreach ($readmeFiles as $curReadme => $curSteps)
			{
				foreach ($curSteps as $key => $curStep)
				{
					if (isset($curStep['status']) && $curStep['status'] == STATUS_NOT_DONE)
					{
						if (!isset($requirements['cannot_open']))
							$requirements['cannot_open'] = array();
						$requirements['cannot_open'][] = array(false, $curStep['code'], 'Cannot open file <a href="'.PLUGIN_URL.'&show_log#a'.$key.'">#'.$key.'</a>');
					}
					if (isset($curStep['substeps']))
					{
						foreach ($curStep['substeps'] as $id => $curSubStep)
						{
							if (isset($curSubStep['status']) && $curSubStep['status'] == STATUS_NOT_DONE)
							{
								if (!isset($requirements['missing_strings']))
									$requirements['missing_strings'] = array();

								$requirements['missing_strings'][] = array(false, $curStep['code'], $langPatcher['Missing string'].' <a href="'.PLUGIN_URL.'&show_log#a'.$id.'">#'.$id.'</a>');
							}
						}
					}
				}
			}
		}

		return $requirements;
	}

	/**
	 * Restore previous content of the modified files
	 *
	 * @return type
	 */
	function revertModifiedFiles()
	{
		global $fs;

		// Revert modified files
		foreach ($this->orginalFiles as $curFile => $contents)
			$fs->put(PUN_ROOT.$curFile, $contents);
	}

	/**
	 * Return all steps for each readme file affected by this patch
	 *
	 * @return array
	 */
	function getSteps()
	{
		$steps = array();

		if ($this->install || $this->update)
		{
			if ($this->mod->isValid)
			{
				// Load steps for current mod
				$steps[$this->mod->id.'/'.$this->mod->readmeFileName] = $this->mod->getSteps();

				// Load steps for related mods (readme_mod_name.txt)
				foreach ($this->mod->readmeFileList as $curReadmeFile)
				{
					$curReadmeFile = ltrim($curReadmeFile, '/');
					if (strpos($curReadmeFile, '_') === false)
						continue;

					$modKey = substr($curReadmeFile, strpos($curReadmeFile, '_') + 1);
					$modKey = substr($modKey, 0, strpos($modKey, '.txt'));
					$modKey = str_replace('_', '-', $modKey);

					if (isset($this->config['installed_mods'][$modKey]) && (!isset($this->config['installed_mods'][$this->mod->id]) || !in_array($curReadmeFile, $this->config['installed_mods'][$this->mod->id])))
						$steps[$this->mod->id.'/'.$curReadmeFile] = $this->mod->getSteps($curReadmeFile);
				}
			}

			foreach ($this->config['installed_mods'] as $curModId => $instModsReadmeFiles)
			{
				$mod = new Patcher_Mod($curModId);
				if (!$mod->isValid)
					continue;

				foreach ($mod->readmeFileList as $curReadmeFile)
				{
					$curReadmeFile = ltrim($curReadmeFile, '/');

					// skip when readme was already installed
					if (in_array($curReadmeFile, $instModsReadmeFiles))
						continue;

					$modKey = substr($curReadmeFile, strpos($curReadmeFile, '_') + 1);
					$modKey = substr($modKey, 0, strpos($modKey, '.txt'));
					$modKey = str_replace('_', '-', $modKey);

					if ($modKey == $this->mod->id)
						$steps[$mod->id.'/'.$curReadmeFile] = $mod->getSteps($curReadmeFile);
				}
			}
		}

		// Uninstall, disable, enable
		else
		{
			// Load cached steps
			foreach ($this->config['steps'] as $curReadmeFile => $stepList)
			{
				if (strpos($curReadmeFile, $this->mod->id) !== false || strpos($curReadmeFile, str_replace('-', '_', $this->mod->id)) !== false)
					$steps[$curReadmeFile] = $stepList;
			}

			if ($this->uninstall || $this->disable)
			{
				// Reverse readme list
				$steps = array_reverse($steps);

				// Correct the order of steps
				foreach ($steps as $curReadmeFile => &$stepList)
				{
					$runStepsStart = $runStepsEnd = $uploadStepsEnd = $curStepList = array();
					$cur_open_steps = array();
					foreach ($stepList as $key => $curStep)
					{
						// Move RUN and DELETE steps at the end
						if (in_array($curStep['command'], array('RUN', 'DELETE')))
						{
							$code = trim($curStep['code']);
							$runStepsEnd[] = $curStep;
						}

						// Delete files at the end
						elseif ($curStep['command'] == 'UPLOAD')
							$uploadStepsEnd[] = $curStep;

						elseif (in_array($curStep['command'], array('OPEN')))
						{
							$curStep['substeps'] = array();
							$curStepList[] = $curStep;
						}

						elseif (in_array($curStep['command'], array('FIND')))
						{
							$idx = count($curStepList) - 1;
							$curStepList[$idx]['substeps'][][0] = $curStep;
						}

						elseif (in_array($curStep['command'], array('REPLACE', 'AFTER ADD', 'BEFORE ADD')))
						{
							$idx = count($curStepList) - 1;
							$arr = $curStepList[$idx]['substeps'];
							$idx2 = count($arr) - 1;
							$curStepList[$idx]['substeps'][$idx2][] = $curStep;
						}

						else
							$curStepList[] = $curStep;
					}

					$newStep_list = array();
					foreach ($curStepList as $key => $cStepList)
					{
						if (!isset($cStepList['substeps']))
						{
							$newStep_list[] = $cStepList;
							continue;
						}

						$substeps = array_reverse($cStepList['substeps']);
						unset($cStepList['substeps']);
						$newStep_list[] = $cStepList;
						foreach ($substeps as $curStep)
							foreach ($curStep as $curStep_sub)
								$newStep_list[] = $curStep_sub;
					}

					$stepList = array_merge($runStepsStart, $newStep_list, $runStepsEnd, $uploadStepsEnd);
				}
			}
		}

		return $steps;
	}

	/**
	 * General patching method
	 * TODO: cleanup this method
	 *
	 * @return bool
	 */
	function patch()
	{
		global $fs;
		$failed = false;

		if ($this->uninstall || $this->disable)
		{
			foreach ($this->mod->filesToUpload as $from => $to)
			{
				// Copy install mod file as we want to uninstall mod
				if ($this->uninstall && strpos($from, 'install_mod.php') !== false)
					$fs->copy($this->mod->readmeFileDir.'/'.$from, PUN_ROOT.'install_mod.php');
				elseif (strpos($from, 'gen.php') !== false) // TODO: make this relative to RUN commands
					$fs->copy($this->mod->readmeFileDir.'/'.$from, PUN_ROOT.'gen.php');
			}
		}
		if ($this->uninstall)
			$this->friendlyUrlUninstallUpload();

		$i = 1;
		foreach ($this->log as $log)
			foreach ($log as $curActionLog)
				$i += count($curActionLog);
		$this->log[$this->action] = array();

		$steps = $this->steps; // TODO: there is something wrong with variables visibility

		 // Allow to add steps inside loop
		while (list($curReadmeFile, $stepList) = each($this->steps))
		{
			$logReadme = array();

			foreach ($stepList as $key => $curStep)
			{
				if (!isset($curStep['status']))
					$curStep['status'] = STATUS_UNKNOWN;

				$function = 'step'.str_replace(' ', '', ucfirst(strtolower($curStep['command'])));
				if (is_callable(array($this, $function)))
				{
					$this->command = $curStep['command'];
					$this->code = $curStep['code'];
					$this->comments = array();
					$this->result = '';

					// Execute current step
					if ($this->validate || (!$this->validate && !isset($curStep['validated'])))
						$curStep['status'] = $this->$function();

					// Replace STATUS_DONE with STATUS_REVERTED when uninstalling mod
					if (($this->uninstall || $this->disable) && $curStep['status'] == STATUS_DONE)
						$curStep['status'] = STATUS_REVERTED;

					if ($this->result != '')
						$curStep['result'] = $this->result;

					$curStep['code'] = $this->code;
					$curStep['comments'] = $this->comments;

					if (in_array($this->command, $this->modifyFileCommands) && $this->validate)
					{
						$this->steps[$curReadmeFile][$key]['validated'] = true;
						$this->steps[$curReadmeFile][$key]['status'] = $curStep['status'];
					}
				}

				if (!(($this->uninstall || $this->disable) && $curStep['command'] == 'NOTE') // Don't display Note message when uninstalling mod
					&& $curStep['status'] != STATUS_NOTHING_TO_DO) // Skip if mod is disabled and we want to uninstall it (as file changes has been already reverted)
				{
					if (in_array($curStep['command'], $this->globalCommands))
					{
						$this->globalStep = $i; // it is a global action

						if ($curStep['command'] == 'UPLOAD')
						{
							$code = array();
							foreach ($this->mod->filesToUpload as $from => $to)
								$code[] = $from.' to '.$to;
							$curStep['substeps'][0] = array('code' => implode("\n", $code));
							unset($curStep['code']);
						}
						elseif ($curStep['command'] == 'RUN CODE')
						{
							$curStep['substeps'][0] = array('code' => $curStep['code']);
							unset($curStep['code']);
						}

						$logReadme[$i] = $curStep;
					}
					else
					{
						if (!isset($logReadme[$this->globalStep]['substeps']))
							$logReadme[$this->globalStep]['substeps'] = array();

						$logReadme[$this->globalStep]['substeps'][$i] = $curStep;
					}
				}

				if (($curStep['status'] == STATUS_DONE || $curStep['status'] == STATUS_REVERTED) && $curStep['command'] != 'OPEN' && !$this->curFileModified)
					$this->curFileModified = true;

				if ($curStep['status'] == STATUS_NOT_DONE)
				{
					// If some step fail, make whole mod install fail
					if (!$failed)
						$failed = true;

					// Delete step if it fails
					if ($this->install || $this->update)
					{
						if (in_array($curStep['command'], array('BEFORE ADD', 'AFTER ADD', 'REPLACE')) && $key > 0 && isset($stepList[$key-1]) && $stepList[$key-1]['command'] == 'FIND')
							unset($stepList[$key-1]);
						unset($stepList[$key]);
					}
				}

				// Delete step for uninstall when step was done
				if ($this->uninstall && $curStep['status'] != STATUS_NOT_DONE && !in_array($curStep['command'], array('FIND', 'OPEN')))
				{
					if (in_array($curStep['command'], array('BEFORE ADD', 'AFTER ADD', 'REPLACE')) && isset($stepList[$key-1]) && $stepList[$key-1]['command'] == 'FIND')
						unset($stepList[$key-1]);

					unset($stepList[$key]);
				}

				$i++;
			}

			$this->log[$this->action][$curReadmeFile] = $logReadme;

			$stepList = array_values($stepList);
			if ($this->uninstall)
			{
				// Delete empty OPEN steps
				foreach ($stepList as $key => $curStep)
				{
					if ($curStep['command'] == 'OPEN' && ((isset($stepList[$key+1]['command']) && $stepList[$key+1]['command'] == 'OPEN') || !isset($stepList[$key+1])))
						unset($stepList[$key]);
				}
				$stepList = array_values($stepList);
			}

			// Update patcher configuration
			$curMod = substr($curReadmeFile, 0, strpos($curReadmeFile, '/'));
			$curReadme = substr($curReadmeFile, strpos($curReadmeFile, '/') + 1);

			if ($this->uninstall)
			{
				if (count($stepList) == 0 && isset($this->config['installed_mods'][$curMod]) && in_array($curReadme, $this->config['installed_mods'][$curMod]))
					$this->config['installed_mods'][$curMod] = array_diff($this->config['installed_mods'][$curMod], array($curReadme)); // delete an element

				if (empty($stepList))
					unset($this->config['steps'][$curReadmeFile]);
				else
					$this->config['steps'][$curReadmeFile] = $stepList;
			}
			elseif ($this->install || $this->update)
			{
				if (!isset($this->config['installed_mods'][$curMod]))
					$this->config['installed_mods'][$curMod] = array();

				if (!in_array($curReadme, $this->config['installed_mods'][$curMod]))
					$this->config['installed_mods'][$curMod][] = $curReadme;

				$this->config['steps'][$curReadmeFile] = $stepList;
			}
		}

		// Update patcher configuration
		if ($this->uninstall)
		{
			if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
				unset($this->config['installed_mods'][$this->mod->id]['disabled']);

			if (isset($this->config['installed_mods'][$this->mod->id]['version']))
				unset($this->config['installed_mods'][$this->mod->id]['version']);

			if ($failed)
				$this->config['installed_mods'][$this->mod->id]['uninstall_failed'] = true;
			else
			{
				if (isset($this->config['installed_mods'][$this->mod->id]['uninstall_failed']))
					unset($this->config['installed_mods'][$this->mod->id]['uninstall_failed']);
				if (empty($this->config['installed_mods'][$this->mod->id]))
					unset($this->config['installed_mods'][$this->mod->id]);
			}
		}
		elseif ($this->install || $this->update)
		{
			$this->config['installed_mods'][$this->mod->id]['version'] = $this->mod->version;

			if ($this->update && isset($this->config['installed_mods'][$this->mod->id]['disabled']))
				unset($this->config['installed_mods'][$this->mod->id]['disabled']);
		}
		elseif ($this->enable && isset($this->config['installed_mods'][$this->mod->id]['disabled']))
			unset($this->config['installed_mods'][$this->mod->id]['disabled']);
		elseif ($this->disable && $GLOBALS['action'] != 'update')
			$this->config['installed_mods'][$this->mod->id]['disabled'] = 1;

		// when some file was opened, save it
		$this->stepSave();

		$_SESSION['patcher_files'] = serialize($this->modifedFiles);

		if ($this->config != $this->configOrg)
		{
			if (!defined('PATCHER_NO_SAVE') && !$this->validate)
				savePatcherConfig($this->config);
			elseif (defined('PATCHER_DEBUG'))
				$fs->put(PATCHER_ROOT.'debug/patcher_config.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'$patcherConfig = '.var_export($this->config, true).';');
		}

		return !$failed;
	}

	/**
	 * Check whether specified code exists in current file content (and try to correct code, for example ignoring space changes)
	 *
	 * @param type &$code
	 * @return type
	 */
	function checkCode(&$code)
	{
		$reg = preg_quote($code, '#');
		if (preg_match('#'.$reg.'#si', $this->curFile))
			return true;

		// Code was not found
		// Ignore multiple tab characters
		$reg = preg_replace("#\t+#", '\t*', $reg);
		$this->comments[] = 'Tabs ignored';
		if (preg_match('#'.$reg.'#si', $this->curFile, $matches))
		{
			$code = $matches[0];
			return true;
		}

		// Ignore spaces
		$reg = preg_replace('#\s+#', '\s*', $reg);
		$this->comments[] = 'Spaces ignored';
		if (preg_match('#'.$reg.'#si', $this->curFile, $matches))
		{
			$code = $matches[0];
			return true;
		}

		// has query?
		$checkCode = $code;
		if (strpos($checkCode, 'query(') !== false)
		{
			preg_match_all('#\n\t*.*?query\((.*?)\) or error.*\n#', "\n".$checkCode."\n", $findM, PREG_SET_ORDER);

			foreach ($findM as $key => $curFindM)
			{
				$findLine = trim($curFindM[0]);
				$findQuery = trim($curFindM[1]);

				$queryId = md5($findLine);

				// Some mod modified this query before
				if (preg_match('#\n\t*.*?query\((.*?)\) or error.*?\/\/ QUERY ID: '.preg_quote($queryId).'#', $this->curFile, $matches))
				{
					$queryLine = trim($matches[0]);
					$curFileQuery = $matches[1];

					$checkCode = str_replace($findLine, $queryLine, $checkCode);
				}
			}
			$this->comments[] = 'Query match';
			if (strpos($this->curFile, $checkCode) !== false)
				return true;
		}

		return false;
	}

	/**
	 * Replace specified code
	 *
	 * @param type $find
	 * @param type $replace
	 * @return type
	 */
	function replaceCode($find, $replace)
	{
		// Mod was already disabled before
		if ($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

		if ($this->uninstall || $this->disable)
		{
			// Swap $find with $replace
			$tmp = $find;
			$find = $replace;
			$replace = $tmp;

			$pos = strrpos(substr($this->curFile, 0, $this->startPos), $find);
			if ($pos === false)
			{
				$pos = strrpos($this->curFile, $find);
				$this->comments[] = 'Whole file';

				if ($pos === false && in_array($this->command, array('BEFORE ADD', 'AFTER ADD')))
				{
					if ($this->command == 'BEFORE ADD')
						$find = $this->code."\n";
					elseif ($this->command == 'AFTER ADD')
						$find = "\n".$this->code;

					$replace = '';
					$this->comments[0] = 'Removing code';
					$pos = strpos($this->curFile, $find);
				}
			}
			else
				$this->startPos = $pos;

			if ($pos === false)
				return STATUS_NOT_DONE;

			$this->curFile = substr_replace($this->curFile, $replace, $pos, strlen($find));
			return STATUS_DONE;
		}

		$pos = strpos($this->curFile, $find, $this->startPos);
		if ($pos === false)
		{
			$pos = strpos($this->curFile, $find);
			$this->comments[0] = 'Whole file';
		}
		else
			$this->startPos = $pos + strlen($replace);

		if ($pos === false)
			return STATUS_NOT_DONE;

		$this->curFile = substr_replace($this->curFile, $replace, $pos, strlen($find));

		return STATUS_DONE;
	}

	/**
	 * Execute upload step from readme
	 *
	 * @return type
	 */
	function stepUpload()
	{
		global $langPatcher, $fs;

		if (defined('PATCHER_NO_SAVE') || ($this->validate && $this->uninstall))
			return STATUS_UNKNOWN;

		// Should never happen
		if ($this->enable || $this->disable)
			return STATUS_NOTHING_TO_DO;

		if ($this->uninstall)
		{
			$directories = array();
			foreach ($this->mod->filesToUpload as $from => $to)
			{
				if (file_exists(PUN_ROOT.$to))
					$fs->delete(PUN_ROOT.$to);

				$curPath = '';
				$dirStructure = explode('/', $to);
				foreach ($dirStructure as $curDir)
				{
					$curPath .= '/'.$curDir;
					if (is_dir(PUN_ROOT.$curPath) && !in_array($curPath, $directories))
						$directories[] = $curPath;
				}
			}
			rsort($directories);
			foreach ($directories as $curDir)
			{
				// Remove directories that are empty
				if ($fs->isEmptyDir(PUN_ROOT.$curDir))
					$fs->rmDir(PUN_ROOT.$curDir);
			}

			return STATUS_REVERTED;
		}

		foreach ($this->mod->filesToUpload as $from => $to)
		{
			if (is_dir($this->mod->readmeFileDir.'/'.$from))
				$fs->copyDir($this->mod->readmeFileDir.'/'.$from, PUN_ROOT.$to);
				// TODO: friendly_url_upload for directory
			else
			{
				if (is_dir(PUN_ROOT.$to) || substr($to, -1) == '/' || strpos(basename($to), '.') === false) // as a comment above
					$to .= (substr($to, -1) == '/' ? '' : '/').basename($from);

				if (!$fs->copy($this->mod->readmeFileDir.'/'.$from, PUN_ROOT.$to))
					message(sprintf($langPatcher['Can\'t copy file'], pun_htmlspecialchars($from), pun_htmlspecialchars($to))); // TODO: move message somewhere :)

				$this->friendlyUrlUpload($to);
			}
		}
		return STATUS_DONE;
	}

	/**
	 * Open file
	 *
	 * @return type
	 */
	function stepOpen()
	{
		global $langPatcher, $fs;

		// if some file was opened, save it
		$this->stepSave();

		// Mod was already disabled before
		if (($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled'])))
			return STATUS_NOTHING_TO_DO;

		$this->code = trim($this->code);

		if (!file_exists(PUN_ROOT.$this->code))
		{
			// Language file that is not English does not exist?
			if (strpos(strtolower($this->code), 'lang/') !== false && strpos(strtolower($this->code), '/english') === false)
			{
				$this->curFile = '';
				$this->curFilePath = '';
				return STATUS_NOTHING_TO_DO;
			}

			$this->curFile = '';
			$this->curFilePath = $this->code;
			$this->result = $langPatcher['File does not exist error'];
			return STATUS_NOT_DONE;
		}

		$this->curFilePath = $this->code;

		if (!$fs->isWritable(PUN_ROOT.$this->code))
			message(sprintf($langPatcher['File not writable'], pun_htmlspecialchars($this->code)));

		if (isset($this->modifedFiles[$this->code]))
			$this->curFile = $this->modifedFiles[$this->code];
		else
		{
			$this->curFile = file_get_contents(PUN_ROOT.$this->code);
			$this->orginalFiles[$this->code] = $this->curFile;
		}

		// Convert EOL to Unix style
		$this->curFile = str_replace("\r\n", "\n", $this->curFile);

		$this->friendlyUrlOpen();

		$this->startPos = $this->uninstall ? strlen($this->curFile) : 0;
		$this->curFileModified = false;
		return STATUS_DONE;
	}

	/**
	 * Save current file
	 *
	 * @return type
	 */
	function stepSave()
	{
		global $fs;
		if (empty($this->curFilePath) || !$this->curFileModified || empty($this->curFile))
			return;

		$this->friendlyUrlSave();

		if ($this->validate)
			$this->modifedFiles[$this->curFilePath] = $this->curFile;

		elseif (!defined('PATCHER_NO_SAVE'))
			$fs->put(PUN_ROOT.$this->curFilePath, $this->curFile);

		elseif (isset($GLOBALS['patcherDebug']['save']) && in_array($this->curFilePath, $GLOBALS['patcherDebug']['save']))
			$fs->put(PATCHER_ROOT.'debug/'.basename($this->curFilePath), $this->curFile);

		$this->curFile = '';
		$this->curFilePath = '';
		$this->curFileModified = false;
	}

	/**
	 * Look for the string in current file
	 *
	 * @return type
	 */
	function stepFind()
	{
		$this->find = $this->code;

		// Mod was already disabled before
		if ($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled']) || $this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		if ($this->uninstall || $this->disable)
			return STATUS_UNKNOWN;
		elseif (!$this->checkCode($this->find))
		{
			$this->find = '';
			return STATUS_NOT_DONE;
		}
		$this->code = $this->find;

		return STATUS_UNKNOWN;
	}

	/**
	 * Replace code with another in current file
	 *
	 * @return type
	 */
	function stepReplace()
	{
		// Mod was already disabled before
		if ($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled']) || $this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		if (!$this->uninstall && !$this->disable && empty($this->find))
			return STATUS_UNKNOWN;

		if (empty($this->find) || empty($this->curFile))
			return STATUS_NOT_DONE;

		// Add QUERY ID at end of query line
		if (strpos($this->code, 'query(') !== false)
		{
			preg_match_all('#\n\t*.*?query\(.*?\) or error.*\n#', "\n".$this->find."\n", $firstM, PREG_SET_ORDER);
			preg_match_all('#\n\t*.*?query\(.*?\) or error.*\n#', "\n".$this->code."\n", $secondM, PREG_SET_ORDER);

			foreach ($firstM as $key => $first)
			{
				$queryLine = trim($first[0]);
				$replaceLine = trim($secondM[$key][0]);

				$this->code = str_replace($replaceLine, $replaceLine.' // QUERY ID: '.md5($queryLine), $this->code);
			}
		}

		$status = $this->replaceCode(trim($this->find), trim($this->code));

		// has query?
		if (in_array($status, array(STATUS_NOT_DONE, STATUS_REVERTED)) && strpos($this->find, 'query(') !== false)
		{
			preg_match_all('#\n\t*.*?query\((.*?)\) or error.*\n#', "\n".$this->find."\n", $findM, PREG_SET_ORDER);
			preg_match_all('#\n\t*.*?query\((.*?)\) or error.*\n#', "\n".$this->code."\n", $codeM, PREG_SET_ORDER);

			foreach ($findM as $key => $curFindM)
			{
				$findLine = trim($curFindM[0]);
				$findQuery = trim($curFindM[1]);
				$codeLine = trim($codeM[$key][0]);
				$codeQuery = $codeM[$key][1];

				$queryId = md5($findLine);

				// Some mod modified this query before
				if (preg_match('#\n\t*.*?query\((.*?)\) or error.*?\/\/ QUERY ID: '.preg_quote($queryId).'#', $this->curFile, $matches))
				{
					$queryLine = trim($matches[0]);
					$curFileQuery = $matches[1];

					if ($this->uninstall || $this->disable)
					{
						$replaceWith = revertQuery($curFileQuery, $codeQuery, $findQuery);

						if (!$replaceWith)
							break;

						$line = str_replace($findQuery, $replaceWith, $findLine); // line with query

						// Make sure we have QUERY ID at the end of line
						if ($findQuery != $replaceWith && strpos($line, '// QUERY ID') === false)
							$line .= ' // QUERY ID: '.$queryId;

						$this->find = str_replace($findLine, $line, $this->find);
						$this->code = str_replace($codeLine, $queryLine, $this->code);
					}
					else
					{
						$replaceWith = replaceQuery($curFileQuery, $codeQuery); // query

						if (!$replaceWith)
							break;

						$line = str_replace($codeQuery, $replaceWith, $codeLine); // line with query
						$this->find = str_replace($findLine, $queryLine, $this->find);
						$this->code = str_replace($codeLine, $line, $this->code);
					}
				}
			}

			if ($this->install || $this->enable || strpos($this->curFile, $this->code) !== false)
			{
				$status = $this->replaceCode(trim($this->find), trim($this->code));
				$this->comments[] = 'Query ID';
			}
		}
		$this->find = $this->code;
		return $status;
	}

	/**
	 * Add code after found text
	 *
	 * @return type
	 */
	function stepAfterAdd()
	{
		// Mod was already disabled before
		if ($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled']) || $this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		if (empty($this->find) || empty($this->curFile))
			return STATUS_UNKNOWN;

		return $this->replaceCode($this->find, $this->find."\n".$this->code);
	}

	/**
	 * Add code before found text
	 *
	 * @return type
	 */
	function stepBeforeAdd()
	{
		// Mod was already disabled before
		if ($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled']) || $this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		if (empty($this->find) || empty($this->curFile))
			return STATUS_UNKNOWN;

		return $this->replaceCode($this->find, $this->code."\n".$this->find);
	}

	/**
	 * Add code at the end of current file
	 *
	 * @return type
	 */
	function stepAtTheEndOfFileAdd()
	{
		// Mod was already disabled before
		if ($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled']) || $this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		// TODO: not tested
		if ($this->uninstall || $this->disable)
		{
			$pos = strrpos($this->curFile, "\n\n".$this->code);
			if ($pos === false)
				return STATUS_NOT_DONE;

			$this->curFile = substr_replace($this->curFile, '', $pos, strlen("\n\n".$this->code));
			return STATUS_REVERTED;
		}

		$this->curFile .= "\n\n".$this->code;
		return STATUS_DONE;
	}

	/**
	 * Add new elements to array
	 *
	 * @return type
	 */
	function stepAddNewElementsOfArray()
	{
		// Mod was already disabled before
		if ($this->uninstall && isset($this->config['installed_mods'][$this->mod->id]['disabled']) || $this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		$count = 0;
		if ($this->uninstall || $this->disable)
		{
			$this->curFile = preg_replace('#'.preg_quote($this->code, '#').'#si', '', $this->curFile, 1, $count); // TODO: fix to str_replace_once
			if ($count == 1)
				return STATUS_REVERTED;

			return STATUS_NOT_DONE;
		}

		$this->curFile = preg_replace('#,?\s*\);#si', ','."\n\n".$this->code."\n".');', $this->curFile, 1, $count); // TODO: fix to str_replace_once
		if ($count == 1)
			return STATUS_DONE;

		return STATUS_NOT_DONE;
	}

	/**
	 * Execute Run step from readme (only used for Mod installer)
	 *
	 * @return type
	 */
	function stepRunCode()
	{
		if (defined('PATCHER_NO_SAVE') || $this->validate)
			return STATUS_UNKNOWN;

		global $db;
		eval($this->code);
		return STATUS_DONE; // done
	}

	/**
	 * Install or uninstall current modification
	 *
	 * @return type
	 */
	function stepRun()
	{
		global $langPatcher;

		if (($this->enable || $this->disable)/* && $this->code == 'install_mod.php'*/)
			return STATUS_NOTHING_TO_DO;

		if (defined('PATCHER_NO_SAVE') || $this->validate)
			return STATUS_UNKNOWN;

		if ($this->code == 'install_mod.php')
		{
			if (!file_exists(PUN_ROOT.$this->code))
			{
				$this->result = $langPatcher['File does not exist error'];
				return STATUS_NOT_DONE;
			}

			if (!isset($_REQUEST['skip_install']))
			{
				$installCode = file_get_contents(PUN_ROOT.'install_mod.php');
				$installCode = substr($installCode, strpos($installCode, '<?php') + 5);
				$len = strlen($installCode);

				if (($pos = strpos($installCode, '// DO NOT EDIT ANYTHING BELOW THIS LINE!')) !== false)
					$len = $pos;
				elseif (($pos = strpos($installCode, 'function install(')) !== false && ($pos2 = strpos($installCode, '/***', $pos)) !== false)
					$len = $pos2;

				// Fix for changes in install_mod.php for another private messaging system
				elseif (($pos = strpos($installCode, '// Make sure we are running a FluxBB version')) !== false)
					$len = $pos;

				$installCode = substr($installCode, 0, $len);
				$installCode = str_replace(array('define(\'PUN_TURN_OFF_MAINT\', 1);', 'define(\'PUN_ROOT\', \'./\');', 'require PUN_ROOT.\'include/common.php\';'), '', $installCode);
				$installCode = str_replace('or error(', 'or patcherError(', $installCode);

				$lines = explode("\n", $installCode);
				foreach ($lines as $curLine)
					if (preg_match('#^\$[a-zA-Z0-9_-]+#', $curLine, $matches))
						eval('global '.$matches[0].';');

				eval($installCode);
				if ($this->uninstall)
				{
					if (!function_exists('restore'))
					{
						$this->result = $langPatcher['Database not restored'];
						return STATUS_UNKNOWN;
					}
					restore();
					$this->result = $langPatcher['Database restored'];
				}
				elseif ($this->install || $this->update)
				{
					install();
					$this->result = sprintf($langPatcher['Database prepared for'], $mod_title);
				}
			}
			return STATUS_DONE;
		}

		ob_start();
		require_once PUN_ROOT.$this->code;
		$this->result = ob_get_clean();

		return STATUS_DONE;
	}

	/**
	 * Delete specified files
	 *
	 * @return type
	 */
	function stepDelete()
	{
		global $fs;

		// Should never happen
		if ($this->enable || $this->disable)
			return STATUS_NOTHING_TO_DO;

		if (defined('PATCHER_NO_SAVE') || $this->validate)
			return STATUS_UNKNOWN;

		// Delete step is usually for install_mod.php so when uninstalling that file does not exist
		if ($this->uninstall)
			return STATUS_UNKNOWN;

		$this->code = trim($this->code);
		if (!file_exists(PUN_ROOT.$this->code))
			return STATUS_UNKNOWN;

		if ($fs->delete(PUN_ROOT.$this->code))
			return STATUS_DONE; // done

		$this->result = $langPatcher['Can\'t delete file error'];
		return STATUS_NOT_DONE;
	}

	/**
	 * Rename files
	 *
	 * @return type
	 */
	function stepRename()
	{
		global $fs;
		if (defined('PATCHER_NO_SAVE') || $this->validate)
			return STATUS_UNKNOWN;

		$this->code = trim($this->code);

		$lines = explode("\n", $this->code);
		foreach ($lines as $curLine)
		{
			$files = explode('to', $curLine);
			$fileToRename = trim($files[0]);
			$newFile = trim($files[1]);

			// TODO: fix status as it indicates last renamed file
			if (!file_exists($newFile) && $fs->move(PUN_ROOT.$fileToRename, PUN_ROOT.$newFile))
				$status = STATUS_DONE;
		}
		return $status;
	}

	/**
	 * When friendly url mod is installed revert its changes from current file (apply again while saving this file)
	 *
	 * @return type
	 */
	function friendlyUrlOpen()
	{
		if ($this->mod->id == 'friendly-url' || !isset($this->config['installed_mods']['friendly-url']) || isset($this->config['installed_mods']['friendly-url']['disabled']) || !isset($this->config['steps']['friendly-url/files/gen.php']))
			return;

		$steps = $this->config['steps']['friendly-url/files/gen.php'];
		$steps = array_values($steps);
		$curFile = '';

		$changes = array();
		$found = false;
		for ($i = 0; $i < count($steps) - 1; $i++)
		{
			if ($found)
			{
				// Revert changes
				unset($this->config['steps']['friendly-url/files/gen.php'][$i]);
				$changes[] = array('replace' => $steps[$i]['code'], 'search' => $steps[++$i]['code']);
				unset($this->config['steps']['friendly-url/files/gen.php'][$i]);

				if (isset($steps[$i+1]['command']) && $steps[$i+1]['command'] == 'OPEN')
					break;
			}

			if (!$found && (!isset($steps[$i]['command']) || $steps[$i]['command'] != 'OPEN' || $steps[$i]['code'] != $this->curFilePath))
				continue;
			$found = true;
			unset($this->config['steps']['friendly-url/files/gen.php'][$i]);
		}
		$this->config['steps']['friendly-url/files/gen.php'] = array_values($this->config['steps']['friendly-url/files/gen.php']);
		$changes = array_reverse($changes);
		$endPos = strlen($this->curFile);
		foreach ($changes as $curChange)
		{
			$pos = strrpos(substr($this->curFile, 0, $endPos), $curChange['search']);
			if ($pos === false)
				$pos = strrpos($this->curFile, $curChange['search']); // as the changes are sorted by string position this should never happen
			else
				$endPos = $pos;

			$this->curFile = substr_replace($this->curFile, $curChange['replace'], $pos, strlen($curChange['search']));
		}
	}

	/**
	 * When friendly url mod is installed apply its changes again (as patcher reverted them in open step)
	 *
	 * @return type
	 */
	function friendlyUrlSave()
	{
		if ($this->mod->id == 'friendly-url' || !isset($this->config['installed_mods']['friendly-url']) || isset($this->config['installed_mods']['friendly-url']['disabled']))
			return;

		$curReadmeFile = 'friendly-url/files/gen.php';
		if (!isset($this->config['steps'][$curReadmeFile]))
			$this->config['steps'][$curReadmeFile] = array();

		if (file_exists(MODS_DIR.'friendly-url/files/gen.php'))
		{
			$changes = array();
			require_once MODS_DIR.'friendly-url/files/gen.php';
			$this->curFile = urlReplaceFile($this->curFilePath, $this->curFile, $changes);
			$this->config['steps'][$curReadmeFile] = array_merge($this->config['steps'][$curReadmeFile], urlGetSteps($changes));
		}
	}

	/**
	 * When friendly url mod is installed apply its changes
	 *
	 * @param type $curFileName
	 * @return type
	 */
	function friendlyUrlUpload($curFileName)
	{
		global $fs;

		if ($this->mod->id == 'friendly-url' || !isset($this->config['installed_mods']['friendly-url']) || isset($this->config['installed_mods']['friendly-url']['disabled'])
			|| substr($curFileName, -4) != '.php' || in_array($curFileName, array('gen.php', 'install_mod.php'))
			|| dirname($curFileName) != '.' && substr($curFileName, 0, 7) != 'include') // directory other than PUN_ROOT and include
			return;

		$genFile = 'friendly-url/files/gen.php';
		if (!isset($this->config['steps'][$genFile]))
			$this->config['steps'][$genFile] = array();

		if (file_exists(MODS_DIR.$genFile))
		{
			$changes = array();
			require_once MODS_DIR.'friendly-url/files/gen.php';
			$curFile = file_get_contents(PUN_ROOT.$curFileName);
			$curFile = urlReplaceFile($curFileName, $curFile, $changes);
			if (count($changes) > 0)
				$fs->put(PUN_ROOT.$curFileName, $curFile);
			$this->config['steps'][$genFile] = array_merge($this->config['steps'][$genFile], urlGetSteps($changes));
		}
	}

	/**
	 * Remove steps from the Patcher configuration when we are uninstalling affected mod
	 *
	 * @return type
	 */
	function friendlyUrlUninstallUpload()
	{
		$genFile = 'friendly-url/files/gen.php';
		if (!isset($this->config['steps'][$genFile]))
			return;

		foreach ($this->mod->filesToUpload as $from => $to)
		{
			$removeSteps = false;
			foreach ($this->config['steps'][$genFile] as $key => $curStep)
			{
				if ($removeSteps)
				{
					if (in_array($curStep['command'], $this->modifyFileCommands))
						unset($this->config['steps'][$genFile][$key]);
					else
						$removeSteps = false;
				}
				elseif ($curStep['command'] == 'OPEN' && $curStep['code'] == $to)
				{
					unset($this->config['steps'][$genFile][$key]);
					$removeSteps = true;
				}
			}
		}
	}
}
