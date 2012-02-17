<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

class Patcher_RepoMod
{
	/**
	 * @var string Modification id (directory)
	 */
	// public $id;

	/**
	 * @var string Modification title
	 */
	// public $title;

	/**
	 * @var string Modification version
	 */
	// public $version;

	/**
	 * @var string FluxBB versions that this mod is compatible with
	 */
	// public $worksOn;

	/**
	 * @var string URL of the modification repository
	 */
	// public $repositoryUrl;

	/**
	 * @var bool Whether or not modification is valid
	 */
	// public $isValid;

	/**
	 * @var string Modification description
	 */
	// public $description;

	/**
	 * Constructor
	 *
	 * @param type $id
	 * 		Modification ID
	 *
	 * @param type $curMod
	 *
	 * @return type
	 */
	function __construct($id, $curMod)
	{
		$this->id = $id;
		$this->title = $curMod['name'];
		$this->repositoryUrl = sprintf(PATCHER_REPO_MOD_URL, urldecode($this->id));
		$this->isValid = true;
		$this->version = $curMod['last_release']['version'];
		$this->author = $curMod['author'];
		$this->authorEmail = 'http://fluxbb.org/forums/profile.php?id='.$curMod['author_id'];
		$this->worksOn = array_reverse($curMod['last_release']['forum_versions']);
		if (isset($curMod['description']))
			$this->description = $curMod['description'];

		$this->isInstalled = $this->isEnabled = false;
	}

	/**
	 * Check whether modification is compatible with installed FluxBB release
	 *
	 * @return bool
	 */
	function isCompatible()
	{
		global $pun_config;

		if (!isset($this->worksOn))
			return false;

		foreach ($this->worksOn as $curVersion)
		{
			if (strpos($curVersion, '*') !== false && preg_match('/'.str_replace('\*', '*', preg_quote($curVersion)).'/', $pun_config['o_cur_version'])
				|| strpos($curVersion, 'x') !== false && preg_match('/'.str_replace('x', '*', preg_quote($curVersion)).'/', $pun_config['o_cur_version'])
				|| $curVersion == $pun_config['o_cur_version']
				|| $curVersion == substr($pun_config['o_cur_version'], 0, strlen($curVersion))
				|| substr($curVersion, 0, strlen($pun_config['o_cur_version'])) == $pun_config['o_cur_version'])
					return true;
		}

		if (preg_match('#([>=<]+)\s*(.*)#', $this->worksOn[0], $matches))
			return version_compare($pun_config['o_cur_version'], $matches[2], $matches[1]);

		return false;
	}
}


class Patcher_Mod extends Patcher_RepoMod
{
	/**
	 * @var string Modification id (directory)
	 */
	public $id;

	/**
	 * @var string Main readme file directory
	 */
	public $readmeFileDir;

	/**
	 * @var string Main readme file path (variable accessible via __get method)
	 */
//	public $readmeFileName = null;

	/**
	 * @var string Full path to the modification directory
	 */
	public $modDir;

	/**
	 * @var string Main readme file content
	 */
	private $readmeFile;

	/**
	 * @var string List of readme files in current mod directory (including subdirectories)
	 */
//	public $readmeFileList = null;

	/**
	 * @var string Is it a duplicate of the mod, but with the higher version number?
	 */
	public $isModUpdate;

	/**
	 * Constructor
	 *
	 * @param string $id
	 * 		Modification ID
	 *
	 * @return type
	 */
	function __construct($id, $isModUpdate = false)
	{
		$this->id = $id;
		$this->modDir = MODS_DIR.$this->id.'/';
		if (!is_dir($this->modDir) || !isset($this->readmeFileName))
		{
			$this->isValid = false;
			return false;
		}
		$this->readmeFileDir = $this->modDir.dirname($this->readmeFileName);
		$this->readmeFile = file_get_contents($this->modDir.$this->readmeFileName);
		$this->isModUpdate = $isModUpdate;
	}

	/**
	 * Used for: readme_file_list, files_to_upload, upload_code
	 *
	 * @param type $name
	 * @return type
	 */
	function __get($name)
	{
		$function_name = 'get'.ucfirst($name);
		if (!is_callable(array($this, $function_name)))
			return null;

		$this->$name = $this->$function_name();
		return $this->$name;
	}

	function __isset($name)
	{
		$value = $this->$name;
		return isset($value) && !empty($value);
	}

	/**
	 * Determines whether mod is installed or not
	 *
	 * @return bool
	 */
	function getIsInstalled()
	{
		global $patcherConfig;
		return isset($patcherConfig['installed_mods'][$this->id]['version']);
	}

	/**
	 * Determines whether mod is installed and enabled
	 *
	 * @return bool
	 */
	function getIsEnabled()
	{
		global $patcherConfig;
		return $this->isInstalled && !isset($patcherConfig['installed_mods'][$this->id]['disabled']);
	}

