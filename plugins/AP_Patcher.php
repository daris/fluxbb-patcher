<?php
/**
 * FluxBB Patcher 2.0
 * http://fluxbb.org/forums/viewtopic.php?id=4431
 */


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_URL', 'admin_loader.php?plugin=AP_Patcher.php');

if (!defined('PATCHER_ROOT'))
	define('PATCHER_ROOT', PUN_ROOT.'plugins/patcher/');

define('PATCHER_VERSION', '1.2');

require PATCHER_ROOT.'functions.php';
require PATCHER_ROOT.'flux_mod.class.php';
require PATCHER_ROOT.'patcher.class.php';

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

// Get the patcher directories
if (!defined('MODS_DIR'))
	define('MODS_DIR', PUN_ROOT.'mods/');
	
if (!is_dir(MODS_DIR))
	error('Mods directory does not exist');

if (!defined('BACKUPS_DIR'))
{
	if (is_dir(MODS_DIR.'../backups'))
		define('BACKUPS_DIR', MODS_DIR.'../backups/');
	else
	{
		// Try to create directory
		if (!is_dir(PUN_ROOT.'backups'))
			mkdir(PUN_ROOT.'backups');
		define('BACKUPS_DIR', PUN_ROOT.'backups/');
	}
}

// Load the language file (related to PATCHER_ROOT instead of PUN_ROOT as I have placed it somewhere else :P )
if (file_exists(PATCHER_ROOT.'../../lang/'.$pun_user['language'].'/admin_plugin_patcher.php'))
	require PATCHER_ROOT.'../../lang/'.$pun_user['language'].'/admin_plugin_patcher.php';
else
	require PATCHER_ROOT.'../../lang/English/admin_plugin_patcher.php';

// Need sesion for storing/retrieving patching log
if (!session_id())
	session_start();

$mod_id = isset($_GET['mod_id']) ? basename($_GET['mod_id']) : null;
$action = isset($_GET['action']) && in_array($_GET['action'], array('install', 'uninstall', 'enable', 'disable', 'show_log')) ? $_GET['action'] : 'install';
$file = isset($_GET['file']) ? $_GET['file'] : 'readme.txt';

// Revert from backup
if (isset($_POST['revert']))
{
	$revert_file = isset($_POST['revert_file']) ? basename($_POST['revert_file']) : null;
	revert($revert_file);
}

// Upload modification package
if (isset($_POST['upload']))
	upload_mod();

// Download an update of mod from FluxBB repo
if (isset($_GET['update']))
{
	if (!isset($mod_id) || empty($mod_id) || !isset($_GET['version']) || empty($_GET['version']))
		message($lang_common['Bad request']);

	update_mod($mod_id, $_GET['version']);
}

// Download modification from FluxBB repo
if (isset($_GET['download']))
	download_mod(basename($_GET['download']));

// Create initial backup
if (!file_exists(BACKUPS_DIR.'fluxbb-'.FORUM_VERSION.'.zip'))
	create_backup('fluxbb-'.FORUM_VERSION);

if (isset($_POST['backup']) && !isset($_POST['patch'])) // TODO: is $_POST['patch'] used somewhere?
{
	$backup_name = isset($_POST['backup_name']) ? basename($_POST['backup_name']) : 'fluxbb_'.time();
	create_backup($backup_name);
	
	redirect(PLUGIN_URL, $lang_admin_plugin_patcher['Backup created redirect']);
}
$warning = '';

// Get modification repository (we need $mod_repo for $patcher_mod)
$mod_repo = get_mod_repo();

// Check for patcher updates
$patcher_mod = new FLUX_MOD('patcher');
$patcher_mod->version = PATCHER_VERSION;
if ($last_patcher_release = $patcher_mod->check_for_updates())
	$warning .= '<div class="blockform">'."\n\t".'<h2></h2>'."\n\t".'<div class="box">'."\n\t\t".'<div class="fakeform">'."\n\t\t".'<div class="inform">'."\n\t\t\t".'<div class="forminfo">'."\n\t\t\t\t".'<p>'.sprintf($lang_admin_plugin_patcher['New version available'], $last_patcher_release, '<a href="http://fluxbb.org/resources/mods/patcher/">'.$lang_admin_plugin_patcher['Resources page'].'</a>').'</p>'."\n\t\t\t".'</div>'."\n\t\t".'</div>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>';

