<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

abstract class Patcher_Zip
{
	static function load($type, $options)
	{
		$class = 'Patcher_Zip_'.$type;
		if (!class_exists($class))
		{
			if (!file_exists(PATCHER_ROOT.'Zip/'.$type.'.php'))
				throw new Exception('No such Zip type: '.$type);

			require PATCHER_ROOT.'Zip/'.$type.'.php';
		}

		return new $class($options);
	}

	public $options;

	/**
	 * @var string Path to the file
	 */
	public $file;

	/**
	 * @var ZipArchive Instance of ZipArchive or PclZip object
	 */
	private $zip;

	/**
	 * @var bool Whether or not current file was opened for reading
	 */
	public $isOpen = false;

	/**
	 * Open specified ZIP file
	 *
	 * @param type $file
	 * @param type $create
	 * @return type
	 */
	function open($file)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Create specified ZIP file
	 *
	 * @param type $file
	 * @return type
	 */
	function create($file)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Return list of the files from ZIP file
	 *
	 * @return array
	 */
	function listContent()
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Extract ZIP file to the specified directory
	 *
	 * @param type $extractTo
	 * @return type
	 */
	function extract($extractTo)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Add files to the ZIP archive
	 *
	 * @param array $files
	 * @return bool
	 */
	function add($files)
	{
		throw new Exception('Not implemented');
	}

	/**
	 * Close current ZIP file
	 *
	 * @return bool
	 */
	function close()
	{
		throw new Exception('Not implemented');
	}
}
