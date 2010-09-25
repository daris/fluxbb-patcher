<?php
/***********************************************************************/

// Some info about your mod.
$mod_title      = 'Auto Poll';
$mod_version    = '1.0';
$release_date   = '2009-09-07';
$author         = 'Koos - original version by Mediator';
$author_email   = 'pampoen10@yahoo.com';

// Set this to false if you haven't implemented the restore function (see below)
$mod_restore	= true;


// This following function will be called when the user presses the "Install" button.
function install()
{
	global $db, $db_type, $pun_config;

	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db->prefix."polls (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					pollid INT(10) UNSIGNED NOT NULL DEFAULT 0,
					options LONGTEXT NOT NULL,
					voters LONGTEXT,
					ptype tinyint(4) NOT NULL default '0',
					votes LONGTEXT,
					created INT(10) UNSIGNED NOT NULL DEFAULT 0,
					edited INT(10) UNSIGNED,
					edited_by VARCHAR(200),
					PRIMARY KEY (id)
					) TYPE=MyISAM;";
			break;

		case 'pgsql':
			$db->start_transaction();

			$sql = 'CREATE TABLE '.$db->prefix."polls (
					id SERIAL,
					pollid INT NOT NULL DEFAULT 0,
					options TEXT NOT NULL,
					voters TEXT,
					ptype SMALLINT NOT NULL default 0,
					votes TEXT,
					created INT NOT NULL DEFAULT 0,
					edited INT,
					edited_by VARCHAR(200),
					PRIMARY KEY (id)
					)";
			break;

		case 'sqlite':
			$db->start_transaction();

			$sql = 'CREATE TABLE '.$db->prefix."polls (
					id INTEGER NOT NULL,
					pollid INTEGER NOT NULL DEFAULT 0,
					options TEXT NOT NULL,
					voters TEXT,
					ptype INTEGER NOT NULL default 0,
					votes TEXT,
					created INTEGER NOT NULL DEFAULT 0,
					edited INTEGER,
					edited_by VARCHAR(200),
					PRIMARY KEY (id)
					)";
			break;
	}
	$db->query($sql) or error('Unable to create table '.$db->prefix.'polls.',  __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$db->query("ALTER TABLE ".$db->prefix."topics ADD question VARCHAR(255) NOT NULL DEFAULT ''") or error('Unable to add columns to table', __FILE__, __LINE__, $db->error());
			$db->query("ALTER TABLE ".$db->prefix."forum_perms ADD post_polls TINYINT(1) NOT NULL DEFAULT 1") or error('Unable to add columns to table', __FILE__, __LINE__, $db->error());
			
			$db->query("ALTER TABLE ".$db->prefix."groups ADD g_post_polls SMALLINT  NOT NULL DEFAULT 1") or error('Unable to add columns to table', __FILE__, __LINE__, $db->error());
			
			break;

		case 'pgsql':
			$db->query("ALTER TABLE ".$db->prefix."topics ADD question VARCHAR(255)") or error('Unable to add columns to table', __FILE__, __LINE__, $db->error());
			$db->query('ALTER TABLE '.$db->prefix.'topics ALTER question SET DEFAULT \'\'') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());

			$db->query('UPDATE '.$db->prefix.'topics SET question=\'\'') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());
			$db->query('ALTER TABLE '.$db->prefix.'topics ALTER question SET NOT NULL') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());

			$db->query("ALTER TABLE ".$db->prefix."forum_perms ADD post_polls SMALLINT") or error('Unable to add columns to table', __FILE__, __LINE__, $db->error());
			$db->query('ALTER TABLE '.$db->prefix.'forum_perms ALTER post_polls SET DEFAULT 1') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());
			$db->query('UPDATE '.$db->prefix.'forum_perms SET post_polls=1') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());
			$db->query('ALTER TABLE '.$db->prefix.'forum_perms ALTER post_polls SET NOT NULL') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());
			break;

		case 'sqlite':
			$db->query("ALTER TABLE ".$db->prefix."topics ADD question VARCHAR(255) NOT NULL DEFAULT ''") or error('Unable to add columns to table', __FILE__, __LINE__, $db->error());
			$db->query("ALTER TABLE ".$db->prefix."forum_perms ADD post_polls INTEGER NOT NULL DEFAULT 1") or error('Unable to add columns to table', __FILE__, __LINE__, $db->error());
			break;
	}


	$config = array(
		'o_poll_enabled'			=> '1',
		'o_poll_max_fields'			=> '10',
		'o_poll_mod_delete_polls'	=> '0',
		'o_poll_mod_edit_polls'		=> '0',
		'o_poll_mod_reset_polls'	=> '0'
	);

	while (list($conf_name, $conf_value) = @each($config))
	{
		$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', $conf_value)")
			or exit('Unable to insert into table '.$db->prefix.'config. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');
	}

	if ($db_type == 'pgsql' || $db_type == 'sqlite')
		$db->end_transaction();

	// Delete everything in the cache since we messed with some stuff
	$d = dir(PUN_ROOT.'cache');
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(PUN_ROOT.'cache/'.$entry);
	}
	$d->close();
}

// This following function will be called when the user presses the "Restore" button (only if $mod_uninstall is true (see above))
function restore()
{
	global $db, $db_type, $pun_config;

	if ($db_type == 'pgsql' || $db_type == 'sqlite')
		$db->start_transaction();

	$db->query('DROP TABLE '.$db->prefix.'polls') or error('Unable to remove table', __FILE__, __LINE__, $db->error());

	if ($db_type != 'sqlite')	// No DROP column in SQLite
	{
		$db->query('ALTER TABLE '.$db->prefix.'topics DROP question') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());
		$db->query('ALTER TABLE '.$db->prefix.'forum_perms DROP post_polls') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());
		$db->query('ALTER TABLE '.$db->prefix.'groups DROP g_post_polls') or error('Unable to alter DB structure.', __FILE__, __LINE__, $db->error());
	}


	$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';
	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name '.$like_command.' \'o_poll%\'') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());

	if ($db_type == 'pgsql' || $db_type == 'sqlite')
		$db->end_transaction();

	// Delete everything in the cache since we messed with some stuff
	$d = dir(PUN_ROOT.'cache');
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(PUN_ROOT.'cache/'.$entry);
	}
	$d->close();
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

$version = explode(".", $pun_config['o_cur_version']);
// Make sure we are running a PunBB version that this mod works with
if ($version[0] != 1 || $version[1] != 4)
	exit('You are running a version of PunBB ('.$pun_config['o_cur_version'].') that this mod does not support. This mod supports PunBB versions: 1.2.x');

$style = (isset($cur_user)) ? $cur_user['style'] : $pun_config['o_default_style'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php echo $mod_title ?> installation</title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_config['o_default_style'].'.css' ?>" />
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
				<p><strong>Mod title:</strong> <?php echo pun_htmlspecialchars($mod_title).' '.$mod_version ?></p>
				<p><strong>Author:</strong> <?php echo pun_htmlspecialchars($author) ?> (<a href="mailto:<?php echo pun_htmlspecialchars($author_email) ?>"><?php echo pun_htmlspecialchars($author_email) ?></a>)</p>
				<p><strong>Disclaimer:</strong> Mods are not officially supported by PunBB. Mods generally can't be uninstalled without running SQL queries manually against the database. Make backups of all data you deem necessary before installing.</p>
<?php if ($mod_restore): ?>				<p>If you've previously installed this mod and would like to uninstall it, you can click the restore button below to restore the database.</p>
<?php endif; ?>			</div>
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