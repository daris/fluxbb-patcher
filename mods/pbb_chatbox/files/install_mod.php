<?php
/***********************************************************************/

// Some info about your mod.
$mod_title      = 'Poki BB ChatBox';
$mod_version    = '2.0.1';
$release_date   = '2010-08-11';
$author         = 'Daris';
$author_email   = 'daris91@gmail.com';

// Versions of FluxBB this mod was created for. A warning will be displayed, if versions do not match
$fluxbb_versions= array('1.4.2', '1.4.1', '1.4.0', '1.4-rc3');

// Set this to false if you haven't implemented the restore function (see below)
$mod_restore	= true;


// This following function will be called when the user presses the "Install" button
function install()
{
	global $db, $db_type, $pun_config;

		$schema_messages = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'poster'		=> array(
							'datatype'			=> 'VARCHAR(200)',
							'allow_null'		=> true,
							'default'		=> 'NULL'
					),
					'poster_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'			=> 1
					),
					'poster_ip'	=> array(
							'datatype'			=> 'VARCHAR(15)',
							'allow_null'		=> true,
							'default'		=> 'NULL'
					),
					'poster_email'	=> array(
							'datatype'		=> 'VARCHAR(50)',
							'allow_null'	=> true,
							'default'		=> 'NULL'
					),
					'message'	=> array(
							'datatype'			=> 'TEXT',
							'allow_null'		=> false
					),
					'posted'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					)
			),
			'PRIMARY KEY'		=> array('id'),
	);
	$db->create_table('chatbox_msg', $schema_messages) or error('Unable to create table "chatbox_msg"', __FILE__, __LINE__, $db->error());
	
	
	$db->add_field('groups', 'g_read_chatbox', 'TINYINT(1)', false, '1');
	$db->add_field('groups', 'g_post_chatbox', 'TINYINT(1)', false, '1');
	$db->add_field('groups', 'g_title_chatbox', 'VARCHAR(200)', true);
	$db->add_field('groups', 'g_post_flood_chatbox', 'SMALLINT(6)', false, '5');
	
	$db->add_field('users', 'num_posts_chatbox', 'INT(10)', false, '0');
	$db->add_field('users', 'last_post_chatbox', 'INT(10)', true);

	$chatbox_config = array(
		'cb_height'			=> '400',
		'cb_msg_maxlength'	=> '300',
		'cb_max_msg'		=> '30',
		'cb_disposition'	=> '<strong><pun_username></strong> - <pun_date> - [ <pun_nbpost><pun_nbpost_txt> ] <pun_admin><br /><pun_message>',
		'cb_ajax_refresh'	=> '5',
		'cb_ajax_errors'	=> '<strong>[<pun_error>]</strong> - <pun_date><br /><pun_error_text>',
		'cb_space'			=> '<br />',
		'cb_pbb_version'	=> '2.1'
	);
	foreach($chatbox_config AS $key => $value)
		$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES (\''.$key.'\', \''.$db->escape($value).'\')') or error('Unable to add column "'.$key.'" to config table', __FILE__, __LINE__, $db->error());
		
	$db->query('UPDATE '.$db->prefix.'groups SET g_title_chatbox=\'<strong>[Admin]</strong>&nbsp;-&nbsp;\', g_read_chatbox=1, g_post_chatbox=1, g_post_flood_chatbox=0 WHERE g_id=1') or error('Unable to update group', __FILE__, __LINE__, $db->error());
	$db->query('UPDATE '.$db->prefix.'groups SET g_title_chatbox=\'<strong>[Mod]</strong>&nbsp;-&nbsp;\', g_read_chatbox=1, g_post_chatbox=1, g_post_flood_chatbox=0 WHERE g_id=2') or error('Unable to update group', __FILE__, __LINE__, $db->error());
	$db->query('UPDATE '.$db->prefix.'groups SET g_read_chatbox=1, g_post_chatbox=0, g_post_flood_chatbox=10 WHERE g_id=3') or error('Unable to update group', __FILE__, __LINE__, $db->error());
	$db->query('UPDATE '.$db->prefix.'groups SET g_read_chatbox=1, g_post_chatbox=1, g_post_flood_chatbox=5 WHERE g_id=4') or error('Unable to update group', __FILE__, __LINE__, $db->error());

	// and now, update the cache...
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();
}

