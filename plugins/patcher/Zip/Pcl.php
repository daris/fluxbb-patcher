<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

require PATCHER_ROOT.'ZIP/Pcl/PclZip.php';

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

		return $zip->listContent();
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
		$not_readable = array();
		foreach ($files as $curFile)
		{
			if (!is_readable(PUN_ROOT.$curFile))
				$not_readable[] = $curFile;
		}
		if (!empty($not_readable))
			message('The following files are not readable:<br />'.implode('<br />', $not_readable));

		if ($this->zip->add(implode(',', $files)) === false)
			message($this->zip->errorInfo(true));

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