	/**
	 * Determines whether there is a new update available to install
	 *
	 * @return string [repo|local] When there is new release or false when no new release
	 */
	function getCanUpdate()
	{
		global $patcherConfig, $modRepo;

		// Look for updates
		if ($this->isInstalled)
		{
			$hasUpdate = array();
			$updateType = false;

			// New update in local copy
			if (isset($patcherConfig['installed_mods'][$this->id]['version']) && version_compare($this->modInfo['mod version'], $patcherConfig['installed_mods'][$this->id]['version'], '>'))
				$hasUpdate['local'] = $this->modInfo['mod version'];

			// New update available to download from fluxbb.org repo
			if (isset($modRepo['mods'][$this->id]['last_release']['version']) && version_compare($modRepo['mods'][$this->id]['last_release']['version'], $patcherConfig['installed_mods'][$this->id]['version'], '>'))
				$hasUpdate['repo'] = $modRepo['mods'][$this->id]['last_release']['version'];

			// Get newest update
			if (isset($hasUpdate['local']) && isset($hasUpdate['repo']))
				$updateType = version_compare($hasUpdate['local'], $hasUpdate['repo'], '>=') ? 'local' : 'repo';
			elseif (isset($hasUpdate['local']))
				$updateType = 'local';
			elseif (isset($hasUpdate['repo']))
				$updateType = 'repo';

			if (!$updateType)
				return false;

			$this->updateVersion = $hasUpdate[$updateType];
			return $updateType;
		}

		// New update available to download from fluxbb.org repo
		else if (isset($modRepo['mods'][$this->id]['last_release']['version']) && version_compare($modRepo['mods'][$this->id]['last_release']['version'], $this->version, '>'))
		{
			$this->updateVersion = $modRepo['mods'][$this->id]['last_release']['version'];
			return 'repo';
		}

		return false;
	}

	/**
	 * Look for readme file name
	 *
	 * @return string
	 */
	function getReadmeFileName()
	{
		if (file_exists(MODS_DIR.$this->id.'/readme.txt'))
			return 'readme.txt';

		if (count($this->readmeFileList) == 1)
			return $this->readmeFileList[0];

		foreach ($this->readmeFileList as $key => $curReadme)
		{
			if (preg_match('/(install|read\s?me|lisezmoi).*?(\.txt)?/i', $curReadme))
				return $curReadme;
		}

		return false;
	}

	/**
	 * Get the readme file list for specified directory
	 *
	 * @param type $dirpath
	 * @param type $subdirectory
	 * @return type
	 */
	function getReadmeFileList($dirpath = '', $subdirectory = true)
	{
		// Load readme file list
		if ($dirpath == '')
			$dirpath = $this->modDir;

		$result = array();
		$dir = dir($dirpath);
		while ($file = $dir->read())
		{
			if (substr($file, 0, 1) != '.')
			{
				if (is_dir($dirpath.'/'.$file))
				{
					if ($subdirectory)
						$result = array_merge($result, $this->getReadmeFileList($dirpath.'/'.$file, false));
				}
				else if ((strpos(strtolower($file), 'read') !== false && strpos(strtolower($file), 'me') !== false || strpos(strtolower($file), 'lisezmoi') !== false) && (strpos(strtolower($file), '.txt') !== false || strpos(strtolower($file), '.') === false))
					$result[] = ltrim(str_replace($this->modDir, '', $dirpath.'/'.$file), '/');
			}
		}
		$dir->close();

		return $result;
	}

	/**
	 * Return array containing modification information
	 *
	 * @return array
	 */
	function getModInfo()
	{
		static $transformations;

		if (!isset($this->readmeFile) || empty($this->readmeFile))
			return array();

		if (!isset($transformations))
		{
			$transformations = array(
				'title'				=> 'mod title',
				'version mod'		=> 'mod version',
				'version'			=> 'mod version',
				'affected file'		=> 'affected files',
				'works on'			=> 'works on fluxbb',
				'works on punbb'	=> 'works on fluxbb', // this should not be here :)
			);
		}

		$file = $this->readmeFile;

		$modInfo = array();

		$file = substr($file, 0, strpos($file, '#--'));
		$file = trim($file, '# '."\n\r\t");

		// Gizzmo's syntax - strip out *****
		$file = preg_replace('#\*{5,}#', '', $file);

		$lines = explode("\n", $file);
		$lastInfo = '';
		foreach ($lines as $line)
		{
			$line = ltrim(trim($line), '#*');
	/*		if ($line == '')
				continue;
	*/
			if (strpos(substr($line, 0, 25), ':') !== false)
			{
				$lastInfo = trim(strtolower(substr($line, 0, strpos($line, ':'))));
				if (isset($transformations[$lastInfo]))
					$lastInfo = $transformations[$lastInfo];
				$modInfo[$lastInfo] = trim(substr($line, strpos($line, ':') + 1));
			}
			elseif ($lastInfo != '')
				$modInfo[$lastInfo] .= "\n".trim($line);
		}

		$this->isValid = isset($modInfo['mod version']);

		return $modInfo;
	}