// Show warning if there are directories not writable
// TODO: is it really needed? There is a requirements list before installing mod
$dirs_not_writable = array();
$check_dirs = array('backups' => BACKUPS_DIR, 'mods' => MODS_DIR);
foreach ($check_dirs as $name => $cur_dir)
{
	if (!is_writable($cur_dir))
		$dirs_not_writable[] = sprintf($lang_admin_plugin_patcher['Directory not writable'], pun_htmlspecialchars($name));
}

if (count($dirs_not_writable) > 0)
	$warning .= '<div class="blockform">'."\n\t".'<h2></h2>'."\n\t".'<div class="box">'."\n\t\t".'<div class="fakeform">'."\n\t\t".'<div class="inform">'."\n\t\t\t".'<div class="forminfo">'."\n\t\t\t\t".'<h3><strong>'.$lang_admin_plugin_patcher['Important'].'</strong></h3><p>'.implode('<br />', $dirs_not_writable).'</p>'."\n\t\t\t".'</div>'."\n\t\t".'</div>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>';

if (isset($mod_id) && file_exists(MODS_DIR.$mod_id))
{
	// Do patching! :)
	if (isset($_POST['install']))
	{
		$flux_mod = new FLUX_MOD($mod_id);

		$patcher = new PATCHER($flux_mod, $action);
		$done = $patcher->patch();
		$log = $patcher->log;

		generate_admin_menu($plugin);
	
?>
	<div class="blockform">
		<h2><span><?php echo $lang_admin_plugin_patcher['Mod installation'] ?></span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Mod installation status'] ?></legend>
						<div class="infldset">
							<p>
<?php

		$actions = $notes = array();

		foreach ($log as $cur_step_list)
		{
			foreach ($cur_step_list as $cur_step)
			{
				if (!isset($cur_step['command']))
					continue;

				if ($cur_step['command'] == 'UPLOAD')
				{
					$num_files = count(explode("\n", $cur_step['substeps'][0]['code']));
					if (in_array($action, array('disable', 'uninstall')))
						$actions[] = array($lang_admin_plugin_patcher['Deleting files'], true, '('.sprintf($lang_admin_plugin_patcher['Num files deleted'], $num_files).')');
					else
						$actions[] = array($lang_admin_plugin_patcher['Uploading files'], true, '('.sprintf($lang_admin_plugin_patcher['Num files uploaded'], $num_files).')');
				}
				elseif ($cur_step['command'] == 'OPEN')
				{
					$num_changes = $num_failed = 0;
					if (isset($cur_step['substeps']))
					{
						foreach ($cur_step['substeps'] as $cur_subaction)
						{
							if ($cur_subaction['status'] == STATUS_DONE || $cur_subaction['status'] == STATUS_REVERTED)
								$num_changes++;
							elseif ($cur_subaction['status'] == STATUS_NOT_DONE)
								$num_failed++;
						}
					}
					
					$color = ($num_failed > 0) ? 'red' : 'green';

					$sub_msg = array();
					if ($num_changes > 0)
						$sub_msg[] = sprintf($lang_admin_plugin_patcher['Num changes'.($action == 'uninstall' ? ' reverted' : '')], $num_changes);
					if ($num_failed > 0)
						$sub_msg[] = sprintf($lang_admin_plugin_patcher['Num failed'], $num_failed);

					$actions[] = array(sprintf($lang_admin_plugin_patcher['Patching file'], pun_htmlspecialchars($cur_step['code'])), $num_failed == 0, (count($sub_msg) > 0 ? '('.implode(', ', $sub_msg).')' : ''));
				}
				elseif ($cur_step['command'] == 'RUN')
				{
					$new_action =  array(sprintf($lang_admin_plugin_patcher['Running'], pun_htmlspecialchars($cur_step['code'])), true);
					if (isset($cur_step['result']))
					{
						$result = $cur_step['result'];
						if (strpos($result, "\n") !== false)
							$result = substr($result, 0, strpos($result, "\n"));
						$new_action[] = $result;
					}
					$actions[] = $new_action;
				}
				elseif ($cur_step['command'] == 'DELETE')
					$actions[] = array(sprintf($lang_admin_plugin_patcher['Deleting'], pun_htmlspecialchars($cur_step['code'])), true);
				
				elseif ($cur_step['command'] == 'NOTE' && isset($cur_step['result']))
					$notes[] = $cur_step['result'];
			}
		}

		foreach ($actions as $cur_action)
			echo '<strong style="color: '.($cur_action[1] ? 'green' : 'red').'">'.$cur_action[0].'</strong>... '.(isset($cur_action[2]) ? $cur_action[2] : '').'<br />';
?>
							</p>
<?php if ($done) : ?>
							<p><strong><?php echo $lang_admin_plugin_patcher['Congratulations'] ?></strong><br /><?php echo $action == 'uninstall' ? $lang_admin_plugin_patcher['Mod uninstalled'] : $lang_admin_plugin_patcher['Mod installed'] ?></p>
<?php else: ?>
							<p><strong><?php echo $action == 'uninstall' ? $lang_admin_plugin_patcher['Uninstall failed'] : $lang_admin_plugin_patcher['Install failed'] ?></strong><br /><?php echo $action == 'uninstall' ? $lang_admin_plugin_patcher['Mod uninstall failed'] : $lang_admin_plugin_patcher['Mod install failed'] ?></p>
<?php endif; ?>
<?php 	if (count($notes) > 0) 
		{
			echo '<p><strong>'.$lang_admin_plugin_patcher['Final instructions'].'</strong>';
			foreach ($notes as $cur_note)
				echo '<code><pre>'.pun_htmlspecialchars($cur_note).'</pre></code>'; 
			echo '</p>';
		} ?>
							<p>
								<a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod_id) ?>&action=show_log"><?php echo $lang_admin_plugin_patcher['Show log'] ?></a> | 
								<a href="<?php echo PLUGIN_URL ?>"><?php echo $lang_admin_plugin_patcher['Return to mod list'] ?></a>
							</p>
						</div>
					</fieldset>
				</div>
			</div>
		</div>
	</div>
<?php

	}
	
	// Show patching log
	elseif ($action == 'show_log')
	{
		$first = true;
		generate_admin_menu($plugin);
		
		if (!isset($_SESSION['patcher_log']))
			message($lang_common['Bad request']);
		$log = unserialize($_SESSION['patcher_log']);

		$i = 0;
		foreach ($log as $cur_readme_file => $actions)
		{
			$cur_mod = substr($cur_readme_file, 0, strpos($cur_readme_file, '/'));
			$cur_readme = substr($cur_readme_file, strpos($cur_readme_file, '/') + 1);
?>
	<div class="block blocktable">
		<h2<?php if ($first) $first = false; else echo ' class="block2"'; ?>><span><?php echo pun_htmlspecialchars($cur_mod).' » '.pun_htmlspecialchars($cur_readme) ?></span></h2>
<?php
			foreach ($actions as $key => $cur_step)
			{
				if (isset($cur_step['command']) && isset($lang_admin_plugin_patcher[$cur_step['command']]))
					$cur_step['command'] = $lang_admin_plugin_patcher[$cur_step['command']];

?>
		<div class="box">
			<div class="inbox">
				<table>
					<thead>
						<tr>
<?php if (isset($cur_step['command']) && isset($cur_step['code'])) : ?>
							<th id="a<?php echo ++$i ?>" style="<?php echo ($cur_step['status'] == STATUS_NOT_DONE) ? 'font-weight: bold; color: #a00' : '' ?>"><span style="float: right"><a href="#a<?php echo $key ?>">#<?php echo $i ?></a></span><?php echo pun_htmlspecialchars($cur_step['command']).' '.pun_htmlspecialchars($cur_step['code']) ?></th>
<?php elseif (isset($cur_step['command'])) : ?>
							<th><?php echo pun_htmlspecialchars($cur_step['command']) ?></th>
<?php else : ?>
							<th><?php echo $lang_admin_plugin_patcher['Actions'] ?></th>
<?php endif; ?>
						</tr>
					</thead>
<?php if (isset($cur_step['result'])) : ?>
					<tr>
						<td><?php echo /*pun_htmlspecialchars(*/$cur_step['result']/*) Allow to run file show html output*/ ?></td>
					</tr>
<?php endif;
				if (isset($cur_step['substeps']) && count($cur_step['substeps']) > 0)
				{
?>
					<tbody>
<?php
					foreach ($cur_step['substeps'] as $id => $cur_substep)
					{
						if (isset($cur_substep['command']) && isset($lang_admin_plugin_patcher[$cur_substep['command']]))
							$cur_substep['command'] = $lang_admin_plugin_patcher[$cur_substep['command']];
						$style = $action_str = '';
						
						if (!isset($cur_substep['status']))
							$cur_substep['status'] = STATUS_UNKNOWN;
						switch ($cur_substep['status'])
						{
							case STATUS_NOT_DONE:		$style = 'font-weight: bold; color: #a00'; $action_str = '('.$lang_admin_plugin_patcher['NOT DONE'].')'; break;
							case STATUS_DONE:			$style = 'color: #0a0'; 	$action_str = '('.$lang_admin_plugin_patcher['DONE'].')'; break;
							case STATUS_ALREADY_DONE:	$style = 'color: orange'; 	$action_str = '('.$lang_admin_plugin_patcher['ALREADY DONE'].')'; break;
							case STATUS_REVERTED:		$style = 'color: #00a'; 	$action_str = '('.$lang_admin_plugin_patcher['REVERTED'].')'; break;
							case STATUS_ALREADY_REVERTED:$style = 'color: #00BFFF'; 	$action_str = '('.$lang_admin_plugin_patcher['ALREADY REVERTED'].')'; break;
						}

?>
						<tr>
							<td>
<?php if (isset($cur_substep['command'])) : ?>								<span style="float: right; margin-right: 1em;"><a href="#a<?php echo ++$i ?>">#<?php echo $i ?></a></span>
								<span id="a<?php echo $i ?>" style="<?php echo $style ?>; display: block; margin-left: 1em"><?php echo pun_htmlspecialchars($cur_substep['command']).' '.$action_str ?></span><?php endif; ?>
<?php if (isset($cur_substep['code']) && trim($cur_substep['code']) != '') : ?>
								<div class="codebox"><pre style="max-height: 30em"><code><?php echo pun_htmlspecialchars($cur_substep['code']) ?></code></pre></div>
<?php endif; ?>
							</td>
						</tr>
<?php
					}
?>
					</tbody>
<?php
				}
?>
				</table>
			</div>
		</div>
<?php
			}
?>
	</div>
<?php

		}

	}
	
	else
	{
		require PUN_ROOT.'include/parser.php'; // handle_url_tag()

		$flux_mod = new FLUX_MOD($mod_id);
		if (!$flux_mod->is_valid)
			message($lang_admin_plugin_patcher['Invalid mod dir']);

		$requirements = $flux_mod->check_requirements();

		generate_admin_menu($plugin);
		echo $warning;
?>
	<div class="blockform">
		<?php mod_overview_table($flux_mod) ?>

<?php if (count($requirements['files_to_upload']) > 0 || count($requirements['directories']) > 0 || count($requirements['affected_files']) > 0) : ?>
		<h2><span><?php echo $lang_admin_plugin_patcher['Mod requirements'] ?></span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">

<?php
		$failed = false;
		$req_type = array(
			'files_to_upload' 	=> array($lang_admin_plugin_patcher['Files to upload'], $lang_admin_plugin_patcher['Files to upload info']),
			'directories' 		=> array($lang_admin_plugin_patcher['Directories'], $lang_admin_plugin_patcher['Directories info']), 
			'affected_files' 	=> array($lang_admin_plugin_patcher['Affected files'], $lang_admin_plugin_patcher['Affected files info'])
		);
		foreach ($requirements as $type => $cur_requirements)
		{
			if (count($cur_requirements) == 0)
				continue;

?>
					<fieldset>
						<legend><?php echo $req_type[$type][0] ?></legend>
						<div class="infldset">
							<table>
								<p><?php echo $req_type[$type][1] ?></p>
<?php
			foreach ($cur_requirements as $text => $cur_requirement)
			{
				if ($cur_requirement[0])
					$status = '<strong style="color: green">'.$cur_requirement[1].'</strong>';
				else
				{
					$status = '<strong style="color: red">'.$cur_requirement[2].'</strong>';
					if (!$failed) $failed = true;
				}
				echo '<tr><td style="width: 50%">'.pun_htmlspecialchars($text).'</td><td>'.$status.'</td></tr>';
			}
?>
							</table>
						</div>
					</fieldset>
<?php
		}
?>

<?php if ($failed) : ?>
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Unmet requirements'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_plugin_patcher['Unmet requirements info'] ?></p>
						</div>
					</fieldset>
<?php endif; ?>
				</div>
			</div>
<?php if ($action == 'uninstall') : ?>
		</div>
		<h2><span><?php echo $lang_admin_plugin_patcher['Mod uninstallation'] ?></span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Warning'] ?></legend>
						<div class="infldset">
							<p style="color: #a00"><strong><?php echo $lang_admin_plugin_patcher['Uninstall warning'] ?></strong></p>
						</div>
					</fieldset>
				</div>
			</div>
<?php endif; ?>
<?php else : ?>
		<h2><span></span></h2>
		<div class="box">
<?php endif; ?>
			<form method="post" action="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod_id).'&action='.$action.(isset($_GET['skip_install']) ? '&skip_install' : '') ?>">
				<div class="inform">
					<p class="buttons">
<?php if ($failed) : ?>						<input type="submit" name="check_again" value="<?php echo $lang_admin_plugin_patcher['Check again'] ?>" /><?php endif; ?>
						<input type="submit" name="install" value="<?php echo $lang_admin_plugin_patcher[ucfirst($action)] ?>"<?php echo $failed ? ' disabled="disabled"' : '' ?> />
						<a href="<?php echo PLUGIN_URL ?>"><?php echo $lang_admin_plugin_patcher['Return to mod list'] ?></a>
					</p>
				</div>
			</form>
		</div>
	</div>
<?php

	}
}
else
{
	generate_admin_menu($plugin);
	echo $warning;
?>
	<div class="plugin blockform">
		<h2><span><?php echo $lang_admin_plugin_patcher['Backup files head'] ?></span></h2>
		<div class="box">
			<form action="<?php echo PLUGIN_URL ?>" method="post">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Backup legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">
										<input type="hidden" name="redirect" value="1" />
										<?php echo $lang_admin_plugin_patcher['Backup filename'] ?><div><input type="submit" name="backup" value="<?php echo $lang_admin_plugin_patcher['Make backup'] ?>" tabindex="2" /></div>
									</th>
									<td>
										<input type="text" name="backup_name" value="<?php echo time() ?>" size="35" maxlength="80" tabindex="1" />
										<span><?php echo $lang_admin_plugin_patcher['Backup tool info'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>

<?php
	$backups = array();
	$dir = dir(BACKUPS_DIR);
	while ($cur_file = $dir->read())
	{
		if (substr($cur_file, 0, 1) != '.' && substr($cur_file, strlen($cur_file) - 4) == '.zip')
		{
			$time = @filemtime(BACKUPS_DIR.$cur_file);
			$backups[$time] = '<option value="'.pun_htmlspecialchars($cur_file).'">'.pun_htmlspecialchars($cur_file). ' ('.format_time($time).')</option>';
		}
		@krsort($backups);
	}

?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Revert files legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
<?php if (count($backups) > 0) : ?>
									<th scope="row">
										<?php echo $lang_admin_plugin_patcher['Revert from backup'] ?> <div><input type="submit" name="revert" value="<?php echo $lang_admin_plugin_patcher['Revert'] ?>" tabindex="4" /></div>
									</th>
									<td>
										<select name="revert_file" tabindex="3"><?php echo implode("\n\t\t\t\t", $backups); ?></select>
										<span><?php echo $lang_admin_plugin_patcher['Revert info'] ?><br /><strong><?php echo $lang_admin_plugin_patcher['Warning'] ?></strong>: <?php echo $lang_admin_plugin_patcher['Revert info 2'] ?></span>
									</td>
<?php else : ?>
									<td><?php echo $lang_admin_plugin_patcher['No backups'] ?></td>
<?php endif; ?>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>

		<h2><span><?php echo $lang_admin_plugin_patcher['Upload modification head'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo PLUGIN_URL ?>" enctype="multipart/form-data">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Upload modification legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
<?php if (is_writable(MODS_DIR)) : ?>
									<th scope="row">
										<?php echo $lang_admin_plugin_patcher['Upload package'] ?> <div><input type="submit" name="upload" value="<?php echo $lang_admin_plugin_patcher['Upload'] ?>" tabindex="6" /></div>
									</th>
									<td>
										<input type="file" name="upload_mod" tabindex="5" />
										<span><?php echo $lang_admin_plugin_patcher['Upload package info'] ?></span>
									</td>
<?php else : ?>
									<td><?php echo $lang_admin_plugin_patcher['Mods directory not writable'] ?></td>
<?php endif; ?>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>


	<div class="plugin blockform">
		<h2><span><?php echo $lang_admin_plugin_patcher['Modifications'] ?></span></h2>
		<div class="box">
			<div class="fakeform">

<?php

	if (file_exists(PUN_ROOT.'mods.php'))
		require PUN_ROOT.'mods.php';
	else
		$inst_mods = array();
	
	$mod_list = array('Installed mods' => array(), 'Mods not installed' => array(), 'Mods to download' => array());

	// Get the mod list from mods directory
	$dir = dir(MODS_DIR);
	while ($mod_id = $dir->read())
	{
		if (substr($mod_id, 0, 1) != '.' && is_dir(MODS_DIR.$mod_id))
		{
			$flux_mod = new FLUX_MOD($mod_id);
			$flux_mod->is_installed = isset($inst_mods[$flux_mod->id]);
			$flux_mod->is_enabled = isset($inst_mods[$flux_mod->id]) && !isset($inst_mods[$flux_mod->id]['disabled']);
			$section = $flux_mod->is_installed ? 'Installed mods' : 'Mods not installed';
			$mod_list[$section][$mod_id] = $flux_mod;
		}
	}
	
	// Get the mod list from the FluxBB repo
	foreach ($mod_repo['mods'] as $cur_mod)
	{
		if ($cur_mod['id'] != 'patcher' && !isset($mod_list['Installed mods'][$cur_mod['id']]) && !isset($mod_list['Mods not installed'][$cur_mod['id']]))
		{
			$repo_mod = new REPO_MOD();
			$repo_mod->id = $cur_mod['id'];
			$repo_mod->title = $cur_mod['name'];
			$repo_mod->repository_url = 'http://fluxbb.org/resources/mods/'.urldecode($cur_mod['id']).'/';
			$repo_mod->is_valid = true;
			if (isset($cur_mod['description']))
				$repo_mod->description = $cur_mod['description'];
			$mod_list['Mods to download'][$cur_mod['id']] = $repo_mod;
		}
	}

	foreach ($mod_list as $section => $mods)
	{
		$i = 0;
		uasort($mods, 'mod_title_compare'); 
?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher[$section] ?></legend>
						<div class="infldset">
<?php
		if (count($mods) == 0)
		{
?>
							<p><?php echo $lang_admin_plugin_patcher['No '.strtolower($section)] ?></p>
<?php
		}
		else
		{
?>
							<table>
								<thead>
									<tr>
										<th class="tcl"><?php echo $lang_admin_plugin_patcher['Mod title'] ?></th>
<?php if ($section != 'Mods to download') : ?>										<th class="tc2" style="width: 10%"><?php echo $lang_admin_plugin_patcher['Version'] ?></th>
										<th class="tc3" style="width: 15%"><?php echo $lang_admin_plugin_patcher['Works on FluxBB'] ?></th>
										<th class="tc3" style="width: 15%"><?php echo $lang_admin_plugin_patcher['Status'] ?></th>
<?php endif; ?>
										<th class="tcr" style="width: <?php echo $section == 'Mods to download' ? 50 : 15 ?>%"><?php echo $lang_admin_plugin_patcher['Action'] ?></th>
									</tr>
								</thead>
								<tbody>
<?php

			foreach ($mods as $flux_mod)
			{
				if (!$flux_mod->is_valid)
					continue;

				$title = array();
				$title[] = '<strong>'.pun_htmlspecialchars($flux_mod->title).'</strong>';
			
				if (isset($flux_mod->repository_url))
					$title[0] = '<a href="'.$flux_mod->repository_url.'">'.$title[0].'</a>';;

				if (isset($flux_mod->author_email))
					$title[] = ' '.$lang_admin_plugin_patcher['by'].' <a href="mailto:'.pun_htmlspecialchars($flux_mod->author_email).'">'.pun_htmlspecialchars($flux_mod->author).'</a>';
				elseif (isset($flux_mod->author))
					$title[] = ' '.$lang_admin_plugin_patcher['by'].' '.pun_htmlspecialchars($flux_mod->author);
				
				if (isset($flux_mod->description))
				{
					if (strlen($flux_mod->description) > 400)
						$title[] = '<br />'.pun_htmlspecialchars(substr($flux_mod->description, 0, 400)).'...';
					else
						$title[] = '<br />'.pun_htmlspecialchars($flux_mod->description);
				}

				// Is the mod compatible with FluxBB version
				if (get_class($flux_mod) == 'FLUX_MOD' && !$flux_mod->is_compatible())
					$title[] = '<br /><span style="color: #a00">'.$lang_admin_plugin_patcher['Unsupported version info'].'</span>';

				if (isset($flux_mod->important))
					$title[] = '<br /><span style="color: #a00"><strong>'.$lang_admin_plugin_patcher['Important'].'</strong>: '.pun_htmlspecialchars($flux_mod->important).'</span>';

				$version = '';
				if (isset($flux_mod->version))
					$version = '<strong>'.pun_htmlspecialchars($flux_mod->version).'</strong>';

				$works_on = '';
				if (isset($flux_mod->works_on))
					$works_on = pun_htmlspecialchars(implode(', ', $flux_mod->works_on));

			
?>
									<tr class="<?php echo ($i % 2 == 0) ? 'roweven' : 'rowodd' ?>">
										<td class="tcl"><?php echo implode("\n", $title) ?></td>
<?php if (get_class($flux_mod) == 'FLUX_MOD') : ?>
										<td class="tc2"><?php echo $version ?></td>
										<td class="tc3"><?php echo $works_on ?></td>

<?php if ($flux_mod->is_installed) : ?>
										<td class="tc3"><?php echo ($flux_mod->is_enabled) ? '<strong style="color: green">'.$lang_admin_plugin_patcher['Enabled'].'</strong>' : '<strong style="color: red">'.$lang_admin_plugin_patcher['Disabled'].'</strong>' ?></td>
										<td class="tcr"><a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($flux_mod->id).'&action='.($flux_mod->is_enabled ? 'disable' : 'enable') ?>"><?php echo $lang_admin_plugin_patcher[($flux_mod->is_enabled ? 'Disable' : 'Enable')] ?></a> | <a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($flux_mod->id) ?>&action=uninstall"><?php echo $lang_admin_plugin_patcher['Uninstall'] ?></a></td>
<?php else :?>
										<td class="tc3"><strong style="color: red"><?php echo $lang_admin_plugin_patcher['Not installed'] ?></strong></td>
										<td class="tcr"><a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($flux_mod->id) ?>&action=install"><?php echo $lang_admin_plugin_patcher['Install'] ?></a></td>
<?php endif; ?>
										</td>
<?php else : ?>
										<td class="tcr" colspan="4"><a href="<?php echo PLUGIN_URL ?>&download=<?php echo pun_htmlspecialchars($flux_mod->id) ?>"><?php echo $lang_admin_plugin_patcher['Download mod'] ?></a></td>
<?php endif; ?>
									</tr>
<?php
				$i++;
			}
		}
		
?>
								</tbody>
							</table>
						</div>
					</fieldset>
				</div>
<?php

	}
?>

			</div>
		</div>
	</div>
<?php

}