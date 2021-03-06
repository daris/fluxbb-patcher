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
	 * Patcher version
	 */
	const VERSION = '2.0-alpha';

	/**
	 * Configuration file revision
	 */
	const CONFIG_REV = 1;

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
	 * @var integer Number of the current global step
	 */
	public $globalStep = 1;

	/**
	 * @var string Action we want to execute
	 * 		Possibilities are: install, uninstall, enable, disable, update
	 */
	public $actionType = null;

	/**
	 * @var Patcher_Action
	 * 		An instance of the Patcher_Action class
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
	 * @var array Commands that are not touching current file
	 */
	public $globalCommands = array('OPEN', 'RUN', 'RUN CODE', 'DELETE', 'RENAME', 'UPLOAD', 'NOTE');

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

		$this->config['patcher_config_rev'] = self::CONFIG_REV;
	}


	function __get($name)
	{
		$function_name = 'get'.ucfirst($name);
		return $this->$name = $this->$function_name();
	}

	/**
	 * Execute specified action
	 *
	 * @param string $actionType
	 * @param bool $validateOnly
	 * @return bool
	 */
	function executeAction($actionType, $validateOnly = false)
	{
		$this->actionType = $actionType;
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
			if (!defined('PATCHER_NO_SAVE'))
			{
				foreach ($files as $curFile => $contents)
					$fs->put(PUN_ROOT.$curFile, $contents);
			}
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
					if (!isset($curStep['status']) || $curStep['status'] == STATUS_NOT_DONE)
					{
						if (!isset($requirements['cannot_open']))
							$requirements['cannot_open'] = array();
						$requirements['cannot_open'][] = array(false, $curStep['code'], $langPatcher['Cannot open file'].' <a href="'.PLUGIN_URL.'&show_log#a'.$key.'">#'.$key.'</a>');
					}
					if (isset($curStep['substeps']))
					{
						foreach ($curStep['substeps'] as $id => $curSubStep)
						{
							if (!isset($curSubStep['status']) || $curSubStep['status'] == STATUS_NOT_DONE)
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
		// TODO: fix this
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

		if ($this->actionType == 'install' || $this->actionType == 'update')
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

			if ($this->actionType == 'uninstall' || $this->actionType == 'disable')
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

		return $steps;
	}

	/**
	 * General patching method
	 *
	 * @return bool
	 */
	function patch()
	{
		// global $fs;
		$failed = false;

		$this->action = $this->determineAction();

		// Initialize patching process (copy install_mod.php when uninstalling mod, etc.)
		$this->action->patchInit();

		$i = 1;
		foreach ($this->log as $log)
			foreach ($log as $curActionLog)
				$i += count($curActionLog);

		$log_key = $this->actionType.':'.$this->mod->title;
		$this->log[$log_key] = array();

		$steps = $this->steps; // TODO: there is something wrong with variables visibility
//		patcherLog(var_export($steps, true));

		// Allow to add steps inside loop
		while (list($curReadmeFile, $stepList) = each($this->steps))
		{
			$logReadme = array();

			foreach ($stepList as $key => $curStep)
			{
				$stepResult = $curStep;
				if ($this->action->executeStep($curStep, $stepResult))
				{
					if (in_array($curStep['command'], $this->globalCommands))
					{
						$this->globalStep = $this->action->globalStep = $i; // it is a global action

						if ($curStep['command'] == 'UPLOAD')
						{
							$code = array();
							foreach ($this->mod->filesToUpload as $from => $to)
								$code[] = $from.' to '.$to;
							$curStep['substeps'][0] = array('status' => STATUS_DONE, 'code' => implode("\n", $code));
							unset($curStep['code']);
						}
						elseif ($curStep['command'] == 'RUN CODE')
						{
							$curStep['substeps'][0] = array('status' => STATUS_DONE, 'code' => $curStep['code']);
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

				$this->steps[$curReadmeFile][$key] = $stepResult;

				if (($curStep['status'] == STATUS_DONE || $curStep['status'] == STATUS_REVERTED) && $curStep['command'] != 'OPEN' && !$this->action->curFileModified)
					$this->action->curFileModified = true;

				// If some step fail, make whole mod install fail
				if ($curStep['status'] == STATUS_NOT_DONE && !$failed)
					$failed = true;

				$this->action->updateStepList($curStep, $stepList, $key);

				$i++;
			}

			$this->log[$log_key][$curReadmeFile] = $logReadme;

			$stepList = array_values($stepList);

			$curMod = substr($curReadmeFile, 0, strpos($curReadmeFile, '/'));
			$curReadme = substr($curReadmeFile, strpos($curReadmeFile, '/') + 1);

			// Remove unneeded steps and update configuration for current readme file
			$this->action->updateReadmeStepList($stepList, $curReadmeFile, $curMod, $curReadme);
		}

		// when some file was opened, save it
		$this->action->stepSave();

		// Update patcher configuration
		$this->action->updateConfig($failed);

		$_SESSION['patcher_files'] = serialize($this->modifedFiles);

		if ($this->config != $this->configOrg)
		{
			if (!defined('PATCHER_NO_SAVE') && !$this->validate)
				savePatcherConfig($this->config);
			// elseif (defined('PATCHER_DEBUG'))
			// 	$fs->put(PATCHER_ROOT.'debug/patcher_config.php', '<?php'."\n\n".'// DO NOT EDIT THIS FILE!'."\n\n".'$patcherConfig = '.var_export($this->config, true).';');
		}

		return !$failed;
	}

	function determineAction()
	{
		switch ($this->actionType)
		{
			case 'install':
				require_once PATCHER_ROOT.'Action/Install.php';
				$action = new Patcher_Action_Install($this);
				break;

			case 'uninstall':
				require_once PATCHER_ROOT.'Action/Uninstall.php';
				$action = new Patcher_Action_Uninstall($this);
				break;

			case 'enable':
				require_once PATCHER_ROOT.'Action/Enable.php';
				$action = new Patcher_Action_Enable($this);
				break;

			case 'disable':
				require_once PATCHER_ROOT.'Action/Disable.php';
				$action = new Patcher_Action_Disable($this);
				break;

			case 'update':
				require_once PATCHER_ROOT.'Action/Update.php';
				$action = new Patcher_Action_Update($this);
				break;

		}
		return $action;
	}

}