	/**
	 * Check whether current modification is valid
	 *
	 * @return bool
	 */
	function getIsValid()
	{
		return isset($this->version);
	}

	/**
	 * Get modification author
	 *
	 * @return string
	 */
	function getAuthor()
	{
		if (!isset($this->modInfo['author']))
			return '';

		$author = $this->modInfo['author'];
		if (preg_match('#^(.*?) [\(<](.+)[>\)]#', $author, $m) // name (test@gmail.com)
			|| preg_match('#^([^@]+)@([^@]+\.[^@]+)#', $author, $m)) // test@gmail.com
			$author = $m[1];

		if (($pos = strpos($author, ';')) !== false)
			$author = substr($author, 0, $pos);

		if (($pos = strpos($author, ' - ')) !== false)
			$author = substr($author, 0, $pos);

		return trim($author);
	}

	/**
	 * Get modification author email
	 *
	 * @return string
	 */
	function getAuthorEmail()
	{
		if (!isset($this->modInfo['author']))
			return '';

		if (preg_match('#[\(<](.+)[>\)]#', $this->modInfo['author'], $m) // name (test@gmail.com)
			|| preg_match('#([^@]+@[^@]+\.[^@]+)#', $this->modInfo['author'], $m)) // test@gmail.com
			return trim($m[1]);
	}

	/**
	 * Get modification title
	 *
	 * @return string
	 */
	function getTitle()
	{
		if (!isset($this->modInfo['mod title']))
			return ucfirst(str_replace(array('-', '_'), ' ', $this->id));

		return trim($this->modInfo['mod title']);
	}

	/**
	 * Get modification version
	 *
	 * @return string
	 */
	function getVersion()
	{
		global $patcherConfig, $modRepo;

		// When it is not a mod update object and is installed, return the installed version
		if (!$this->isModUpdate && $this->isInstalled && isset($patcherConfig['installed_mods'][$this->id]['version']))
			return $patcherConfig['installed_mods'][$this->id]['version'];

		// When it is a mod update object and there is a new version available, return update version
		else if ($this->isModUpdate && $this->isInstalled && isset($modRepo['mods'][$this->id]['last_release']['version']) && version_compare($modRepo['mods'][$this->id]['last_release']['version'], $patcherConfig['installed_mods'][$this->id]['version'], '>'))
			return $modRepo['mods'][$this->id]['last_release']['version'];

		else if (!isset($this->modInfo['mod version']))
			return '';

		// else return mod version from readme
		return $this->modInfo['mod version'];
	}

	/**
	 * Get modification description
	 *
	 * @return string
	 */
	function getDescription()
	{
		if (!isset($this->modInfo['description']))
			return '';

		return $this->modInfo['description'];
	}

	/**
	 * Get Affects DB property from the readme file
	 *
	 * @return string
	 */
	function getAffectsDb()
	{
		if (!isset($this->modInfo['affects db']))
			return '';

		return $this->modInfo['affects db'];
	}

	/**
	 * Get Important property from the readme file
	 *
	 * @return string
	 */
	function getImportant()
	{
		if (!isset($this->modInfo['important']))
			return '';

		return $this->modInfo['important'];
	}

	/**
	 * Get modification release date
	 *
	 * @return string
	 */
	function getReleaseDate()
	{
		if (!isset($this->modInfo['release date']))
			return '';

		return $this->modInfo['release date'];
	}

	/**
	 * Get array of the FluxBB versions that modification is compatible with
	 *
	 * @return array
	 */
	function getWorksOn()
	{
		if (!isset($this->modInfo['works on fluxbb']))
			return array();

		$worksOn = str_replace(array(' and ', '.x', '.*'), array(',', ''), $this->modInfo['works on fluxbb']);
		$worksOn = preg_replace('/[^a-zA-Z0-9-\.\*]+/', ',', $worksOn);
		$versions = array_filter(array_map('trim', explode(',', $worksOn)));
		usort($versions, 'version_compare');
		return array_reverse($versions);
	}

	/**
	 * Get repository URL of the modification
	 *
	 * @return string
	 */
	function getRepositoryUrl()
	{
		if (!isset($this->modInfo['repository url']) || strpos($this->modInfo['repository url'], '(Leave unedited)') !== false)
			return '';

		return $this->modInfo['repository url'];
	}

