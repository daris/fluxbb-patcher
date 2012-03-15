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
	 * @var JFTP Joomla FTP class instance
	 */
	public $ftp;

	/**
	 * @var string Relative path to the FluxBB directory
	 */
	public $root;

	/**
	 * @var string Real path to the FluxBB root directory
	 */
	public $rootRealPath;

	function __construct($options = null)
	{
		$this->options = $options;
		$this->rootRealPath = str_replace('\\', '/', realpath(PUN_ROOT));
		$this->root = $this->options['path'];
	}

	/**
	 * Connect to the FTP server (only when in FTP mode)
	 *
	 * @return type
	 */
	function getFTP()
	{
		if (!is_object($this->ftp))
		{
			if (empty($this->options['host']))
				throw new Exception('You have to define FTP host in config file');

			if (empty($this->options['port']))
				throw new Exception('You have to define FTP port in config file');

			if (empty($this->options['user']))
				throw new Exception('You have to define FTP username in config file');

			if (empty($this->options['pass']))
				throw new Exception('You have to define FTP password in config file');

			if (empty($this->options['path']))
				throw new Exception('You have to define FTP path in config file');

			require_once PATCHER_ROOT.'FileSystem/FTP/FTP.php';

			$this->ftp = new JFTP();
			if (!$this->ftp->connect($this->options['host'], $this->options['port']))
				throw new Exception('FTP: Connection failed');

			if (!$this->ftp->login($this->options['user'], $this->options['pass']))
				throw new Exception('FTP: Login failed');

			if (!$this->ftp->chdir($this->options['path']))
				throw new Exception('FTP: Directory "'.$this->options['path'].'" does not exist');

			if (!@$this->ftp->listDetails($this->relativePath('config.php')))
				throw new Exception('FTP: The FluxBB root directory is not valid');
		}

		return $this->ftp;
	}

	/**
	 * Return path relative to the PUN_ROOT directory
	 *
	 * @param string $path
	 * @return string
	 */
	function relativePath($path)
	{
		if (file_exists($path))
		{
			$path = str_replace('\\', '/', realpath($path));
			$rootPath = $this->rootRealPath;
		}
		else
			$rootPath = PUN_ROOT;

		$len = strlen($rootPath);
//		echo '<br />'.__FUNCTION__.'<br />path = '.$path.'<br />root = '.$rootPath.'<br />';

		// Root directory?
		if ($rootPath == $path)
			return '.';

		// Is the current path prefixed with PUN_ROOT directory?
		elseif (substr($path, 0, $len) == $rootPath)
			return ltrim(substr($path, $len), '/\\');

		elseif (substr($rootPath, 0, strlen($path)) == $path)
		{
			$pathRest = substr($rootPath, strlen($path));
			return str_repeat('../', substr_count($pathRest, '/'));
		}

		return '.';
	}

	/**
	 * Create directory
	 *
	 * @param type $pathname
	 * @return type
	 */
	function mkdir($pathname)
	{
		return $this->getFTP()->mkdir($this->relativePath($pathname));
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
		$realSrcPath = str_replace('\\', '/', realpath($src));

		// File is already on the FTP server (eg. in fluxbb cache directory) so move it to another location
		if (substr($realSrcPath, 0, strlen($this->rootRealPath)) == $this->rootRealPath)
			return $this->getFTP()->rename($this->relativePath($src), $this->relativePath($dest));

		// We have to upload file to the FTP server
		else
			return $this->getFTP()->store($src, $this->relativePath($dest)) && unlink($src);
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
		return $this->getFTP()->store($src, $this->relativePath($dest));
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
		return $this->getFTP()->write($this->relativePath($file), $data);
	}

	/**
	 * Delete file
	 *
	 * @param type $file
	 * @return type
	 */
	function delete($file)
	{
		return $this->getFTP()->delete($this->relativePath($file));
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
				$this->getFTP()->delete($this->relativePath($curFile));
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
		$path = str_replace('\\', '/', $path);
		if (is_dir($path))
		{
			$dir = rtrim($path, '/').'/';
			$filename = uniqid();
			$ftpFile = $this->relativePath($dir).'/'.$filename;
			$this->getFTP()->write($ftpFile, '');
			$isWritable = file_exists($dir.$filename);

			if (!$this->delete($ftpFile))
				throw new Exception('FTP: Cannot delete temporary file ('.$ftpFile.'). Do you have delete permissions?');

			return $isWritable;
		}
		else
		{
			$details = $this->getFTP()->listDetails($this->relativePath($path));
			$name = $this->relativePath($path);

			$rights = $details[0]['rights'];

			// Is not a file?
			if (substr($rights, 0, 1) != '-')
				return false;

			// TODO: real permissions checking
			if (substr($rights, 2, 1) == 'w')
				return true;
		}
	}
}

