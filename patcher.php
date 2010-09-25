<?php

if (!defined('PUN_ROOT'))
	define('PUN_ROOT', './');

if (!defined('PATCHER_ROOT'))
	define('PATCHER_ROOT', PUN_ROOT.'patcher/');

define('PATCHER_VERSION', '1.1');

require PUN_ROOT.'include/common.php';
require PATCHER_ROOT.'functions.php';

if ($pun_user['g_id'] != PUN_ADMIN)
	message($lang_common['No permission']);

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);
	
if (!defined('MODS_DIR'))
	define('MODS_DIR', PUN_ROOT.'mods/');

if (!defined('BACKUPS_DIR'))
	define('BACKUPS_DIR', PUN_ROOT.'backups/');

$mod = isset($_GET['mod']) ? basename($_GET['mod']) : null;
$file = isset($_GET['file']) ? $_GET['file'] : null;

if (isset($_POST['revert']))
{
	$revert_file = isset($_POST['revert_file']) ? basename($_POST['revert_file']) : null;
	revert($revert_file);
}

if (isset($_GET['export']))
{
	$name = isset($_GET['name']) ? basename($_GET['name']) : $mod;
	export($mod, $name);
}

if (isset($_POST['upload']))
	upload_mod();

if (isset($_POST['backup']))
{
	$backup_name = isset($_POST['backup_name']) ? basename($_POST['backup_name']) : 'fluxbb_'.time();

	create_backup($backup_name);
	
	if (isset($_POST['redirect']))
		redirect('patcher.php', 'Backup created');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html dir="ltr" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FluxBB Patcher v<?php echo PATCHER_VERSION ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $pun_config['o_base_url'] ?>/style/<?php echo (isset($pun_user['style'])) ? $pun_user['style'] : $pun_config['o_default_style'] ?>.css" />

<style type="text/css">
	.pun .codebox pre {
		max-height: 25em;
		overflow: auto;
	}
</style>

</head>
<body>

<div id="punuserlist" class="pun">
<div class="punwrap">

<?php


if (isset($mod) && file_exists(MODS_DIR.$mod))
{

?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="patcher.php">FluxBB Patcher</a></li>
			<li><span>»&#160;</span><?php echo pun_htmlspecialchars($mod) ?></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>

<?php
	
	// User hasn't confirmed modifying
	if (!isset($_POST['patch']))
	{
?>
<div class="blockform">
	<h2><span>Mod installation</span></h2>
	<div class="box">

<?php
	
		$form_action = 'patcher.php?mod='.pun_htmlspecialchars($mod);
	
		$readme = MODS_DIR.$mod.'/readme.txt';
		if (isset($file) && file_exists(MODS_DIR.$mod.'/'.$file))
		{
			$readme = MODS_DIR.$mod.'/'.$file;
			$form_action .= '&file='.$file;
		}
		
		if (!file_exists($readme))
			message('File '.$file.' does not exist');
			

?>
		<form action="<?php echo $form_action ?>" method="post">

<?php

		$install_file = '';
		if (file_exists(MODS_DIR.$mod.'/install_mod.php'))
			$install_file = MODS_DIR.$mod.'/install_mod.php';
		elseif (file_exists(MODS_DIR.$mod.'/files/install_mod.php'))
			$install_file = MODS_DIR.$mod.'/files/install_mod.php';
		
		if ($install_file != '')
		{
?>
			<div class="inform">
				<fieldset>
					<legend>Install mod</legend>
					<div class="infldset">
						<p>You need to install mod before patching FluxBB source code. 
<?php
			
			if (copy($install_file, PUN_ROOT.'install_mod.php'))
				echo 'Click following link to do it.</p><p><a href="'.$pun_config['o_base_url'].'/install_mod.php" target="_blank">Install MOD</a></p>';

			else
				echo 'Copy '.pun_htmlspecialchars($install_file).' file to the FluxBB root directory and run it in your browser.</p>';
?>
					</div>
				</fieldset>
			</div>
<?php
		}
		

		$files = array();
		scan_dir(MODS_DIR.$mod.'/files');

		if (!empty($files))
		{
?>
			<div class="inform">
				<fieldset>
					<legend>Files to upload</legend>
					<div class="infldset">
						<label><input type="checkbox" name="upload_files" value="1" checked="checked" /> Upload following files</label>
						<div class="forminfo">Warning: If file already exists, it will be overwritten.</div><br />
						<div style="max-height: 25em; overflow: auto">
<?php
			foreach ($files as $cur_file)
			{
				$warn = '';
				$checked = ' checked="checked"';
				if (file_exists(PUN_ROOT.$cur_file))
					$warn = ' (Already exists)';

				echo '<label><input type="checkbox" name="files[]" value="'.htmlspecialchars($cur_file).'"'.$checked.' /> <strong>'.htmlspecialchars($cur_file).'</strong>'.$warn.'</label>';
			}
?>
							</div>
					</div>
				</fieldset>
			</div>
<?php
		}
		
?>
			<div class="inform">
				<fieldset>
					<legend>Backup</legend>
					<div class="infldset">
						<p>If you want to create a backup before patching FluxBB files, select checkbox below.</p>
<?php

		if (class_exists('ZipArchive'))
		
			echo '<label><input type="checkbox" name="backup" />Create backup</label><label>Backup filename: <input type="text" name="backup_name" value="fluxbb_'.time().'" /></label>';
		else
			echo '<p>Install Zip extension for your PHP sever if you want to backup FluxBB files before patching.</p>';
		
?>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="patch" value="Start patching" />Click this button if you are ready :)</p>
		</form>
	</div>
</div>
<?php

	}
	else
	{
?>
<div class="blocktable">
	<div class="box">
		<div class="inbox">
			<table>
			
<?php	
		$uploaded_files = array();
		if (isset($_POST['upload_files']) && isset($_POST['files']))
		{
			foreach ($_POST['files'] as $cur_file)
			{
				$dirs = explode('/', dirname($cur_file));
				$cur_path = '';
				foreach ($dirs as $cur_dir)
				{
					$cur_path .= $cur_dir.'/';
					
					if (!file_exists(PUN_ROOT.$cur_path))
						mkdir(PUN_ROOT.$cur_path);
				}
				
				
				if (copy(MODS_DIR.$mod.'/files/'.$cur_file, PUN_ROOT.$cur_file))
					$uploaded_files[] = $cur_file;
			}
		}
		
		if (!empty($uploaded_files))
		{
?>
				<thead>
					<tr>
						<th>Following files were uploaded:</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><div class="codebox"><pre><code><?php echo implode("\n", $uploaded_files) ?></code></pre></div></td>
					</tr>
				</tbody>
<?php

		}
		
?>
				<tbody>
<?php
		$summary = patch_files();

		if (isset($summary['actions'][0]) && count($summary['actions'][0]) > 0 || 
			isset($summary['modified_files']) && count($summary['modified_files']) > 0) 
		{
?>

		

<div class="blockform" style="margin-top: 12px">
	<h2><span>Summary</span></h2>
	<div class="box">		
		<div class="fakeform">
			<div class="inform">
<?php		if (isset($summary['modified_files']) && count($summary['modified_files']) > 0) { ?>
				<fieldset>
					<legend>Modified files</legend>
					<div class="infldset">
						<div class="codebox"><pre><code><?php echo htmlspecialchars(implode("\n", $summary['modified_files'])) ?></code></pre></div>
					</div>
				</fieldset>
<?php 		} if (isset($summary['actions'][0]) && count($summary['actions'][0]) > 0) { ?>
				<fieldset>
					<legend>Actions not done</legend>
					<div class="infldset">
<?php	foreach ($summary['actions'][0] as $key => $val) echo '<a href="#action'.$key.'">'.$key.'. '.$val.'</a><br />' ?>
					</div>
				</fieldset>
<?php 		} ?>
			</div>
		</div>
	</div>
</div>
<?php		}

	}
	
?>



<div class="linksb">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="patcher.php">FluxBB Patcher</a></li>
			<li><span>»&#160;</span><?php echo pun_htmlspecialchars($mod) ?></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>
<?php


}
else
{

?>

<div class="blockform">
	<h2><span>Backup FluxBB files</span></h2>
	<div class="box">
		<div class="block2col">
			<div class="leftcol" style="float: left; width: 50%">
				<form action="patcher.php" method="post">
					<div class="inform">
						<fieldset>
							<legend>Backup FluxBB files</legend>
							<div class="infldset">
<?php	if (class_exists('ZipArchive'))
			echo '<input type="hidden" name="redirect" value="1" /><label>Backup filename: <input type="text" name="backup_name" value="fluxbb_'.time().'" /></label>';
		else
			echo '<p>Install Zip extension for your PHP sever if you want to backup FluxBB files.</p>';
?>
								<p>This tool makes a backup of FluxBB root and include directory.</p>
								<p>All backups are placed in <strong>patcher/backups</strong> directory.</p>
							</div>
						</fieldset>
<?php if (class_exists('ZipArchive')) : ?>			<p class="buttons"><input type="submit" name="backup" value="Make backup" /></p><?php endif; ?>
					</div>
				</form>
			</div>


<?php
	$backups = array();
	$dir = dir(BACKUPS_DIR);
	while ($file = $dir->read())
	{
		if (substr($file, 0, 1) != '.' && substr($file, strlen($file) - 4) == '.zip')
		{
			$created_time = format_time(filemtime(BACKUPS_DIR.$file));
			
			$backups[] = '<option value="'.pun_htmlspecialchars($file).'">'.pun_htmlspecialchars($file). ' ('.$created_time.')</option>';
		}
	}
	

?>

			<div class="rightcol" style="margin-left: 50%">
				<form action="patcher.php" method="post">
					<div class="inform" style="padding-left: 0">
						<fieldset>
							<legend>Revert FluxBB files</legend>
							<div class="infldset">
<?php								if (count($backups) > 0) { ?>
								<label>Revert from file: 
									<select name="revert_file">
										<?php echo implode("\n\t\t\t\t", $backups); ?>
									</select>
								</label>
								<p>Use this form to revert FluxBB files from backup.</p>

<?php	} else { ?>
								<p>You have not created any backups yet.</p>
								<p>If you want to, use form at left.</p>
<?php	} ?>
								<p>&nbsp;</p>
							</div>
						</fieldset>
						<p class="buttons"><input type="submit" name="revert" value="Revert" /></p>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>



<div class="blockform" style="margin-top: 12px">
	<h2><span>Upload modification</span></h2>
	<div class="box">
		<form method="post" action="patcher.php" enctype="multipart/form-data">
			<div class="inform">
				<fieldset>
					<legend>Upload modification</legend>
					<div class="infldset">
						<label>Upload file: <input type="file" name="upload_mod" /></label>
						<p>You can use this form to upload modification ZIP archive and install it.</p>
					</div>
				</fieldset>
				<p class="buttons"><input type="submit" name="upload" value="Upload" /></p>
			</div>
		</form>
	</div>
</div>

<div class="blocktable" style="margin-top: 12px">
	<div class="box">
		<div class="inbox">
			<table>
				<thead>
					<tr>
						<th class="tcl" style="width: 50%">Modifications</th>
						<th>Readme file</th>
						<th class="tc3">Version</th>
						<th class="tcr" style="width: 10%">Export</th>
					</tr>
				</thead>
				<tbody>
<?php

	if (file_exists(PUN_ROOT.'mods.php'))
		require PUN_ROOT.'mods.php';
	else
		$inst_mods = array();
	
	$i = 0;
	$mods_array = array();
	$dir = dir(MODS_DIR);
	while ($mod = $dir->read())
	{
		if (substr($mod, 0, 1) != '.' && is_dir(MODS_DIR.$mod))
		{
			$readme_files = $readme_file_names = array();
			
			look_for_readme($mod, MODS_DIR.$mod, MODS_DIR.$mod);
			
			if (count($readme_file_names) > 0)
				$mod_info = mod_info($mod, $readme_file_names[0]);
			else
				$mod_info = mod_info($mod);
			
			$mods_array[$mod_info['Mod title'].'_'.$mod_info['Mod version']] = array($mod_info, $readme_files);
		}
	}
	
	ksort($mods_array);

	foreach ($mods_array as $info)
	{
		list($mod_info, $readme_files) = $info;
	
		$export = '<a href="patcher.php?export&mod='.htmlspecialchars($mod_info['Mod title']).'&name='.htmlspecialchars($mod_info['Mod title'].(isset($mod_info['Mod version']) ? '_'.$mod_info['Mod version'] : '')).'">Export</a>';
		
		$title = array();
		$title[] = '<strong>'.pun_htmlspecialchars($mod_info['Mod title']).'</strong>';

		if (isset($mod_info['Author email']) && trim($mod_info['Author email']) != '')
			$title[] = ' by <a href="mailto:'.pun_htmlspecialchars($mod_info['Author email']).'">'.pun_htmlspecialchars($mod_info['Author']).'</a>';
		elseif (isset($mod_info['Author']) && !empty($mod_info['Author']))
			$title[] = ' by '.pun_htmlspecialchars($mod_info['Author']);
		
		if (isset($mod_info['Description']))
			$title[] = '<br />'.pun_htmlspecialchars($mod_info['Description']);
			
		$version = array();
		if (isset($mod_info['Mod version']))
			$version[] = '<strong>'.pun_htmlspecialchars($mod_info['Mod version']).'</strong>';
		
		if (isset($mod_info['Release date']))
			$version[] = pun_htmlspecialchars($mod_info['Release date']);

?>
					<tr class="<?php echo ($i % 2 == 0) ? 'roweven' : 'rowodd' ?>">
						<td class="tcl"><?php echo implode("\n", $title) ?></td>
						<td><?php echo implode(', ', $readme_files) ?></td>
						<td class="tc3"><?php echo implode('<br />', $version) ?></td>
						<td class="tcr"><?php echo $export ?></td>
					</tr>
<?php
		$i++;
		
	}
					
	if ($i == 0)
		echo '<tr><td colspan="4">No modifications found in <strong>patcher/mods</strong> directory.</td></tr>';
?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php

}


// Display debug info (if enabled/defined)
if (defined('PUN_DEBUG'))
{
	echo '<p id="debugtime">[ ';

	// Calculate script generation time
	$time_diff = sprintf('%.3f', get_microtime() - $pun_start);
	echo sprintf($lang_common['Querytime'], $time_diff, $db->get_num_queries());

	if (function_exists('memory_get_usage'))
	{
		echo ' - '.sprintf($lang_common['Memory usage'], file_size(memory_get_usage()));

		if (function_exists('memory_get_peak_usage'))
			echo ' '.sprintf($lang_common['Peak usage'], file_size(memory_get_peak_usage()));
	}

	echo ' ]</p>'."\n";
}

// End the transaction
$db->end_transaction();

// Display executed queries (if enabled)
if (defined('PUN_SHOW_QUERIES'))
	display_saved_queries();

// Close the db connection (and free up any result data)
$db->close();

?>

</div>
</div>

</body>
</html>