	/**
	 * Get list of the affected files by this modification
	 *
	 * @return array
	 */
	function getAffectedFiles()
	{
		static $transformations;

		if (!isset($transformations))
		{
			$transformations = array(
				'[language]'					=> 'English',
				'your_lang'						=> 'English',
				'[style]'						=> 'Air',
				'your_style'					=> 'Air',
				'Your_style'					=> 'Air',
				'For plugin installation:'		=> '',
			);
		}

		if (!isset($this->modInfo['affected files']))
			return '';

		$files = array();
		$delimiter = (strpos($this->modInfo['affected files'], ', ') !== false) ? ',' : "\n";
		$affectedFiles = explode($delimiter, $this->modInfo['affected files']);
		foreach ($affectedFiles as $curFile)
		{
			// Do some fix for current file :)
			$curFile = str_replace(array_keys($transformations), array_values($transformations), trim($curFile));

			// Delete everything after ( and [ characters
			if (strpos($curFile, ' (') !== false)
				$curFile = substr($curFile, 0, strpos($curFile, ' ('));
			if (strpos($curFile, ' [') !== false)
				$curFile = substr($curFile, 0, strpos($curFile, ' ['));

			// Does not look like a file?
			if (($pos = strrpos($curFile, '.')) === false || $pos < strlen($curFile) - 5 || $pos >= strlen($curFile) - 1)
				continue;

			// Exclude lines that has Null, None or No word
			if (!empty($curFile) && !in_array(strtolower($curFile), array('null', 'none', 'no')))
				$files[] = trim($curFile);
		}

		if (file_exists($this->readmeFileDir.'/patcher.affected_files.php'))
			$files = array_merge($files, require($this->readmeFileDir.'/patcher.affected_files.php'));

		sort($files);
		return array_unique($files);
	}

	/**
	 * Get source code of the Upload step from modification readme file
	 *
	 * @return type
	 */
	function getUploadCode()
	{
		if (strpos($this->readmeFile, 'UPLOAD ]--') === false)
			return false;

		$uploadCode = substr($this->readmeFile, strpos($this->readmeFile, 'UPLOAD ]--'));

		// Mpok's style (first line - English, second - translation)
		if (preg_match('/\]-+\s*\n#-+\[/si', $uploadCode))
			$uploadCode = preg_replace('/(\]-+\r?\n)#-+.*?\n/si', '$1', $uploadCode, 1);

		$uploadCode = substr($uploadCode, strpos($uploadCode, "\n") + 1);
		$uploadCode = substr($uploadCode, 0, strpos($uploadCode, '#--'));
		return trim($uploadCode, '#'."\n\r");
	}

	/**
	 * Get list of the files to upload
	 *
	 * @return array
	 */
	function getFilesToUpload()
	{
		$filesToUpload = array();

		// Get files to upload from mod readme
		if ($this->uploadCode)
		{
			// Mod author was too lazy? :P
			if (preg_match('/(upload.+from|all).+files.+/', strtolower($this->uploadCode)) || preg_match('/(file|all).+folders?/', strtolower($this->uploadCode)))
			{
				if (is_dir($this->readmeFileDir.'/files'))
					$filesToUpload = listFilesToUpload($this->readmeFileDir, 'files');
				else
					$filesToUpload = listFilesToUpload($this->readmeFileDir, '');
			}

			// We have the list of files to upload :)
			else
			{
				$lines = explode("\n", $this->uploadCode);
				foreach ($lines as $line)
				{
					// Remove spaces from start or end of line
					$line = trim($line);

					if ($line == '' || strtoupper($line) == 'OR' || substr($line, 0, 2) == '# ')
						continue;

					if (strpos($line, ' (') !== false)
						$line = substr($line, 0, strpos($line, ' ('));

					// directory/filename.php to directory/filename.php
					if (preg_match('/^([a-zA-Z0-9_\-\(\)\/\.]+).*?\s+to\s+([a-zA-Z0-9_\-\(\)\/\.]+)/', $line, $parts))
					{
						$from = $parts[1];
						$to = $parts[2];
					}

					// Only file name
					elseif (preg_match('/^([a-zA-Z0-9_\-\(\)\/\.]+).*/', $line, $parts))
						$from = $to = $parts[1];

					// Everything else :)
					else
						$from = $to = $line;

					// Some mod uses your_forum_folder or your_forum_file prefix for path
					$to = str_replace(array('/your_forum_folder', '/your_forum_file'), '', $to);

					// We can't the $to variable so it should be / (PUN_ROOT)
					if ($to == '')
						$to = '/';

					// Why should I correct mod author mistakes? :P
					if (!file_exists($this->readmeFileDir.'/'.$from))
					{
						 // Try to find file in files directory
						if (file_exists($this->readmeFileDir.'/files/'.$from))
							$from = 'files/'.$from;

						 // maybe new_files directory?
						elseif (file_exists($this->readmeFileDir.'/new_files/'.$from))
							$from = 'new_files/'.$from;

						// maybe new_files instead of files?
						elseif (file_exists($this->readmeFileDir.'/'.str_replace('files/', 'new_files/', $from)))
							$from = str_replace('files/', 'new_files/', $from);
					}

					// If the current path is a directory, read and add its contents
					if (is_dir($this->readmeFileDir.'/'.$from))
						$filesToUpload = array_merge($filesToUpload, listFilesToUpload($this->readmeFileDir, rtrim($from, '/'), rtrim($to, '/')));
					else
						$filesToUpload[$from] = $to;
				}
			}
		}

		// Look files to upload in the files directory
		elseif (is_dir($this->readmeFileDir.'/files'))
			$filesToUpload = listFilesToUpload($this->readmeFileDir, 'files');

		foreach ($filesToUpload as $from => &$to)
		{
			// Checking that dot character exists in the path is not a good idea for determining file but I don't know better method :)
			if (is_dir(PUN_ROOT.$to) || substr($to, -1) == '/' || strpos(basename($to), '.') === false)
				$to .= (substr($to, -1) == '/' ? '' : '/').basename($from);

			// Strip slash
			$to = ltrim($to, '\\/');

			// Ignore mod installer files
			if (preg_match('/plugins\/.*?\/(mod_config|search_insert|lang\/.*\/mod_admin).php$/', $from))
				unset($filesToUpload[$from]);

			// Do not upload language files when language folder does not exist
			elseif (preg_match('/lang\/(.+?)\//i', $to, $matches) && strtolower($matches[1]) != 'english' && !is_dir(PUN_ROOT.'lang/'.$matches[1]))
				unset($filesToUpload[$from]);

		}

		// Sort by the $from value
		ksort($filesToUpload);
		return $filesToUpload;
	}

