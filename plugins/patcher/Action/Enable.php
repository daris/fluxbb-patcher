<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

require_once PATCHER_ROOT.'Action/Install.php';

class Patcher_Action_Enable extends Patcher_Action_Install
{
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

		if (!$this->checkCode($this->find))
		{
			$this->find = '';
			return STATUS_NOT_DONE;
		}
		$this->code = $this->find;

		return STATUS_UNKNOWN;
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
