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

					$newStepList = array();
					foreach ($curStepList as $key => $cStepList)
					{
						if (!isset($cStepList['substeps']))
						{
							$newStepList[] = $cStepList;
							continue;
						}

						$substeps = array_reverse($cStepList['substeps']);
						unset($cStepList['substeps']);
						$newStepList[] = $cStepList;
						foreach ($substeps as $curStep)
							foreach ($curStep as $curStep_sub)
								$newStepList[] = $curStep_sub;
					}

					$stepList = array_merge($runStepsStart, $newStepList, $runStepsEnd, $uploadStepsEnd);
				}
			}
		}
//print_r($steps);
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

		$action = $this->determineAction();
		$action->validate = $this->validate;
		$action->config = $this->config;
		$action->mod = $this->mod;
		$action->action = $this->action;
		$cur_action = $this->action;
		$action->$cur_action = true;

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
			$action->friendlyUrlUninstallUpload();

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
				$action->executeStep($curStep, $this->steps[$curReadmeFile][$key]);

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

				if (($curStep['status'] == STATUS_DONE || $curStep['status'] == STATUS_REVERTED) && $curStep['command'] != 'OPEN' && !$action->curFileModified)
					$action->curFileModified = $this->curFileModified = true;

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
		$action->stepSave();
		patcherLog(__FUNCTION__.': <?'.var_export($this->log, true));

		$_SESSION['patcher_files'] = serialize($action->modifedFiles);

		if ($this->config != $this->configOrg)
		{
			if (!defined('PATCHER_NO_SAVE') && !$this->validate)
				savePatcherConfig($this->config);
			elseif (defined('PATCHER_DEBUG'))
				$fs->put(PATCHER_ROOT.'debug/patcher_config.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'$patcherConfig = '.var_export($this->config, true).';');
		}

		return !$failed;
	}

	function determineAction()
	{
		switch ($this->action)
		{
			case 'install':
				require_once PATCHER_ROOT.'Action/Install.php';
				$action = new Patcher_Action_Install;
				break;

			case 'uninstall':
				require_once PATCHER_ROOT.'Action/Uninstall.php';
				$action = new Patcher_Action_Uninstall;
				break;

			case 'enable':
				require_once PATCHER_ROOT.'Action/Enable.php';
				$action = new Patcher_Action_Enable;
				break;

			case 'disable':
				require_once PATCHER_ROOT.'Action/Disable.php';
				$action = new Patcher_Action_Disable;
				break;

			case 'update':
				require_once PATCHER_ROOT.'Action/Update.php';
				$action = new Patcher_Action_Update;
				break;

		}
		return $action;
	}

}
