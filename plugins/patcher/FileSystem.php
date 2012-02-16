<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

abstract class Patcher_FileSystem
{
	static function load($type, $options)
	{
		$class = 'Patcher_FileSystem_'.$type;
		if (!class_exists($class))
		{
			if (!file_exists(PATCHER_ROOT.'FileSystem/'.$type.'.php'))
				throw new Exception('No such FileSystem type: '.$type);

			require PATCHER_ROOT.'FileSystem/'.$type.'.php';
		}

		return new $class($options);
	}

	public $options;
	public $root;

	function __construct($options = null)
	{
		$this->options = $options;
		$this->root = PUN_ROOT;
	}

	/**
	 * Return path to the temporary file in cache directory
	 *
	 * @return string
	 */
	function tmpname()
	{
		return FORUM_CACHE_DIR.md5(time().rand());
	}

	/**
	 * Create directory
	 *
	 * @param type $directory
	 * @return type
	 */
	function mkdir($dir)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Move directory or file
	 *
	 * @param type $src
	 * @param type $dest
	 * @return type
	 */
	function move($src, $dest)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Copy file to another location
	 *
	 * @param type $src
	 * @param type $dest
	 * @return type
	 */
	function copy($src, $dest)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Save specified file
	 *
	 * @param type $file
	 * @param type $data
	 * @return type
	 */
	function put($file, $data)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Delete file
	 *
	 * @param type $file
	 * @return type
	 */
	function delete($file)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Remove directory recursively
	 *
	 * @param type $path
	 * @return type
	 */
	function rmDir($path)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Get the list of files from specified directory that we want to remove
	 *
	 * @param type $path
	 * @return type
	 */
	function listToRemove($path)
	{
		$files = array();
		$d = dir($path);
		while ($f = $d->read())
		{
			if ($f == '.' || $f == '..')
				continue;

			if (is_file($path.'/'.$f))
				$files[] = $path.'/'.$f;
			else
			{
				$files = array_merge($files, $this->listToRemove($path.'/'.$f));
				//$directories[] = $path.'/'.$f;
			}
		}
		$d->close();
		$files[] = $path;
		return $files;
	}

	/**
	 * Copy specified directory
	 *
	 * @param type $source
	 * @param type $dest
	 * @return type
	 */
	function copyDir($source, $dest)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Check whether given directory is empty
	 *
	 * @param type $dir
	 * 		Path to the directory
	 *
	 * @return bool
	 */
	function isEmptyDir($dir)
	{
		$d = dir($dir);
		while ($f = $d->read())
		{
			if ($f != '.' && $f != '..')
			{
				$d->close();
				return false;
			}
		}
		$d->close();
		return true;
	}

	/**
	 * Check whether given file or directory is writable
	 *
	 * @param type $path
	 * @return type
	 */
	function isWritable($path)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Check whether specified files are writable
	 *
	 * @param type $files
	 * @return type
	 */
	function areFilesWritable($files)
	{
		global $langPatcher;

		$notWritable = array();
		foreach ($files as $curFile)
		{
			if (!$this->isWritable($curFile))
				$notWritable[] = $curFile;
		}

		if (count($notWritable) > 0)
			message($langPatcher['Files not writable info'].':<br />'.implode('<br />', $notWritable));

		return true;
	}
}

