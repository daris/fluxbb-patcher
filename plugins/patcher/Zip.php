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
	protected $zip;

	/**
	 * @var bool Whether or not current file was opened for reading
	 */
	protected $isOpen = false;

	/**
	 * Open specified ZIP file
	 *
	 * @param type $file
	 * @return type
	 */
	abstract function open($file);

	/**
	 * Create specified ZIP file
	 *
	 * @param type $file
	 * @return type
	 */
	abstract function create($file);

	/**
	 * Return list of the files from ZIP file
	 *
	 * @return array
	 */
	abstract function listContent();

	/**
	 * Extract ZIP file to the specified directory
	 *
	 * @param type $extractTo
	 * @return type
	 */
	abstract function extract($extractTo);

	/**
	 * Add files to the ZIP archive
	 *
	 * @param array $files
	 * @return bool
	 */
	abstract function add($files);

	/**
	 * Close current ZIP file
	 *
	 * @return bool
	 */
	abstract function close();
}
