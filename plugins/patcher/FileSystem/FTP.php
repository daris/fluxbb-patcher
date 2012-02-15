<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

class Patcher_FileSystem_FTP extends Patcher_FileSystem
{
	/**
	 * JFTP object
	 */
	public $ftp;

	public $isConnected = false;

	/**
	 * Connect to the FTP server (only when in FTP mode)
	 *
	 * @return type
	 */
	function checkConnection()
	{
		if ($this->isConnected)
			return false;

		require_once PATCHER_ROOT.'Ftp.php';

		$this->ftp = new JFTP();
		if (!$this->ftp->connect($this->options['host'], $this->options['port']))
			throw new Exception('FTP: Connection failed');

		if (!$this->ftp->login($this->options['user'], $this->options['pass']))
			throw new Exception('FTP: Login failed');

		if (!$this->ftp->chdir($this->options['path']))
			throw new Exception('FTP: Directory change failed');

		if (!@$this->ftp->listDetails($this->fixPath('config.php')))
			throw new Exception('FTP: The FluxBB root directory is not valid');

		$this->root = $this->options['path'];

		$this->isConnected = true;
	}

	/**
	 * Return path relative to the PUN_ROOT directory
	 *
	 * @param string $path
	 * @return string
	 */
	function fixPath($path)
	{
		$len = strlen(PUN_ROOT);

		// Is the current path prefixed with PUN_ROOT directory?
		if (substr($path, 0, $len) == PUN_ROOT)
			return ltrim(substr($path, $len), '/');

		return $path;
	}

	/**
	 * Create directory
	 *
	 * @param type $pathname
	 * @return type
	 */
	function mkdir($pathname)
	{
		$this->checkConnection();
		return $this->ftp->mkdir($this->fixPath($pathname));
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
		$this->checkConnection();

		$srcPath = $this->fixPath($src);

		// File is already on the FTP server (eg. in fluxbb cache directory) so move it to another location
		if (substr($src, 0, strlen(PUN_ROOT)) == PUN_ROOT)
			return $this->ftp->rename($srcPath, $this->fixPath($dest));

		// We have to upload file to the FTP server
		else
			return $this->ftp->store($src, $this->fixPath($dest)) && unlink($src);
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
		$this->checkConnection();
		return $this->ftp->store($src, $this->fixPath($dest));
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
		$this->checkConnection();
		return $this->ftp->write($this->fixPath($file), $data);
	}

	/**
	 * Delete file
	 *
	 * @param type $file
	 * @return type
	 */
	function delete($file)
	{
		$this->checkConnection();
		return $this->ftp->delete($this->fixPath($file));
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

		$this->checkConnection();

		$list = $this->listToRemove($path);

		// It files aren't writable the rest of this function will not be executed
		$this->areFilesWritable($list);

		foreach ($list as $curFile)
		{
			if (is_dir($curFile))
			{
				if ($this->isFtp)
					$this->ftp->delete($this->fixPath($curFile));
				else
					rmdir($curFile);
			}
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
	 * Check whether given file or directory is writable
	 *
	 * @param type $path
	 * @return type
	 */
	function isWritable($path)
	{
		if ($path == PUN_ROOT.'.')
			return $this->isWritable(PUN_ROOT);

		$this->checkConnection();

		$details = array();
		$name = '';
		if (is_dir($path))
		{
			if (substr($path, -1) != '/')
				$path .= '/';
			$fixedPath = $this->fixPath($path);
			if ($fixedPath == './')
				$fixedPath = '';
			$details = @$this->ftp->listDetails($fixedPath.'../');

			// Can't read directory contents?
			if (!is_array($details))
				return false;

			$name = basename($path);

			foreach ($details as $cur_details)
			{
				if ($cur_details['name'] == $name)
				{
					//print_r($cur_details);
					$rights = $cur_details['rights'];
					if (substr($rights, 0, 1) == 'd' && substr($rights, 2, 1) == 'w')
						return true;
					else
						return false;
				}
			}
		}
		else
		{
			$details = $this->ftp->listDetails($this->fixPath($path));
			$name = $this->fixPath($path);

			$rights = $details[0]['rights'];

			// Is not a file?
			if (substr($rights, 0, 1) != '-')
				return false;

			// TODO: real permissions checking
			if (substr($rights, 2, 1) == 'w')
				return true;
		}

		return false;
	}
}