	/**
	 * Check modification requirements (and return them as an array)
	 *
	 * @return array
	 */
	function checkRequirements()
	{
		global $langPatcher, $fs;

		$dirsToCheck = array();
		$requirements = array('files_to_upload' => array(), 'directories' => array(), 'affected_files' => array());

		if ($GLOBALS['action'] == 'uninstall')
		{
			foreach ($this->filesToUpload as $from => $to)
			{
				$dir = dirname($to);
				if ($fs->isWritable(PUN_ROOT.$dir))
					$requirements['directories'][$dir] = array(true, $dir, $langPatcher['Found'].', '.$langPatcher['Writable']);
				else
					$requirements['directories'][$dir] = array(false, $dir, $langPatcher['Not writable']);
			}
		}
		elseif (in_array($GLOBALS['action'], array('update', 'install')))
		{
			foreach ($this->filesToUpload as $from => $to)
			{
				if (!file_exists($this->readmeFileDir.'/'.$from))
					$requirements['files_to_upload'][] = array(false, $from, $langPatcher['Not exists']);

				$curDir = $to;
				// Checking that dot character exists in the path is not a good idea for determining file but I don't know better method :)
				if (strpos($to, '.') !== false)
					$curDir = dirname($curDir);

				// Add directory if it was not added earlier
				if (!in_array($curDir, $dirsToCheck))
					$dirsToCheck[] = $curDir;
			}

			sort($dirsToCheck);
			foreach ($dirsToCheck as $curDirToCheck)
			{
				if (!is_dir(PUN_ROOT.$curDirToCheck))
				{
					$directories = explode('/', $curDirToCheck);
					$curPath = '';
					foreach ($directories as $curDir)
					{
						$curPath .= $curDir.'/';

						// Attempt to create directory
						if (!is_dir(PUN_ROOT.$curPath))
						{
							if (@$fs->mkdir(PUN_ROOT.$curPath))
							{
								if ($fs->isWritable(PUN_ROOT.$curPath))
									$requirements['directories'][] = array(true, $curPath, $langPatcher['Created'].', '.$langPatcher['Writable']);
								else
									$requirements['directories'][] = array(false, $curPath, $langPatcher['Created'].', '.$langPatcher['Not writable']);
							}
							else
								$requirements['directories'][] = array(false, $curPath, $langPatcher['Can\'t create']);
						}
					}
				}

				// Check whether directory is writable
				else
				{
					if ($fs->isWritable(PUN_ROOT.$curDirToCheck))
						$requirements['directories'][] = array(true, $curDirToCheck, $langPatcher['Found'].', '.$langPatcher['Writable']);
					else
						$requirements['directories'][] = array(false, $curDirToCheck, $langPatcher['Not writable']);
				}
			}
		}

		if (count($this->affectedFiles) > 0)
		{
			foreach ($this->affectedFiles as $curFile)
			{
				// Language file that is not English does not exist?
				if (!file_exists(PUN_ROOT.$curFile) && strpos(strtolower($curFile), 'lang/') !== false && strpos(strtolower($curFile), '/english') === false)
					continue;

				$error = '';
				if (!file_exists(PUN_ROOT.$curFile))
					$error = $langPatcher['Not exists'];
				elseif (!$fs->isWritable(PUN_ROOT.$curFile))
					$error = $langPatcher['Not writable'];

				if (empty($error))
					$requirements['affected_files'][] = array(true, $curFile, $langPatcher['Found'].', '.$langPatcher['Writable']);
				else
					$requirements['affected_files'][] = array(false, $curFile, $error);
			}
		}

		// Check whether there are no any unmet requirements
		foreach ($requirements as &$curRequirements)
		{
			ksort($curRequirements);
			foreach ($curRequirements as $curRequirement)
			{
				if (!$curRequirement[0])
				{
					$requirements['failed'] = true;
					break;
				}
			}

			if (isset($requirements['failed']))
				break;
		}

		return $requirements;
	}

