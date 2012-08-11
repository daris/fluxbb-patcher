<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

/**
 * Manage Zip files using the unzip command via shell_exec()
 */
class Patcher_Zip_Shell
{
	/**
	 * Constructor (checking for unzip command)
	 */
	function __construct()
	{
		$result = trim(shell_exec('unzip -v'));
		if (empty($result))
			throw new Exception('The unzip shell command was not found. Make sure your server has this application installed.');
	}

	/**
	 * Open specified ZIP file
	 *
	 * @param type $file
	 * @param type $create
	 * @return type
	 */
	function create($file)
	{
		$result = trim(shell_exec('zip -v'));
		if (empty($result))
			throw new Exception('The zip shell command was not found. Make sure your server has this application installed.');

		$this->isOpen = true;
		$this->file = $file;
		return $this->isOpen;
	}

	/**
	 * Open specified ZIP file
	 *
	 * @param type $file
	 * @param type $create
	 * @return type
	 */
	function open($file)
	{
		if (!file_exists($file))
			throw new Exception('File does not exist '.$file);

		$this->isOpen = true;
		$this->file = $file;
		return $this->isOpen;
	}

	/**
	 * Return list of the files from ZIP file
	 *
	 * @return array
	 */
	function listContent()
	{
		if (!$this->isOpen)
			return false;

		$list = shell_exec('unzip -ql "'.$this->file.'"');
		preg_match_all('/\d+:\d+\s{2,}(.*)/', $list, $matches);

		if (!isset($matches[1]))
			$matches[1] = array();

		sort($matches[1]);
		return $matches[1];
	}

	/**
	 * Extract ZIP file to the specified directory
	 *
	 * @param type $extractTo
	 * @return type
	 */
	function extract($extractTo)
	{
		global $fs;

		if (!$this->isOpen)
			return false;

		if (!is_dir($extractTo))
			throw new Exception('Directory '.$extractTo.' does not exist');

		// Hack for extracting zip as one shell command
		if (get_class($fs) == 'Patcher_FileSystem_Native')
		{
			shell_exec('unzip -o "'.$this->file.'" -d "'.$extractTo.'"');
			return true;
		}

		$files = $this->listContent();

		if (!$files)
			throw new Exception('ZIP archive is empty');

		foreach ($files as $curFile)
		{
			$contents = shell_exec('unzip -p "'.$this->file.'" "'.$curFile.'"');

			$dir = dirname($curFile);
			if (!is_dir($extractTo.'/'.$dir))
				$fs->mkdir($extractTo.'/'.$dir);

			$fs->put($extractTo.'/'.$curFile, $contents);
		}
		return true; // TODO: error reporting
	}

	/**
	 * Add files to the ZIP archive
	 *
	 * @param array $files
	 * @return bool
	 */
	function add($files)
	{
		if (!$this->isOpen)
			return false;

		// Check whether there are some files that we can't read
		$notReadable = array();
		foreach ($files as $curFile => $curPath)
		{
			if (!is_readable($curPath))
				$notReadable[] = $curFile;
		}
		if (!empty($notReadable))
			message('The following files are not readable:<br />'.implode('<br />', $notReadable));

		$cmd = 'zip "'.$this->file.'"';
		foreach ($files as $curFile => $curPath)
			$cmd .= ' "'.$curFile.'"';

		shell_exec($cmd);
		return true;
	}

	/**
	 * Close current ZIP file
	 *
	 * @return bool
	 */
	function close()
	{
		return true;
	}
}
