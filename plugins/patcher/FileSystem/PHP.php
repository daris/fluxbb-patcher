<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

class Patcher_FileSystem_PHP extends Patcher_FileSystem
{
	/**
	 * Create directory
	 *
	 * @param type $directory
	 * @return type
	 */
	function mkdir($dir)
	{
		return mkdir($dir);
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
		return rename($src, $dest);
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
		return copy($src, $dest);
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
		return file_put_contents($file, $data);
	}

	/**
	 * Delete file
	 *
	 * @param type $file
	 * @return type
	 */
	function delete($file)
	{
		return unlink($file);
	}

	/**
	 * Remove directory recursively
	 *
	 * @param type $path
	 * @return type
	 */
	function rmDir($path)
	{
		if (!is_dir($path))
			return false;

		$list = $this->listToRemove($path);

		// It files aren't writable the rest of this function will not be executed
		$this->areFilesWritable($list);

		foreach ($list as $curFile)
		{
			if (is_dir($curFile))
				rmdir($curFile);
			else
				$this->delete($curFile);
		}

		return true;
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
		if (!is_dir($dest))
			$this->mkdir($dest);

		$d = dir($source);
		while ($f = $d->read())
		{
			if ($f != '.' && $f != '..' && $f != '.git' && $f != '.svn')
			{
				if (is_dir($source.'/'.$f))
					$this->copyDir($source.'/'.$f, $dest.'/'.$f);
				else
					$this->copy($source.'/'.$f, $dest.'/'.$f);
			}
		}
		$d->close();
		return true;
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
		if ($path == PUN_ROOT.'.')
			return $this->isWritable(PUN_ROOT);

		return is_writable($path);
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

