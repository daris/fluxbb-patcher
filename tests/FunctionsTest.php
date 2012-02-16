<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

define('PATCHER_ROOT', dirname(__FILE__).'/../plugins/patcher/');
require_once PATCHER_ROOT.'functions.php';

class FunctionsTest extends PHPUnit_Framework_TestCase
{
	public function test_replaceQuery()
	{
		$first  = '\'SELECT u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online FROM \'.$db->prefix.\'posts AS p INNER JOIN \'.$db->prefix.\'users AS u ON u.id=p.poster_id INNER JOIN \'.$db->prefix.\'groups AS g ON g.g_id=u.group_id LEFT JOIN \'.$db->prefix.\'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.id IN (\'.implode(\',\', $post_ids).\') ORDER BY p.id\', true';
		$second = '\'SELECT u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, p.user_agent, g.g_id, g.g_user_title, o.user_id AS is_online FROM \'.$db->prefix.\'posts AS p INNER JOIN \'.$db->prefix.\'users AS u ON u.id=p.poster_id INNER JOIN \'.$db->prefix.\'groups AS g ON g.g_id=u.group_id LEFT JOIN \'.$db->prefix.\'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.id IN (\'.implode(\',\', $post_ids).\') ORDER BY p.id\', true';

//		echo replaceQuery($first, $second)."\n\n";
		$res    = '\'SELECT u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online, p.user_agent FROM \'.$db->prefix.\'posts AS p INNER JOIN \'.$db->prefix.\'users AS u ON u.id=p.poster_id INNER JOIN \'.$db->prefix.\'groups AS g ON g.g_id=u.group_id LEFT JOIN \'.$db->prefix.\'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.id IN (\'.implode(\',\', $post_ids).\') ORDER BY p.id\', true';
		$this->assertEquals($res, replaceQuery($first, $second));
	}
}
//
//$t = new PatcherTest();
//$t->test_replaceQuery();
