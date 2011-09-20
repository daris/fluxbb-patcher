<?php
/***********************************************************************/

// Some info about your mod.
$mod_title      = 'Attachment Mod';
$mod_version    = '2.1.1';
$release_date   = '2011-04-27';
$author         = 'Daris';
$author_email   = 'daris91@gmail.com';

// One or more versions of FluxBB that this mod works on. The version names must match exactly!
$fluxbb_versions= array('1.4.5', '1.4.4', '1.4.3', '1.4.2', '1.4.1', '1.4.0', '1.4-rc3');

// Set this to false if you haven't implemented the restore function (see below)
$mod_restore	= true;


// This following function will be called when the user presses the "Install" button.
function install($basepath='')
{
	global $db, $db_type, $pun_config, $mod_version;
	//include PUN_ROOT.'include/attach/attach_incl.php';
	
	if ($basepath == '')
		$basepath = dirname($_SERVER['SCRIPT_FILENAME']).'/attachments/';

	//first check so that the path seems reasonable
	if(!((substr($basepath,0,1) == '/' || substr($basepath,1,1) == ':') && substr($basepath,-1) == '/'))
		error('The pathname specified doesn\'t comply with the rules set. Go back and make sure that it\'s the complete path, and that it ends with a slash and that it either start with a slash (example: "/home/username/attachments/", on *nix servers (unix, linux, bsd, solaris etc.)) or a driveletter (example: "C:/webpages/attachments/" on windows servers)');

	// create the files table
	$schema_files = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'owner'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'post_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'filename'	=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false,
					),
					'extension'		=> array(
							'datatype'			=> 'VARCHAR(64)',
							'allow_null'		=> false,
					),
					'mime'	=> array(
							'datatype'			=> 'VARCHAR(64)',
							'allow_null'		=> false
					),
					'location'	=> array(
							'datatype'			=> 'TEXT',
							'allow_null'		=> false
					),
					'size'	=> array(
							'datatype'		=> 'INT(10)',
							'allow_null'	=> false,
							'default'		=> '0'
					),
					'downloads'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					)
			),
			'PRIMARY KEY'		=> array('id'),
	);
	
	$db->create_table('attach_2_files', $schema_files) or error('Unable to create table "attach_2_files"', __FILE__, __LINE__, $db->error());
	
	
	// create the files table
	$schema_rules = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'forum_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'group_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'rules'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'size'		=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'per_post'	=> array(
							'datatype'			=> 'TINYINT(4)',
							'allow_null'		=> false,
							'default'		=> '1'
					),
					'file_ext'	=> array(
							'datatype'			=> 'TEXT',
							'allow_null'		=> false
					),
			),
			'PRIMARY KEY'		=> array('id'),
	);
	
	$db->create_table('attach_2_rules', $schema_rules) or error('Unable to create table "attach_2_rules"', __FILE__, __LINE__, $db->error());
	
	//ok path could be correct, try to make a subfolder :D
	$newname = attach_generate_pathname($basepath);
	if(!attach_create_subfolder($newname,$basepath))
		error('Unable to create new subfolder with name "'.$newname.'", make sure php has write access to that folder!',__FILE__,__LINE__);
	
		
	// ok, add the stuff needed in the config cache
	$attach_config = array(	'attach_always_deny'	=>	'html"htm"php"php3"php4"php5"exe"com"bat',
							'attach_basefolder'		=>	$basepath,
							'attach_create_orphans'	=>	'1',
							'attach_cur_version'	=>	$mod_version,
							'attach_icon_folder'	=>	'img/attach/',
							'attach_icon_extension'	=>	'txt"log"doc"pdf"wav"mp3"ogg"avi"mpg"mpeg"png"jpg"jpeg"gif"zip"rar"7z"gz"tar',
							'attach_icon_name'		=>	'text.png"text.png"doc.png"doc.png"audio.png"audio.png"audio.png"video.png"video.png"video.png"image.png"image.png"image.png"image.png"compress.png"compress.png"compress.png"compress.png"compress.png',
							'attach_max_size'		=>	'100000',
							'attach_subfolder'		=>	$newname,
							'attach_use_icon'		=>	'1');
	
	foreach($attach_config AS $key => $value)
		if (!isset($pun_config[$key]))
			$db->query("INSERT INTO ".$db->prefix."config (conf_name, conf_value) VALUES ('$key', '".$db->escape($value)."')") or error('Unable to add column "'.$key.'" to config table', __FILE__, __LINE__, $db->error());


	// and now, update the cache...
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();	

}