	/**
	 * Get all steps from specified readme file
	 *
	 * @param type $readmeFile
	 * 		Path to the readme file
	 *
	 * @return array
	 */
	function getSteps($readmeFile = null)
	{
		static $commandTransformations;

		if (!isset($commandTransformations))
		{
			$commandTransformations = array(
				'AFTER ADD'					=> array('ADD AFTER', 'AFTER INSERT'),
				'BEFORE ADD'				=> array('ADD BEFORE'),
				'OPEN'						=> array('OPEN FILE'),
				'FIND'						=> array('FIND LINE', 'SEARCH', 'GO TO LINE'),
				'AT THE END OF FILE ADD'	=> array('ADD AT THE BOTTOM OF THE FILE', 'ADD AT THE BOTTOM OF THE FUNCTION', 'AT THE END ADD', 'PLACE AT END OF THE FILE'),
				'IN THIS LINE FIND' 		=> array('IN THE SAME LINE FIND', 'IN THESE LINES FIND', 'EVER IN THESE LINES FIND'),
				'NOTE'						=> array('VISIT', 'NOTES'),
				'UPLOAD'					=> array('UPLOAD THE CONTENT OF', 'SEND ON THE SERVER TO THE ROOT OF THE FORUM'),
				'RUN'						=> array('LAUNCH'),
			);
		}

		if ($readmeFile == null)
			$readme = $this->readmeFile;
		else
			$readme = file_get_contents(MODS_DIR.$this->id.'/'.$readmeFile);

		$readme = substr($readme, strpos($readme, '#--'));

		// Mpok's style (first line - English, second - translation)
		if (preg_match('/\]-+\s*\n#-+\[/si', $readme))
			$readme = preg_replace('/(\]-+\r?\n)#-+.*?\n/si', '$1', $readme);

		// Convert EOL to Unix style
		$readme = str_replace("\r\n", "\n", $readme);

		$readme .= '#--';
		$doInlineFind = false;

		$steps = array();

		while (($pos = strpos($readme, '#--')) !== false)
		{
			$readme = substr($readme, $pos + 3);

			// We've reached end of file
			if (trim($readme) == '')
				break;

			if (($pos = strpos($readme, '#--')) !== false)
				$curStep = substr($readme, 0, $pos);

			$curStep = substr($curStep, strpos($curStep, '[') + 1);
//			$curStep = substr($curStep, strpos($curStep, '.') + 1); // +1 = dot
			$curCommand = substr($curStep, 0, strpos($curStep, ']') - 1);

			$curInfo = null;
			if (($pos = strpos($curCommand, '(')) !== false)
			{
				$curInfo = substr($curCommand, $pos + 1);
				$curInfo = substr($curInfo, 0, strpos($curInfo, ')'));
				$curCommand = substr($curCommand, 0, strpos($curCommand, '('));
			}

			if (($pos = strpos($curCommand, '.')) !== false)
				$curCommand = substr($curCommand, $pos + 1);

			$curCommand = trim(preg_replace('/[^A-Z\s]/', '', strtoupper($curCommand)));

			if (empty($curCommand))
				continue;

			// REPLACE WITH command for example
			if (strpos($curCommand, 'REPLACE') !== false)
				$curCommand = 'REPLACE';

			foreach ($commandTransformations as $newCommand => $commandsToFix)
			{
				if (in_array($curCommand, $commandsToFix))
				{
					$curCommand = $newCommand;
					break;
				}
			}

			if (!$doInlineFind && $curCommand == 'IN THIS LINE FIND')
				$doInlineFind = true;

			// We don't want SAVE and END commands
			if (strpos($curCommand, 'SAVE') !== false || $curCommand == 'END')
				continue;

			$curCode = substr($curStep, strpos($curStep, "\n") + 1);

			// Gizzmo's syntax - strip out ***** at end
			$curCode = preg_replace('#\*{5,}$#', '', $curCode);

			// Remove blank string after # at start and at end
			$curCode = preg_replace('#^\#[ \r\t]*#', '', $curCode);
			$curCode = preg_replace('#\s*\#\s*$#s', '', $curCode);

			// Empty lines at start and at end
			$curCode = preg_replace('#^\n*[ \t]*\n+#', '', $curCode);
			$curCode = preg_replace('#\n+[ \t]*\n*$#', '', $curCode);

			if ($curCommand == 'OPEN')
			{
				$curCode = str_replace(array('[language]', 'your_language'), 'English', $curCode);
				$curCode = str_replace(array('[style]', 'Your_style'), 'Air.css', $curCode);
				$curCode = ltrim(trim($curCode), '/');

				if (!file_exists(PUN_ROOT.$curCode) && preg_match('#[a-zA-Z0-9-_\/\\\\]+\.php#i', $curCode, $matches) && file_exists(PUN_ROOT.$matches[0]))
					$curCode = $matches[0];
			}
			elseif ($curCommand == 'NOTE')
			{
				if (strpos(strtolower($curCode), 'launch mod installer') !== false)
					continue;

				if (isset($curInfo) && strpos($curInfo, 'server') !== false && isset($_SERVER['SERVER_SOFTWARE']))
				{
					$serverSoft = $_SERVER['SERVER_SOFTWARE'];
					if (strpos($serverSoft, '/') !== false)
						$serverSoft = substr($serverSoft, 0, strpos($serverSoft, '/'));
					if (strpos(strtolower($curInfo), 'for '.strtolower($serverSoft).' server') === false)
						continue;
				}
			}

			$newStep = array('command' => $curCommand);
			if ($curCommand == 'NOTE')
				$newStep['result'] = $curCode;
			else
				$newStep['code'] = $curCode;

			if (isset($curInfo))
				$newStep['info'] = $curInfo;
			$steps[] = $newStep;
		}

		$steps = array_merge($steps, $this->getModInstallerSteps());

		// Correct action IN THIS LINE FIND
		if ($doInlineFind)
			$steps = $this->fixInlineFindActions($steps);

		return $steps;
	}

