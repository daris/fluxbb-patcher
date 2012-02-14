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
	function executeStep(&$curStep)
	{
		parent::executeStep($curStep);

		// Replace STATUS_DONE with STATUS_REVERTED when uninstalling mod
		if ($curStep['status'] == STATUS_DONE)
			$curStep['status'] = STATUS_REVERTED;

		// Don't display Note message when uninstalling mod
		return ($curStep['command'] != 'NOTE');
			/*&& $curStep['status'] != STATUS_NOTHING_TO_DO) // Skip if mod is disabled and we want to uninstall it (as file changes has been already reverted)
			// TODO: isset($this->config['installed_mods'][$this->mod->id]['disabled']) ????*/
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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
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

		if ($this->validate)
			return STATUS_UNKNOWN;

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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']) || $this->curFilePath == '')
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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
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
		if (isset($this->config['installed_mods'][$this->mod->id]['disabled']))
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