function attach_create_subfolder($newfolder='',$basepath){
		
	// check to see if that folder is there already, then just update the config ...
	if(!is_dir($basepath.$newfolder)){
		// if the folder doesn't exist, try to create it
		if(!mkdir($basepath.$newfolder,0755))
			error('Unable to create new subfolder with name \''.$basepath.$newfolder.'\' with mode 0755',__FILE__,__LINE__);
		// create a .htaccess and index.html file in the new subfolder
		if(!copy($basepath.'.htaccess', $basepath.$newfolder.'/.htaccess'))
			error('Unable to copy .htaccess file to new subfolder with name \''.$basepath.$newfolder.'\'',__FILE__,__LINE__);
		if(!copy($basepath.'index.html', $basepath.$newfolder.'/index.html'))
			error('Unable to copy index.html file to new subfolder with name \''.$basepath.$newfolder.'\'',__FILE__,__LINE__);
		// if the folder was created continue
	}
	// return true if everything has gone as planned, return false if the new folder could not be created (rights etc?)
	return true;
}

function attach_generate_pathname($storagepath=''){
	if(strlen($storagepath)!=0){
		//we have to check so that path doesn't exist already...
		$not_unique=true;
		while($not_unique){
			$newdir = attach_generate_pathname();
			if(!is_dir($storagepath.$newdir))return $newdir;
		}
	}else
		return substr(md5(time().'54£7 k3yw0rd, r3pl4ce |f U w4nt t0'),0,32);
}



function attach_generate_filename($storagepath, $messagelenght=0, $filesize=0){
	$not_unique=true;
	while($not_unique){
		$newfile = md5(attach_generate_pathname().$messagelenght.$filesize.'Some more salt keyworbs, change if you want to').'.attach';
		if(!is_file($storagepath.$newfile))return $newfile;
	}	
}


// This following function will be called when the user presses the "Restore" button (only if $mod_uninstall is true (see above))
function restore()
{
	global $db, $db_type, $pun_config;

		// ok, add the stuff needed in the config cache
	$attach_config = array(	'attach_always_deny', 'attach_basefolder', 'attach_create_orphans', 'attach_cur_version', 'attach_icon_folder', 'attach_icon_extension', 'attach_icon_name',  'attach_max_size', 'attach_subfolder', 'attach_use_icon');
	
	foreach($attach_config AS $value)
		$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name=\''.$value.'\'') or error('Unable to delete column "'.$value.'" to config table', __FILE__, __LINE__, $db->error());

	$db->drop_table('attach_2_files') or error('Unable to drop table "attach_2_files"', __FILE__, __LINE__, $db->error());
	$db->drop_table('attach_2_rules') or error('Unable to drop table "attach_2_rules"', __FILE__, __LINE__, $db->error());

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
		install($_POST['full_basename']);

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
				<p><strong>Important instructions:</strong> Before pressing the install button, create a folder where you want your attachments to be stored on disk. (Read step 1 in readme.txt) It's crucial that PHP has writepermissions there, and that it's not browseable! (Examples: "/home/username/attachments/" or "D:/homepages/attachments/", note, use only /, not \)<br />Enter the <strong>full</strong> pathname in the box below<br /><input type="text" name="full_basename" size="60" value="<?php echo dirname($_SERVER['SCRIPT_FILENAME']); ?>/attachments/"></p>
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