	/**
	 * Change the IN THIS LINE FIND actions to the FIND => REPLACE structure
	 *
	 * @param array $steps
	 * @return array
	 */
	function fixInlineFindActions($steps)
	{
		$find = $inlineFind = $inlineReplace = '';
		$lastFindKey = 0;

		foreach ($steps as $key => $curStep)
		{
			if ($curStep['command'] == 'OPEN')
				$inlineFind = '';
			elseif ($curStep['command'] == 'FIND')
			{
				if ($inlineReplace != '')
					$steps[$lastFindKey + 1] = array('command' => 'REPLACE', 'code' => $inlineReplace);

				$find = $curStep['code'];
				$inlineFind = $inlineReplace = '';
				$lastFindKey = $key;
			}
			elseif ($curStep['command'] == 'IN THIS LINE FIND')
			{
				if ($inlineReplace == '')
					$inlineReplace = $find;
				else
					unset($steps[$key]);

				$inlineFind = trim($curStep['code'], "\t");
			}
			elseif ($curStep['command'] == 'AFTER ADD' && $inlineFind != '')
			{
				$inlineReplace = str_replace($inlineFind, $inlineFind.trim($curStep['code'], "\t"), $inlineReplace);
				unset($steps[$key]);
			}
			elseif ($curStep['command'] == 'REPLACE' && $inlineFind != '')
				$inlineReplace = str_replace($inlineFind, $inlineFind.$inlineReplace, $inlineReplace);
		}

		// Fix section numbering
		return array_values($steps);
	}

