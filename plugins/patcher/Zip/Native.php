<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

/**
 * Wrapper for ZipArchive class
 */
class Patcher_Zip_Native
{
	/**
	 * Open specified ZIP file
	 *
	 * @param type $file
	 * @param type $create
	 * @return type
	 */
	function create($file)
	{
		$this->zip = new ZipArchive;
		$this->isOpen = ($this->zip->open($this->file, ZIPARCHIVE::CREATE) === true);
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

		$this->zip = new ZipArchive;
		$this->isOpen = ($this->zip->open($file) === true);
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

		$content = array();

		$i = 0;
		while ($curFile = $this->zip->statIndex($i++))
			$content[] = $curFile['name'];
		return $content;
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

		$files = $this->listContent();

		if (!$files)
			throw new Exception('ZIP archive is empty');

		foreach ($files as $curFile)
		{
			$fp = $this->zip->getStream($curFile);

			if (!$fp)
				throw new Exception('Failed to read ZIP file');

			$contents = '';
			while (!feof($fp))
				$contents .= fread($fp, 2);
			fclose($fp);

			if (in_array(substr($curFile, -1), array('/', '\\')))
			{
				if (!is_dir($extractTo.'/'.$curFile))
					$fs->mkdir($extractTo.'/'.$curFile);
			}
			else
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
		$not_readable = array();
		foreach ($files as $curFile)
		{
			if (!is_readable(PUN_ROOT.$curFile))
				$not_readable[] = $curFile;
		}
		if (!empty($not_readable))
			message('The following files are not readable:<br />'.implode('<br />', $not_readable));

		foreach ($files as $curFile)
			$this->zip->addFile(PUN_ROOT.$curFile, $curFile);

		return true;
	}

	/**
	 * Close current ZIP file
	 *
	 * @return bool
	 */
	function close()
	{
		$this->zip->close();
	}
}
