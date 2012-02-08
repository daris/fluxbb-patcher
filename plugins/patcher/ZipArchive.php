<?php
/**
 * FluxBB Patcher 2.0-dev
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

if (!defined('ZIP_NATIVE'))
	define('ZIP_NATIVE', class_exists('ZipArchive') ? true : false);

if (!ZIP_NATIVE)
	require PATCHER_ROOT.'PclZip.php';

class Patcher_ZipArchive
{
	var $file;
	private $zip; // ZipArchive or PclZip object
	var $is_open = false;

	function __construct($file, $create = false)
	{
		$this->file = $file;
		$this->open(null, $create);
	}

	function open($file = null, $create = false)
	{
		if (isset($file))
			$this->file = $file;

		if (!file_exists($this->file) && !$create)
			message('File does not exist '.$this->file);

		if (ZIP_NATIVE)
		{
			$this->zip = new ZipArchive;
			$this->is_open = ($this->zip->open($this->file, ($create ? ZIPARCHIVE::CREATE : null)) === true);
			return $this->is_open;
		}

		$this->zip = new PclZip($this->file);
		$this->is_open = true; // TODO: what if it fails?
		return $this->is_open;
	}

	function listContent()
	{
		if (!$this->is_open)
			return false;

		$content = array();
		if (ZIP_NATIVE)
		{
			$i = 0;
			while ($curFile = $this->zip->statIndex($i++))
				$content[] = $curFile['name'];
			return $content;
		}

		return $archive->listContent();
	}

	function extract($extract_to)
	{
		global $fs;

		if (!$this->is_open)
			return false;

		if (!is_dir($extract_to))
			message('Can\'t extract files. Directory '.pun_htmlspecialchars($extract_to).' does not exist');

		if (ZIP_NATIVE)
		{
			$files = $this->listContent();
			if (!$files)
				return false;

			foreach ($files as $curFile)
			{
				$fp = $this->zip->getStream($curFile);
				if (!$fp)
					message('Failed');
				$contents = '';
				while (!feof($fp))
					$contents .= fread($fp, 2);
				fclose($fp);

				if (in_array(substr($curFile, -1), array('/', '\\')))
				{
					if (!is_dir($extract_to.'/'.$curFile))
						$fs->mkdir($extract_to.'/'.$curFile);
				}
				else
					$fs->put($extract_to.'/'.$curFile, $contents);
			}
			return true; // TODO: error reporting
		}

		$files = $this->zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		if (!$files)
			return false;

		foreach ($files as $curFile)
		{
			if ($curFile['folder'] == 1)
			{
				if (!is_dir($extract_to.'/'.$curFile['stored_filename']))
					$fs->mkdir($extract_to.'/'.$curFile['stored_filename']);
			}
			else
				$fs->put($extract_to.'/'.$curFile['stored_filename'], $curFile['content']);
		}
		return true;
	}


	function add($files)
	{
		if (!$this->is_open)
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

		if (ZIP_NATIVE)
		{
			foreach ($files as $curFile)
				$this->zip->addFile(PUN_ROOT.$curFile, $curFile);
			return true;
		}

		if ($this->zip->add(implode(',', $files)) === false)
			message($this->zip->errorInfo(true));

		return true;
	}

	function close()
	{
		if (ZIP_NATIVE)
			$this->zip->close();
	}
}
