<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

require_once PATCHER_ROOT.'Action/Install.php';

class Patcher_Action_Update extends Patcher_Action_Install
{
	/**
	 * Update Patcher configuration after patching
	 *
	 * @param bool $failed Whether patching failed or not
	 * @return type
	 */
	function updateconfig($failed)
	{
		parent::updateconfig($failed);

		if (isset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']))
			unset($this->patcher->config['installed_mods'][$this->patcher->mod->id]['disabled']);
	}
}