	/**
	 * Get steps for the ModInstaller (search_inser.php and update_install.php files in plugins directory)
	 *
	 * @return type
	 */
	function getModInstallerSteps()
	{
		// Support for mod installer
		$pluginsDir = null;
		if (is_dir($this->readmeFileDir.'/plugins/'))
			$pluginsDir = $this->readmeFileDir.'/plugins/';
		elseif (is_dir($this->readmeFileDir.'/files/plugins/'))
			$pluginsDir = $this->readmeFileDir.'/files/plugins/';
		else
			return array();

		$d = dir($pluginsDir);
		while ($f = $d->read())
		{
			if (substr($f, 0, 1) == '.')
				continue;

			// Mod installer
			if (is_dir($pluginsDir.'/'.$f) && file_exists($pluginsDir.'/'.$f.'/search_insert.php'))
			{
				require $pluginsDir.'/'.$f.'/search_insert.php';
				$list_files = array();
				$list_base = array();
				// Do not modify the order below, otherwise some mods cannot be installed
				// 1st files_to_insert - 2nd files_to_add - 3rd files_to_replace - 4th files_to_move
				if (isset($files_to_insert))
					$list_files[] = "files_to_insert";
				if (isset($files_to_add))
					$list_files[] = "files_to_add";
				if (isset($files_to_replace))
					$list_files[] = "files_to_replace";
				if (isset($files_to_move))
				{
					$list_files[] = "files_to_move";
					$move_start = "//modif oto - mod "/*.$mod_config['mod_name']*/." - Beginning of the block moved\n";
					$move_end = "//modif oto - mod "/*.$mod_config['mod_name']*/." - End of the block moved\n";
				}
				// Database to modify
				if (isset($fields_to_add))
					$list_tables[] = "fields_to_add";
				if (isset($config_to_insert))
					$list_tables[] = "config_to_insert";

				// is there database modifications to do?
				if (!empty($list_tables))
				{
					$code_array = array();
					global $db;

					foreach ($list_tables as $base_name)
					{
						foreach($$base_name as $table_value)
						{//$table_value is name of table for modifications
							if ($base_name == "fields_to_add")
							{
								for ($i =0 ; $i < count($add_field_name[$table_value]); $i++)
									// If the field already exist there is no error.
									$code_array[] = '$db->add_field(\''.$table_value.'\', \''.$add_field_name[$table_value][$i].'\', \''.$add_field_type[$table_value][$i].'\', \''.$add_allow_null[$table_value][$i].'\', \''.$add_default_value[$table_value][$i].'\') or error(\'Unable to add column '.$add_field_name[$table_value][$i].' to table '.$table_value.'\', __FILE__, __LINE__, $db->error());';
							}
							else if ($base_name == "config_to_insert")
							{
								$sql = "REPLACE INTO `".$db->prefix.$table_value."` (`conf_name`, `conf_value`) VALUES ";
								for ($i = 0; $i < count($values[$table_value]); $i = $i + 2)
									$sql .= "(\'".$db->escape($values[$table_value][$i])."\', \'".$db->escape($values[$table_value][$i+1])."\'),";

								$sql = substr($sql, 0, -1);
								$code_array[] = '$db->query(\''.$sql.'\') or error(\'Unable to INSERT values INTO '.$table_value.'\', __FILE__, __LINE__, $db->error());';
							}
						}
					}
					$steps[] = array('action' => 'RUN CODE', 'code' => 'if ($this->install)'."\n{\n".implode("\n", $code_array)."\n}\n");
				}

				foreach ($list_files as $file_name)
				{
					foreach ($$file_name as $file_value)
					{
						$steps[] = array('command' => 'OPEN', 'code' => $file_value);

						list($name_file,$ext_file) = explode('.',$file_value);

						if ($file_name == "files_to_insert")
						{
							//Inserting the code before an existing line.
							for($i = 0; $i < count($insert_file[$name_file]); $i++)
							{
								$steps[] = array('command' => 'FIND', 'code' => $search_file[$name_file][$i]);
								$steps[] = array('command' => 'BEFORE ADD', 'code' => $insert_file[$name_file][$i]);
							}
						}
						elseif ($file_name == "files_to_add")
						{
							//Adding the code after an existing line.
							for($i = 0; $i < count($insert_add_file[$name_file]); $i++)
							{
								$steps[] = array('command' => 'FIND', 'code' => $search_add_file[$name_file][$i]);
								$steps[] = array('command' => 'AFTER ADD', 'code' => $insert_add_file[$name_file][$i]);
							}
						}
						elseif ($file_name == "files_to_replace")
						{
							//Replacing an existing code by another one.
							for($i=0; $i < count($insert_replace_file[$name_file]); $i++)
							{
								$steps[] = array('command' => 'FIND', 'code' => $search_replace_file[$name_file][$i]);
								$steps[] = array('command' => 'REPLACE', 'code' => $insert_replace_file[$name_file][$i]);
							}
						}
						// currently unsupported
/*						elseif ($file_name == "files_to_move")
						{
							// Move code between two lines to another location
							for ($i=0; $i < count($move_get_start[$name_file]); $i++)
							{
								$pos_start = strpos($file_content, $move_get_start[$name_file][$i]) + strlen($move_get_start[$name_file][$i]);
								$pos_end = strpos($file_content, $move_get_end[$name_file][$i]);
								$move_string = substr($file_content, $pos_start, $pos_end - $pos_start);

								$searching[] = $move_get_start[$name_file][$i].$move_string.$move_get_end[$name_file][$i];
								$replacement[] = $move_get_start[$name_file][$i].$move_get_end[$name_file][$i];
								$searching[] = $move_to_start[$name_file][$i].$move_to_end[$name_file][$i];
								$replacement[] = $move_to_start[$name_file][$i].$move_start.$move_string.$move_end.$move_to_end[$name_file][$i];
							}
						}*/
					}
				}
			}
			// Mod installer
			if (is_dir($pluginsDir.'/'.$f) && file_exists($pluginsDir.'/'.$f.'/update_install.php'))
			{
				$code = 'if ($this->install)'."\n{\n?>".file_get_contents($pluginsDir.'/'.$f.'/update_install.php')."<?php\n".'}';
				if (file_exists($pluginsDir.'/'.$f.'/update_uninstall.php'))
					$code .= "\n\n".'if ($this->uninstall)'."\n{\n?>".file_get_contents($pluginsDir.'/'.$f.'/update_uninstall.php')."<?php\n".'}';

				$code = str_replace('?><?php', '', $code);
				$steps[] = array('command' => 'RUN CODE', 'code' => $code);
			}
		}

		return $steps;
	}
}
