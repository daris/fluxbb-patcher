<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

require_once PATCHER_ROOT.'Action/Install.php';

class Patcher_Action_Uninstall extends Patcher_Action_Install
{
	function patchInit()
	{
		global $fs;

		foreach ($this->patcher->mod->filesToUpload as $from => $to)
		{
			// Copy install mod file as we want to uninstall mod
			if (strpos($from, 'install_mod.php') !== false)
				$fs->copy($this->patcher->mod->readmeFileDir.'/'.$from, PUN_ROOT.'install_mod.php');
			elseif (strpos($from, 'gen.php') !== false) // TODO: make this relative to RUN commands
				$fs->copy($this->patcher->mod->readmeFileDir.'/'.$from, PUN_ROOT.'gen.php');
		}

		$this->friendlyUrlUninstallUpload();
	}

	/**
	 * Execute specified step
	 *
	 * @param array &$curStep
	 * 		Step to process, for example:
	 * 		array(
	 * 			'command'	=> 'FIND',
	 * 			'code'		=> '// some code'
	 * 		)
	 *
	 * @param array &$stepResult
	 * 		Duplicate of the $curStep array, contains result of the executing step, for example:
	 * 		array(
	 * 			'command'	=> 'FIND',
	 * 			'code'		=> '// some code'
	 * 			'validate'	=> true,
	 * 			'status'	=> STATUS_DONE
	 * 		)
	 *
	 * @return type
	 */
	function executeStep(&$curStep, &$stepResult)
	{
		parent::executeStep($curStep, $stepResult);

		// Replace STATUS_DONE with STATUS_REVERTED when uninstalling mod
		if ($curStep['status'] == STATUS_DONE)
			$curStep['status'] = STATUS_REVERTED;

		// Don't display Note message when uninstalling mod
		return ($curStep['command'] != 'NOTE');
			/*&& $curStep['status'] != STATUS_NOTHING_TO_DO) // Skip if mod is disabled and we want to uninstall it (as file changes has been already reverted)
			// TODO: isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']) ????*/
	}

	function updateStepList($curStep, &$stepList, $key)
	{
		// Delete step for uninstall when step was done
		if ($curStep['status'] != STATUS_NOT_DONE && !in_array($curStep['command'], array('FIND', 'OPEN')))
		{
			if (in_array($curStep['command'], array('BEFORE ADD', 'AFTER ADD', 'REPLACE')) && isset($stepList[$key-1]) && $stepList[$key-1]['command'] == 'FIND')
				unset($stepList[$key-1]);

			unset($stepList[$key]);
		}
	}

	function updateReadmeStepList(&$stepList, $curReadmeFile, $curMod, $curReadme)
	{
		// Delete empty OPEN steps
		foreach ($stepList as $key => $curStep)
		{
			if ($curStep['command'] == 'OPEN' && ((isset($stepList[$key+1]['command']) && $stepList[$key+1]['command'] == 'OPEN') || !isset($stepList[$key+1])))
				unset($stepList[$key]);
		}
		$stepList = array_values($stepList);

		// Update configuration for specified readme file
		if (count($stepList) == 0 && isset($this->patcher->config['installed_mods'][$curMod]) && in_array($curReadme, $this->patcher->config['installed_mods'][$curMod]))
			$this->patcher->config['installed_mods'][$curMod] = array_diff($this->patcher->config['installed_mods'][$curMod], array($curReadme)); // delete an element

		if (empty($stepList))
			unset($this->patcher->config['steps'][$curReadmeFile]);
		else
			$this->patcher->config['steps'][$curReadmeFile] = $stepList;
	}

	/**
	 * Update Patcher configuration after patching
	 *
	 * @param bool $failed Whether patching failed or not
	 * @return type
	 */
	function updateConfig($failed)
	{
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			unset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']);

		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['version']))
			unset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['version']);

		if ($failed)
			$this->patcher->config['installed_mods'][$this->patcher->mod->id]['uninstall_failed'] = true;
		else
		{
			if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['uninstall_failed']))
				unset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['uninstall_failed']);
			if (empty($this->patcher->config['installed_mods'][$this->patcher->mod->id]))
				unset($this->patcher->config['installed_mods'][$this->patcher->mod->id]);
		}
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
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

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

	/**
	 * Execute upload step from readme
	 *
	 * @return type
	 */
	function stepUpload()
	{
		global $langPatcher, $fs;

		if ($this->patcher->validate)
			return STATUS_UNKNOWN;

		$directories = array();
		foreach ($this->patcher->mod->filesToUpload as $from => $to)
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
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

		$status = parent::stepOpen();
		$this->startPos = strlen($this->curFile);
		return $status;
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
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

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
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']) || $this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		if (empty($this->find))
			return STATUS_UNKNOWN;

		return parent::stepReplace();
	}

	/**
	 * Add code after found text
	 *
	 * @return type
	 */
	function stepAfterAdd()
	{
		// Mod was already disabled before
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

		return parent::stepAfterAdd();
	}

	/**
	 * Add code before found text
	 *
	 * @return type
	 */
	function stepBeforeAdd()
	{
		// Mod was already disabled before
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

		return parent::stepBeforeAdd();
	}

	/**
	 * Add code at the end of current file
	 *
	 * @return type
	 */
	function stepAtTheEndOfFileAdd()
	{
		// Mod was already disabled before
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

		// TODO: not tested
		$pos = strrpos($this->curFile, "\n\n".$this->code);
		if ($pos === false)
			return STATUS_NOT_DONE;

		$this->curFile = substr_replace($this->curFile, '', $pos, strlen("\n\n".$this->code));
		return STATUS_REVERTED;
	}

	/**
	 * Add new elements to array
	 *
	 * @return type
	 */
	function stepAddNewElementsOfArray()
	{
		// Mod was already disabled before
		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			return STATUS_NOTHING_TO_DO;

		$count = 0;
		$this->curFile = preg_replace('#'.preg_quote($this->code, '#').'#si', '', $this->curFile, 1, $count); // TODO: fix to str_replace_once
		if ($count == 1)
			return STATUS_REVERTED;

		return STATUS_NOT_DONE;
	}

	/**
	 * Install or uninstall current modification
	 *
	 * @return type
	 */
	function stepRun()
	{
		global $langPatcher;

		if (defined('PATCHER_NO_SAVE') || $this->patcher->validate)
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

				if (!function_exists('restore'))
				{
					$this->result = $langPatcher['Database not restored'];
					return STATUS_UNKNOWN;
				}
				restore();
				$this->result = $langPatcher['Database restored'];
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
		// Delete step is usually for install_mod.php so when uninstalling that file does not exist
		return STATUS_UNKNOWN;
	}

	/**
	 * Remove steps from the Patcher configuration when we are uninstalling affected mod
	 *
	 * @return type
	 */
	function friendlyUrlUninstallUpload()
	{
		$genFile = 'friendly-url/files/gen.php';
		if (!isset($this->patcher->config['steps'][$genFile]))
			return;

		foreach ($this->patcher->mod->filesToUpload as $from => $to)
		{
			$removeSteps = false;
			foreach ($this->patcher->config['steps'][$genFile] as $key => $curStep)
			{
				if ($removeSteps)
				{
					if (in_array($curStep['command'], $this->modifyFileCommands))
						unset($this->patcher->config['steps'][$genFile][$key]);
					else
						$removeSteps = false;
				}
				elseif ($curStep['command'] == 'OPEN' && $curStep['code'] == $to)
				{
					unset($this->patcher->config['steps'][$genFile][$key]);
					$removeSteps = true;
				}
			}
		}
	}
}
