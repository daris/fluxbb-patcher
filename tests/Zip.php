<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

define('PUN_ROOT', realpath(dirname(__FILE__).'/../../'));
define('PATCHER_ROOT', realpath(dirname(__FILE__).'/../').'/plugins/patcher/');
require PATCHER_ROOT.'Zip.php';
require PATCHER_ROOT.'FileSystem.php';

abstract class ZipTest extends \PHPUnit_Framework_TestCase
{
	protected static $zip;

	protected static $filename;
	protected static $files;
	protected static $fs;

	public function testCreate()
	{

		self::$filename = '/tmp/'.uniqid().'.zip';
		self::$zip->create(self::$filename);

		self::$files = array(
			'Patcher.php'	=> PATCHER_ROOT.'Patcher.php',
			'Mod.php'		=> PATCHER_ROOT.'Mod.php'
		);
		self::$zip->add(self::$files);

		$this->assertEquals(self::$zip->listContent(), array_keys(self::$files));
		self::$zip->close();
	}

	public function testOpen()
	{
		self::$zip->open(self::$filename);

		$this->assertEquals(self::$zip->listContent(), array_keys(self::$files));
		self::$zip->close();
	}

	public function testExtract()
	{
		global $fs;

		$fs = Patcher_FileSystem::load('Native', array());

		self::$zip->open(self::$filename);

		$fileSize = array();
		foreach (self::$files as $curFile => $curPath)
			$fileSize[$curFile] = filesize($curPath);

		$dir = '/tmp/'.uniqid().'/';
		mkdir($dir);
		self::$zip->extract($dir);
		$fileSizeAfter = array();
		foreach (self::$files as $curFile => $curPath)
			if (file_exists($dir.$curFile))
				$fileSizeAfter[$curFile] = filesize($dir.$curFile);

		$this->assertEquals($fileSize, $fileSizeAfter);

		self::$zip->close();
	}

	public static function tearDownAfterClass()
	{
		unlink(self::$filename);
	}

}
