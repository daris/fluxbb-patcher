<?php

if (!function_exists('sys_get_temp_dir'))
{
	function sys_get_temp_dir()
	{
		 // Try to get from environment variable
		if (!empty($_ENV['TMP']))
			return realpath($_ENV['TMP']);
		elseif (!empty($_ENV['TMPDIR']))
			return realpath($_ENV['TMPDIR']);
		elseif (!empty($_ENV['TEMP']))
			return realpath( $_ENV['TEMP'] );

		// Detect by creating a temporary file
		else
		{
			// Try to use system's temporary directory as random name shouldn't exist
			$temp_file = tempnam(md5(uniqid(rand(), TRUE)), '');
			if ($temp_file)
			{
				$temp_dir = realpath(dirname($temp_file));
				unlink($temp_file);
				return $temp_dir;
			}
		}
		return false;
	}
}


class FILESYSTEM
{
	var $ftp;
	var $root = PUN_ROOT;
	var $is_ftp = false;
	var $ftp_data = array();
	var $is_connected = false;

	function __construct($ftp_data = null)
	{
		$this->tmp = @sys_get_temp_dir();
		if (!$this->tmp)
			$this->tmp = FORUM_CACHE_DIR; // fallback - this directory is writable for sure :)

		if (isset($ftp_data) && is_array($ftp_data))
		{
			$this->is_ftp = true;
			$this->ftp_data = $ftp_data;
		}
	}
	
	function tmpname()
	{
		return realpath($this->tmp).'/'.md5(time().rand());
	}
	
	
	function check_connection()
	{
		if (!$this->is_ftp || $this->is_connected)
			return;
		
		require_once PATCHER_ROOT.'ftp.class.php';

		$this->ftp = new JFTP();
		if (!$this->ftp->connect($this->ftp_data['host'], $this->ftp_data['port']))
			error('FTP: Connection failed', __FILE__, __LINE__);
		if (!$this->ftp->login($this->ftp_data['user'], $this->ftp_data['pass']))
			error('FTP: Login failed', __FILE__, __LINE__);
		if (!$this->ftp->chdir($this->ftp_data['path']))
			error('FTP: Directory change failed', __FILE__, __LINE__);
		
		if (!@$this->ftp->listDetails($this->fix_path('config.php')) || !@$this->ftp->listDetails($this->fix_path('admin_index.php')))
			error('FTP: The FluxBB root directory is not valid', __FILE__, __LINE__);

		$this->root = $this->ftp_data['path'];
		
		$this->is_connected = true;
	}
	
	function fix_path($path)
	{
		if (substr($path, 0, strlen(PUN_ROOT)) == PUN_ROOT)
			return ltrim(substr($path, strlen(PUN_ROOT)), '/');
		return $path;
	}
	
	
	function mkdir($pathname)
	{
		$this->check_connection();
		return $this->is_ftp ? $this->ftp->mkdir($this->fix_path($pathname)) : mkdir($pathname);
	}
	
	function move($src, $dest)
	{
		$this->check_connection();
		return $this->is_ftp ? $this->ftp->store($src, $this->fix_path($dest)) && unlink($src) : rename($src, $dest);
	}
	
	function copy($src, $dest)
	{
		$this->check_connection();
		return $this->is_ftp ? $this->ftp->store($src, $this->fix_path($dest)) : copy($src, $dest);
	}

	function put($file, $data)
	{
		$this->check_connection();
		return $this->is_ftp ? $this->ftp->write($this->fix_path($file), $data) : file_put_contents($file, $data);
	}
	
	function delete($file)
	{
		$this->check_connection();
		if ($this->is_ftp) 
			return $this->ftp->delete($this->fix_path($file));
		
		return unlink($file);
	}
	
	// Recursive directory remove
	function remove_directory($path)
	{
		if (!is_dir($path))
			return true;
		
		$this->check_connection();
		if ($this->is_ftp) 
			return $this->ftp->delete($this->fix_path($file));
		
		$files = array();
		$d = dir($path);
		while ($f = $d->read())
		{
			if ($f != '.' && $f != '..')
			{
				if (is_file($path.'/'.$f))
					$files[] = $path.'/'.$f;
			}
		}
		$d->close();
		
		// It files aren't writable the rest of this function will not be executed
		$this->are_files_writable($files);
		
		foreach ($files as $cur_file)
			$this->delete($cur_file);

		$d = dir($path);
		while ($f = $d->read())
		{
			if ($f != '.' && $f != '..')
			{
				if (is_dir($path.'/'.$f))
					$this->remove_directory($path.'/'.$f);
			}
		}
		$d->close();

		return rmdir($path);
	}
	
	
	function copy_directory($source, $dest)
	{
		if (!is_dir($dest))
			$this->mkdir($dest);

		$d = dir($source);
		while ($f = $d->read())
		{
			if ($f != '.' && $f != '..' && $f != '.git' && $f != '.svn')
			{
				if (is_dir($source.'/'.$f))
					$this->copy_directory($source.'/'.$f, $dest.'/'.$f);
				else
					$this->copy($source.'/'.$f, $dest.'/'.$f);
			}
		}
		$d->close();
		return true;
	}
	
	function is_empty_directory($dir)
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

	
	function is_writable($path)
	{
		$this->check_connection();
		if ($this->is_ftp)
		{
			$details = array();
			$name = '';
			if (is_dir($path))
			{
				if (substr($path, -1) != '/')
					$path .= '/';
				$details = @$this->ftp->listDetails($this->fix_path($path).'../');
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
				$details = $this->ftp->listDetails($this->fix_path($path));
				$name = $this->fix_path($path);

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
	

	function are_files_writable($files)
	{
		global $lang_admin_plugin_patcher;

		$not_writable = array();
		foreach ($files as $cur_file)
		{
			if (!$this->is_writable($cur_file))
				$not_writable[] = $cur_file;
			if (!$this->is_writable(dirname($cur_file)))
				$not_writable[] = dirname($cur_file);
		}

		if (count($not_writable) > 0)
			message($lang_admin_plugin_patcher['Directories not writable info'].'<br />'.implode('<br />', $not_writable));
		return true;
	}
}

