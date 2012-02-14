<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

class Patcher_Action_Install
{
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


	function executeStep(&$curStep)
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

			if ($this->result != '')
				$curStep['result'] = $this->result;

			$curStep['code'] = $this->code;
			$curStep['comments'] = $this->comments;

			if (in_array($this->command, $this->modifyFileCommands) && $this->validate)
				$curStep['validated'] = true;
		}
		return true;
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

		patcherLog('replaceCode in '.$this->curFilePath.': '.var_export(strpos($this->curFile, $replace), true));

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

		if (defined('PATCHER_NO_SAVE'))
			return STATUS_UNKNOWN;

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

		$this->startPos = 0;
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
			return STATUS_UNKNOWN;

		$this->friendlyUrlSave();

		if ($this->validate)
			$this->modifedFiles[$this->curFilePath] = $this->curFile;

		elseif (!defined('PATCHER_NO_SAVE'))
		{
			$fs->put(PUN_ROOT.$this->curFilePath, $this->curFile);
			patcherLog('Saved file: '.$this->curFilePath);
		}

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

		if (!$this->checkCode($this->find))
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

	function replaceQuery($curFileQuery, $findQuery, $codeQuery, $findLine, $queryLine, $codeLine)
	{
		$replaceWith = replaceQuery($curFileQuery, $codeQuery); // query

		if (!$replaceWith)
			return false;

		$line = str_replace($codeQuery, $replaceWith, $codeLine); // line with query
		$this->find = str_replace($findLine, $queryLine, $this->find);
		$this->code = str_replace($codeLine, $line, $this->code);
	}

	/**
	 * Add code after found text
	 *
	 * @return type
	 */
	function stepAfterAdd()
	{
		// Mod was already disabled before
		if ($this->curFilePath == '')
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
		if ($this->curFilePath == '')
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
		if ($this->curFilePath == '')
			return STATUS_NOTHING_TO_DO;

		$count = 0;
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

				install();
				$this->result = sprintf($langPatcher['Database prepared for'], $mod_title);
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

		if (defined('PATCHER_NO_SAVE') || $this->validate)
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
}
