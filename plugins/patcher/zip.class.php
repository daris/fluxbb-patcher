<?php

if (!defined('ZIP_NATIVE'))
	define('ZIP_NATIVE', class_exists('ZipArchive') ? true : false);

if (!ZIP_NATIVE)
	require PATCHER_ROOT.'pclzip.lib.php';


class ZIP_ARCHIVE
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

	function list_content()
	{
		if (!$this->is_open)
			return false;

		$content = array();
		if (ZIP_NATIVE)
		{
			$i = 0;
			while ($cur_file = $this->zip->statIndex($i++))
				$content[] = $cur_file['name'];
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
			$files = $this->list_content();
			if (!$files)
				return false;

			foreach ($files as $cur_file)
			{
				$fp = $this->zip->getStream($cur_file);
				if (!$fp)
					message('Failed');
				$contents = '';
				while (!feof($fp))
					$contents .= fread($fp, 2);
				fclose($fp);

				if (in_array(substr($cur_file, -1), array('/', '\\')))
				{
					if (!is_dir($extract_to.'/'.$cur_file))
						$fs->mkdir($extract_to.'/'.$cur_file);
				}
				else
					$fs->put($extract_to.'/'.$cur_file, $contents);
			}
			return true; // TODO: error reporting
		}

		$files = $this->zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		if (!$files)
			return false;

		foreach ($files as $cur_file)
		{
			if ($cur_file['folder'] == 1)
			{
				if (!is_dir($extract_to.'/'.$cur_file['stored_filename']))
					$fs->mkdir($extract_to.'/'.$cur_file['stored_filename']);
			}
			else
				$fs->put($extract_to.'/'.$cur_file['stored_filename'], $cur_file['content']);
		}
		return true;
	}


	function add($files)
	{
		if (!$this->is_open)
			return false;

		// Check whether there are some files that we can't read
		$not_readable = array();
		foreach ($files as $cur_file)
		{
			if (!is_readable(PUN_ROOT.$cur_file))
				$not_readable[] = $cur_file;
		}
		if (!empty($not_readable))
			message('The following files are not readable:<br />'.implode('<br />', $not_readable));

		if (ZIP_NATIVE)
		{
			foreach ($files as $cur_file)
				$this->zip->addFile(PUN_ROOT.$cur_file, $cur_file);
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