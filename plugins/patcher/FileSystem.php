<?php


class Patcher_FileSystem
{
	var $ftp;
	var $root = PUN_ROOT;
	var $isFtp = false;
	var $ftpData = array();
	var $isConnected = false;

	function __construct($ftpData = null)
	{
		if (isset($ftpData) && is_array($ftpData))
		{
			$this->isFtp = true;
			$this->ftpData = $ftpData;
		}
	}

	// Returns path of temporary file in cache directory
	function tmpname()
	{
		return FORUM_CACHE_DIR.md5(time().rand());
	}


	function checkConnection()
	{
		if (!$this->isFtp || $this->isConnected)
			return false;

		require_once PATCHER_ROOT.'Ftp.php';

		$this->ftp = new JFTP();
		if (!$this->ftp->connect($this->ftpData['host'], $this->ftpData['port']))
			error('FTP: Connection failed', __FILE__, __LINE__);
		if (!$this->ftp->login($this->ftpData['user'], $this->ftpData['pass']))
			error('FTP: Login failed', __FILE__, __LINE__);
		if (!$this->ftp->chdir($this->ftpData['path']))
			error('FTP: Directory change failed', __FILE__, __LINE__);

		if (!@$this->ftp->listDetails($this->fixPath('config.php')))
			error('FTP: The FluxBB root directory is not valid', __FILE__, __LINE__);

		$this->root = $this->ftpData['path'];

		$this->isConnected = true;
	}

	function fixPath($path)
	{
		$len = strlen(PUN_ROOT);

		// Is the current path prefixed with PUN_ROOT directory?
		if (substr($path, 0, $len) == PUN_ROOT)
			return ltrim(substr($path, $len), '/');

		return $path;
	}


	function mkdir($pathname)
	{
		$this->checkConnection();
		return $this->isFtp ? $this->ftp->mkdir($this->fixPath($pathname)) : mkdir($pathname);
	}

	function move($src, $dest)
	{
		$this->checkConnection();
		if ($this->isFtp)
		{
			$src_path = $this->fixPath($src);

			// File is already on the FTP server (eg. in fluxbb cache directory) so move it to another location
			if (substr($src, 0, strlen(PUN_ROOT)) == PUN_ROOT)
				return $this->ftp->rename($src_path, $this->fixPath($dest));

			// We have to upload file to the FTP server
			else
				return $this->ftp->store($src, $this->fixPath($dest)) && unlink($src);
		}
		return rename($src, $dest);
	}

	function copy($src, $dest)
	{
		$this->checkConnection();
		if ($this->isFtp)
			return $this->ftp->store($src, $this->fixPath($dest));

		return copy($src, $dest);
	}

	function put($file, $data)
	{
		$this->checkConnection();
		return $this->isFtp ? $this->ftp->write($this->fixPath($file), $data) : file_put_contents($file, $data);
	}

	function delete($file)
	{
		$this->checkConnection();
		if ($this->isFtp)
			return $this->ftp->delete($this->fixPath($file));

		return unlink($file);
	}

	// Recursive directory remove
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


	function isWritable($path)
	{
		if ($path == PUN_ROOT.'.')
			return $this->isWritable(PUN_ROOT);

		$this->checkConnection();
		if ($this->isFtp)
		{
			$details = array();
			$name = '';
			if (is_dir($path))
			{
				if (substr($path, -1) != '/')
					$path .= '/';
				$fixed_path = $this->fixPath($path);
				if ($fixed_path == './')
					$fixed_path = '';
				$details = @$this->ftp->listDetails($fixed_path.'../');

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

		return is_writable($path);
	}


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

