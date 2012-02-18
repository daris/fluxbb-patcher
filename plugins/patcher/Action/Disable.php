<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

require_once PATCHER_ROOT.'Action/Uninstall.php';

class Patcher_Action_Disable extends Patcher_Action_Uninstall
{
	function patchInit()
	{
		global $fs;

		foreach ($this->patcher->mod->filesToUpload as $from => $to)
		{
			if (strpos($from, 'gen.php') !== false) // TODO: make this relative to RUN commands
				$fs->copy($this->patcher->mod->readmeFileDir.'/'.$from, PUN_ROOT.'gen.php');
		}
	}

	function updateStepList($curStep, &$stepList, $key)
	{
		return false;
	}

	function updateReadmeStepList(&$stepList, $curReadmeFile, $curMod, $curReadme)
	{
		return false;
	}

	/**
	 * Update Patcher configuration after patching
	 *
	 * @param bool $failed Whether patching failed or not
	 * @return type
	 */
	function updateconfig($failed)
	{
		if ($GLOBALS['action'] != 'update')
			$this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled'] = 1;
	}

	/**
	 * Execute upload step from readme
	 *
	 * @return type
	 */
	function stepUpload()
	{
		return STATUS_NOTHING_TO_DO;
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
		if (empty($this->find))
			return STATUS_UNKNOWN;

		return parent::stepReplace();
	}

	/**
	 * Execute Run step from readme (only used for Mod installer)
	 *
	 * @return type
	 */
	function stepRunCode()
	{
		return STATUS_NOTHING_TO_DO;
	}

	/**
	 * Install or uninstall current modification
	 *
	 * @return type
	 */
	function stepRun()
	{
		return STATUS_NOTHING_TO_DO;
	}

	/**
	 * Delete specified files
	 *
	 * @return type
	 */
	function stepDelete()
	{
		return STATUS_NOTHING_TO_DO;
	}

	/**
	 * Rename files
	 *
	 * @return type
	 */
	function stepRename()
	{
		return STATUS_NOTHING_TO_DO;
	}
}