// This following function will be called when the user presses the "Restore" button (only if $mod_restore is true (see above))
function restore()
{
	global $db, $db_type, $pun_config;

	$db->drop_table('chatbox_msg');
	
	$db->drop_field('groups', 'g_read_chatbox');
	$db->drop_field('groups', 'g_post_chatbox');
	$db->drop_field('groups', 'g_title_chatbox');
	$db->drop_field('groups', 'g_post_flood_chatbox');
	
	$db->drop_field('users', 'num_posts_chatbox');
	$db->drop_field('users', 'last_post_chatbox');

	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name IN(\'cb_height\', \'cb_msg_maxlength\', \'cb_max_msg\', \'cb_disposition\', \'cb_ajax_refresh\', \'cb_ajax_errors\', \'cb_space\', \'cb_pbb_version\')') or error('Unable to add column "'.$key.'" to config table', __FILE__, __LINE__, $db->error());
	
	// and now, update the cache...
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();
}

/***********************************************************************/

// DO NOT EDIT ANYTHING BELOW THIS LINE!


// Circumvent maintenance mode
define('PUN_TURN_OFF_MAINT', 1);
define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

// Make sure we are running a FluxBB version that this mod works with
$version_warning = !in_array($pun_config['o_cur_version'], $fluxbb_versions);

$style = (isset($pun_user)) ? $pun_user['style'] : $pun_config['o_default_style'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo pun_htmlspecialchars($mod_title) ?> installation</title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $style.'.css' ?>" />
</head>
<body>

<div id="punwrap">
<div id="puninstall" class="pun" style="margin: 10% 20% auto 20%">

<?php

if (isset($_POST['form_sent']))
{
	if (isset($_POST['install']))
	{
		// Run the install function (defined above)
		install();

?>
<div class="block">
	<h2><span>Installation successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully prepared for <?php echo pun_htmlspecialchars($mod_title) ?>. See readme.txt for further instructions.</p>
		</div>
	</div>
</div>
<?php

	}
	else
	{
		// Run the restore function (defined above)
		restore();

?>
<div class="block">
	<h2><span>Restore successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully restored.</p>
		</div>
	</div>
</div>
<?php

	}
}
else
{

?>
<div class="blockform">
	<h2><span>Mod installation</span></h2>
	<div class="box">
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?foo=bar">
			<div><input type="hidden" name="form_sent" value="1" /></div>
			<div class="inform">
				<p>This script will update your database to work with the following modification:</p>
				<p><strong>Mod title:</strong> <?php echo pun_htmlspecialchars($mod_title.' '.$mod_version) ?></p>
				<p><strong>Author:</strong> <?php echo pun_htmlspecialchars($author) ?> (<a href="mailto:<?php echo pun_htmlspecialchars($author_email) ?>"><?php echo pun_htmlspecialchars($author_email) ?></a>)</p>
				<p><strong>Disclaimer:</strong> Mods are not officially supported by FluxBB. Mods generally can't be uninstalled without running SQL queries manually against the database. Make backups of all data you deem necessary before installing.</p>
<?php if ($mod_restore): ?>
				<p>If you've previously installed this mod and would like to uninstall it, you can click the Restore button below to restore the database.</p>
<?php endif; ?>
<?php if ($version_warning): ?>
				<p style="color: #a00"><strong>Warning:</strong> The mod you are about to install was not made specifically to support your current version of FluxBB (<?php echo $pun_config['o_cur_version']; ?>). This mod supports FluxBB versions: <?php echo pun_htmlspecialchars(implode(', ', $fluxbb_versions)); ?>. If you are uncertain about installing the mod due to this potential version conflict, contact the mod author.</p>
<?php endif; ?>
			</div>
			<p class="buttons"><input type="submit" name="install" value="Install" /><?php if ($mod_restore): ?><input type="submit" name="restore" value="Restore" /><?php endif; ?></p>
		</form>
	</div>
</div>
<?php

}

?>

</div>
</div>

</body>
</html>