<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

require PATCHER_ROOT.'Zip/Pcl/PclZip.php';

/**
 * Wrapper for PclZip library
 */
class Patcher_Zip_Pcl
{
	/**
	 * Create specified ZIP file
	 *
	 * @param type $file
	 * @param type $create
	 * @return type
	 */
	function create($file)
	{
		$this->zip = new PclZip($file);
		$this->isOpen = true; // TODO: what if it fails?
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

		$this->zip = new PclZip($file);
		$this->isOpen = true; // TODO: what if it fails?
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

		$files = array();
		foreach ($this->zip->listContent() as $curFile)
			$files[] = $curFile['filename'];

		return $files;
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

		$files = $this->zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		if (!$files)
			throw new Exception('Failed to extract ZIP file');

		foreach ($files as $curFile)
		{
			if ($curFile['folder'] == 1)
			{
				if (!is_dir($extractTo.'/'.$curFile['stored_filename']))
					$fs->mkdir($extractTo.'/'.$curFile['stored_filename']);
			}
			else
				$fs->put($extractTo.'/'.$curFile['stored_filename'], $curFile['content']);
		}
		return true;
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
			throw new Exception('The following files are not readable:'."\n".implode("\n", $notReadable));

		$addFiles = array();
		foreach ($files as $curFile => $curPath)
			$addFiles[] = array(PCLZIP_ATT_FILE_NAME => $curPath, PCLZIP_ATT_FILE_NEW_FULL_NAME => $curFile);

		if ($this->zip->add($addFiles) === false)
			throw new Exception($this->zip->errorInfo(true));

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
