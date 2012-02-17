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

	protected $options;

	function __construct($options = null)
	{
		$this->options = $options;
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
	 * @param type $dir
	 * @return type
	 */
	abstract function mkdir($dir);

	/**
	 * Move directory or file
	 *
	 * @param type $src
	 * @param type $dest
	 * @return type
	 */
	abstract function move($src, $dest);

	/**
	 * Copy file to another location
	 *
	 * @param type $src
	 * @param type $dest
	 * @return type
	 */
	abstract function copy($src, $dest);

	/**
	 * Save specified file
	 *
	 * @param type $file
	 * @param type $data
	 * @return type
	 */
	abstract function put($file, $data);

	/**
	 * Delete file
	 *
	 * @param type $file
	 * @return type
	 */
	abstract function delete($file);

	/**
	 * Remove directory recursively
	 *
	 * @param type $path
	 * @return type
	 */
	abstract function rmDir($path);

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
	abstract function copyDir($source, $dest);

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
	abstract function isWritable($path);

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

