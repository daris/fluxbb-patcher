<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

require_once dirname(__FILE__).'/../Zip.php';

class ZipTest_Native extends ZipTest
{
	public static function setUpBeforeClass()
	{
		self::$zip = Patcher_Zip::load('Native', array());
	}
}
