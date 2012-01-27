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

//define('PATCHER_NO_DOWNLOAD', 1); // uncoment if you want to dsiable download feature
define('PATCHER_VERSION', '2.0-dev');
define('PATCHER_CONFIG_REV', '1');
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

error_reporting(E_ALL);
require PATCHER_ROOT.'functions.php';

if (file_exists(PATCHER_ROOT.'debug.php'))
	require PATCHER_ROOT.'debug.php';

if (file_exists(PATCHER_ROOT.'config.php'))
	require PATCHER_ROOT.'config.php';

require PATCHER_ROOT.'flux_mod.class.php';
require PATCHER_ROOT.'patcher.class.php';
require PATCHER_ROOT.'filesystem.class.php';
require PATCHER_ROOT.'zip.class.php';

// Load the language file (related to PATCHER_ROOT instead of PUN_ROOT as I have placed it somewhere else :P )
if (file_exists(PATCHER_ROOT.'../../lang/'.$pun_user['language'].'/admin_plugin_patcher.php'))
	require PATCHER_ROOT.'../../lang/'.$pun_user['language'].'/admin_plugin_patcher.php';
else
	require PATCHER_ROOT.'../../lang/English/admin_plugin_patcher.php';

$fs = new Patcher_FileSystem(isset($ftp_data) ? $ftp_data : null);

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

if (!$fs->is_writable(PUN_ROOT))
	message($lang_admin_plugin_patcher['Root directory not writable message']);

// Get the patcher directories
if (!defined('MODS_DIR'))
	define('MODS_DIR', PUN_ROOT.'mods/');

if (!is_dir(MODS_DIR))
	error('Mods directory does not exist');

if (!defined('BACKUPS_DIR'))
{
	// Try to create directory
	if (!is_dir(PUN_ROOT.'backups'))
		$fs->mkdir(PUN_ROOT.'backups');
	define('BACKUPS_DIR', PUN_ROOT.'backups/');
}

// Need sesion for storing/retrieving patching log
if (!session_id())
	session_start();

$mod_id = isset($_GET['mod_id']) ? basename($_GET['mod_id']) : null;
$action = isset($_GET['action']) && in_array($_GET['action'], array('install', 'uninstall', 'update', 'enable', 'disable', 'show_log')) ? $_GET['action'] : 'install';
$file = isset($_GET['file']) ? $_GET['file'] : 'readme.txt';

if (file_exists(PUN_ROOT.'mods.php') && !file_exists(PUN_ROOT.'patcher_config.php'))
	convertModsToConfig();

// Revert from backup
if (isset($_POST['revert']))
{
	$revert_file = isset($_POST['revert_file']) ? basename($_POST['revert_file']) : null;
	revert($revert_file);
}

// Upload modification package
if (isset($_POST['upload']))
	uploadMod();

// Download an update of mod from FluxBB repo
if (isset($_GET['download_update']))
{
	if (!isset($mod_id) || empty($mod_id))
		message($lang_common['Bad request']);

	downloadUpdate($mod_id, $_GET['download_update']);
}

// Download modification from FluxBB repo
if (isset($_GET['download']))
{
	if (empty($_GET['download']))
		message($lang_Common['Bad request']);

	downloadMod(basename($_GET['download']));
}

// Create initial backup
if ($fs->is_writable(BACKUPS_DIR) && !file_exists(BACKUPS_DIR.'fluxbb-'.FORUM_VERSION.'.zip'))
	createBackup('fluxbb-'.FORUM_VERSION);

if (isset($_POST['backup']))
{
	$backup_name = isset($_POST['backup_name']) ? basename($_POST['backup_name']) : 'fluxbb_'.time();
	createBackup($backup_name);

	redirect(PLUGIN_URL, $lang_admin_plugin_patcher['Backup created redirect']);
}
$notes = array();

// Get modification repository at http://fluxbb.org/resources/
$mod_repo = getModRepo(isset($_GET['check_for_updates']));

// Check for patcher updates
if (isset($mod_updates['patcher']['release']) && version_compare($mod_updates['patcher']['release'], PATCHER_VERSION, '>'))
	$notes[] = sprintf($lang_admin_plugin_patcher['New Patcher version available'], $mod_updates['patcher']['release'], '<a href="http://fluxbb.org/resources/mods/patcher/">'.$lang_admin_plugin_patcher['Resources page'].'</a>');

// Check needed directories to be writable
$dirs_not_writable = array();
$check_dirs = array(
	'root' => PUN_ROOT,
	'include' => PUN_ROOT.'include/',
	'lang' => PUN_ROOT.'lang/',
	'lang/English' => PUN_ROOT.'lang/English/',
	'backups' => BACKUPS_DIR,
	'mods' => MODS_DIR
);
foreach ($check_dirs as $name => $curDir)
{
	if (!$fs->is_writable($curDir))
		$dirs_not_writable[] = pun_htmlspecialchars($name);
}

// Show a warning info if there are some directories not writable
if (count($dirs_not_writable) > 0)
	$notes[] = '<strong>'.$lang_admin_plugin_patcher['Directories not writable info'].'</strong>: '.implode(', ', $dirs_not_writable).'<br />'.$lang_admin_plugin_patcher['Disabled features info'];

$warning = '';
if (count($notes) > 0)
{
	$warning .= '<div class="blockform">'."\n\t".'<h2></h2>'."\n\t".'<div class="box">'."\n\t\t".'<div class="fakeform">'."\n\t\t".'<div class="inform">'."\n\t\t\t".'<div class="forminfo">'."\n\t\t\t\t";
	foreach ($notes as $cur_note)
		$warning .= '<p>'.$cur_note.'</p>';
	$warning .= "\n\t\t\t".'</div>'."\n\t\t".'</div>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>';
}

// User wants to do some action?
if (isset($mod_id) && file_exists(MODS_DIR.$mod_id))
{
	// Load patcher config from file
	$patcher_config = array('installed_mods' => array(), 'steps' => array());
	if (file_exists(PUN_ROOT.'patcher_config.php'))
		require PUN_ROOT.'patcher_config.php';

	// Mod is installed and we want to install again
	if ($action == 'install' && isset($patcher_config['installed_mods'][$mod_id]))
		message($lang_admin_plugin_patcher['Mod already installed']);

	// Do not allow to uninstall mod if it is not installed
	elseif ($action == 'uninstall' && !isset($patcher_config['installed_mods'][$mod_id]))
		message($lang_admin_plugin_patcher['Mod already uninstalled']);

	// Mod is already enabled
	elseif ($action == 'enable' && !isset($patcher_config['installed_mods'][$mod_id]['disabled']))
		message($lang_admin_plugin_patcher['Mod already enabled']);

	// Mod is disabled and we want to disable again
	elseif ($action == 'disable' && isset($patcher_config['installed_mods'][$mod_id]['disabled']))
		message($lang_admin_plugin_patcher['Mod already disabled']);

	$mod = new Patcher_Mod($mod_id);
	if (!$mod->is_valid)
		message($lang_admin_plugin_patcher['Invalid mod dir']);

	// Get the requirenment list
	$requirements = $mod->checkRequirements();

	$_SESSION['patcher_log'] = '';
	$logs = array();

	$patcher = new Patcher($mod);
	$is_valid = true;

	// If user wants to update mod, first remove its code from files (disable mod) and then update it
	if ($action == 'update' && !isset($patcher_config['installed_mods'][$mod_id]['disabled']))
	{
		$is_valid = $patcher->executeAction('disable', true);
	}

	$is_valid &= $patcher->executeAction($action, true);

	// Do the patching
	$logs = $patcher->log;
	$done = $is_valid; // TODO: remove

	unset($_SESSION['patcher_steps']);

	if (!$is_valid)
	{
		$requirements['failed'] = true;
		$requirements = array_merge($requirements, $patcher->unmetRequirements());
		$_SESSION['patcher_steps'] = serialize($patcher->steps);
	}

	// Store logs in session as we may want to view logs in another page
	$_SESSION['patcher_logs'] = serialize($logs);

	// Do patching! :)
	if (!isset($requirements['failed']) // there are no unment requirements
		&& $is_valid
		&& (isset($_POST['install']) || /*in_array($action, array('enable', 'disable'))*/ !in_array($action, array('install', 'uninstall')))) // user clicked button on previous page or wants to enable/disable mod
	{
		$patcher->makeChanges();
		$logs = $patcher->log;
		// Store logs in session as we may want to view logs in another page
		$_SESSION['patcher_logs'] = serialize($logs);

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
<?php

		$notes = array();

		$done_info = array(
			'install'	=> $lang_admin_plugin_patcher['Mod installed'],
			'uninstall'	=> $lang_admin_plugin_patcher['Mod uninstalled'],
			'enable'	=> $lang_admin_plugin_patcher['Mod enabled'],
			'disable'	=> $lang_admin_plugin_patcher['Mod disabled'],
			'update'	=> $lang_admin_plugin_patcher['Mod updated'],
		);

		$failed_info = array(
			'install'	=> $lang_admin_plugin_patcher['Install failed'],
			'uninstall'	=> $lang_admin_plugin_patcher['Uninstall failed'],
			'enable'	=> $lang_admin_plugin_patcher['Enable failed'],
			'disable'	=> $lang_admin_plugin_patcher['Disable failed'],
			'update'	=> $lang_admin_plugin_patcher['Update failed']
		);

		$action_info = array(
			'install'	=> $lang_admin_plugin_patcher['Installing'],
			'uninstall'	=> $lang_admin_plugin_patcher['Uninstalling'],
			'enable'	=> $lang_admin_plugin_patcher['Enabling'],
			'disable'	=> $lang_admin_plugin_patcher['Disabling'],
			'update'	=> $lang_admin_plugin_patcher['Updating']
		);

		// Loop through each action
		foreach ($logs as $curAction => $log)
		{
			echo "\n\t\t\t\t\t\t".'<p>'.$action_info[$curAction].'...<br />';

			// The array containing list of actions that were done
			$actions = array();

			// Loop through each readme file of current action
			foreach ($log as $curStepList)
			{
				// Loop through each step
				foreach ($curStepList as $id => $curStep)
				{
					if (!isset($curStep['command']))
						continue;

					// Uploading... or Deleting...
					if ($curStep['command'] == 'UPLOAD')
					{
						$num_files = count(explode("\n", $curStep['substeps'][0]['code']));
						if ($curAction == 'uninstall')
							$actions[] = array($lang_admin_plugin_patcher['Deleting files'], $curStep['status'] != STATUS_NOT_DONE, '('.sprintf($lang_admin_plugin_patcher['Num files deleted'], $num_files).')');
						elseif (in_array($curAction, array('install', 'update')))
							$actions[] = array($lang_admin_plugin_patcher['Uploading files'], $curStep['status'] != STATUS_NOT_DONE, '('.sprintf($lang_admin_plugin_patcher['Num files uploaded'], $num_files).')');
					}

					// Opening...
					elseif ($curStep['command'] == 'OPEN')
					{
						$steps_failed = array();
						$num_changes = $num_failed = 0;

						if (isset($curStep['substeps']))
						{
							// We're looking for any steps that failed to do
							foreach ($curStep['substeps'] as $key => $curSubStep)
							{
								if ($curSubStep['status'] == STATUS_DONE || $curSubStep['status'] == STATUS_REVERTED)
									$num_changes++;
								elseif ($curSubStep['status'] == STATUS_NOT_DONE)
								{
									if (isset($curStep['substeps'][$key-1]['command']) && $curStep['substeps'][$key-1]['command'] == 'FIND')
										$steps_failed[$key] = $key-1;
									else
										$steps_failed[$key] = $key;
								}
							}
						}
						if ($curStep['status'] == STATUS_NOT_DONE)
							$steps_failed[$id] = $id;

						$color = (count($steps_failed) > 0) ? 'red' : 'green';

						$sub_msg = array();
						if ($num_changes > 0)
							$sub_msg[] = sprintf($lang_admin_plugin_patcher['Num changes'.(in_array($curAction, array('uninstall', 'disable')) ? ' reverted' : '')], $num_changes);
						if (count($steps_failed) > 0)
						{
							$steps_failed_info = array();
							foreach ($steps_failed as $key => $s)
								$steps_failed_info[] = '<a href="'.PLUGIN_URL.'&show_log#a'.$s.'">#'.$key.'</a>';
							$sub_msg[] = sprintf($lang_admin_plugin_patcher['Num failed'], count($steps_failed)).': '.implode(', ', $steps_failed_info);
						}

						$actions[] = array(sprintf($lang_admin_plugin_patcher['Patching file'], pun_htmlspecialchars($curStep['code'])), count($steps_failed) == 0, (count($sub_msg) > 0 ? '('.implode(', ', $sub_msg).')' : ''));
					}

					// Running...
					elseif ($curStep['command'] == 'RUN' && !in_array($curAction, array('enable', 'disable')))
					{
						$new_action =  array(sprintf($lang_admin_plugin_patcher['Running'], pun_htmlspecialchars($curStep['code'])), $curStep['status'] != STATUS_NOT_DONE);
						if (isset($curStep['result']))
						{
							$result = $curStep['result'];
							if (strpos($result, "\n") !== false)
								$result = substr($result, 0, strpos($result, "\n"));
							$new_action[] = $result;
						}
						$actions[] = $new_action;
					}

					// Deleting...
					elseif ($curStep['command'] == 'DELETE' && !in_array($curAction, array('enable', 'disable')))
						$actions[] = array(sprintf($lang_admin_plugin_patcher['Deleting'], pun_htmlspecialchars($curStep['code'])), $curStep['status'] != STATUS_NOT_DONE);

					// Running code...
					elseif ($curStep['command'] == 'RUN CODE' && !in_array($curAction, array('enable', 'disable')))
						$actions[] = array($lang_admin_plugin_patcher['Running code'], $curStep['status'] != STATUS_NOT_DONE);

					// Add current note to the $notes array
					elseif ($curStep['command'] == 'NOTE' && isset($curStep['result']))
						$notes[] = $curStep['result'];
				}
			}

			// Print output of each action
			foreach ($actions as $curAction)
				echo "\n\t\t\t\t\t\t\t".'<strong style="color: '.($curAction[1] ? 'green' : 'red').'">'.$curAction[0].'</strong>... '.(isset($curAction[2]) ? $curAction[2] : '').'<br />';

			echo '</p>'."\n";
		}

?>
<?php if ($done) : ?>
							<p><strong><?php echo $lang_admin_plugin_patcher['Congratulations'] ?></strong><br /><?php echo $done_info[$action] ?></p>
<?php else: ?>
							<p><strong><?php echo $failed_info[$action] ?></strong><br /><?php echo $lang_admin_plugin_patcher['Mod patching failed'] ?></p>
							<p><strong><?php echo $lang_admin_plugin_patcher['What to do now'] ?></strong><br /><?php echo $lang_admin_plugin_patcher['Mod patching failed info 1'] ?></p>
<?php endif; ?>
	<?php 	if (count($notes) > 0)
			{
				echo "\n\t\t\t\t\t\t".'<p><strong>'.$lang_admin_plugin_patcher['Final instructions'].'</strong>';
				foreach ($notes as $cur_note)
					echo "\n\t\t\t\t\t\t\t".'<code><pre style="white-space: pre-wrap">'.pun_htmlspecialchars($cur_note).'</pre></code>';
				echo "\n\t\t\t\t\t\t".'</p>';
			} ?>
							<p>
								<a href="<?php echo PLUGIN_URL ?>&show_log"><?php echo $lang_admin_plugin_patcher['Show log'] ?></a> |
<?php if (in_array($action, array('install', 'update'))) : ?>								<a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod_id) ?>&action=update"><?php echo $lang_admin_plugin_patcher['Update'] ?></a> | <?php endif; ?>
<?php if ($action != 'uninstall') : ?>								<a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod_id) ?>&action=uninstall"><?php echo $lang_admin_plugin_patcher['Uninstall'] ?></a> |  <?php endif; ?>
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

	else
	{
		require PUN_ROOT.'include/parser.php'; // need for handle_url_tag()

		$detailed_info = array();
		// Generate mod info
		$info = '<strong>'.pun_htmlspecialchars($mod->title).' v'.pun_htmlspecialchars($mod->version).'</strong>';

		if (isset($mod->repository_url))
			$info = '<a href="'.$mod->repository_url.'">'.$info.'</a>';;

		if (isset($mod->author_email))
			$info .= ' '.$lang_admin_plugin_patcher['by'].' <a href="mailto:'.pun_htmlspecialchars($mod->author_email).'">'.pun_htmlspecialchars($mod->author).'</a>';
		elseif (isset($mod->author))
			$info .= ' '.$lang_admin_plugin_patcher['by'].' '.pun_htmlspecialchars($mod->author);

		if (isset($mod->description))
			$info .= '<br />'.pun_htmlspecialchars($mod->description);

		$detailed_info[$lang_admin_plugin_patcher['Description']] = $info;

		if (isset($mod->works_on))
			$detailed_info[$lang_admin_plugin_patcher['Works on FluxBB']] = pun_htmlspecialchars(implode(', ', $mod->works_on));

		if (isset($mod->release_date))
			$detailed_info[$lang_admin_plugin_patcher['Release date']] = pun_htmlspecialchars($mod->release_date);

		if (isset($mod->affects_db))
			$detailed_info[$lang_admin_plugin_patcher['Affects DB']] = pun_htmlspecialchars($mod->affects_db);

		generate_admin_menu($plugin);

		echo $warning;
?>
	<div class="blockform">
		<h2><span><?php echo $lang_admin_plugin_patcher['Modification overview'] ?></span></h2>
		<div id="adstats" class="box">
			<div class="inbox">
				<dl>
					<?php foreach ($detailed_info as $name => $curInfo) echo "\n\t\t\t".'<dt>'.$name.'</dt><dd>'.$curInfo.'</dd>'; ?>
				</dl>
<?php if (!$mod->isCompatible()): ?>
				<p style="color: #a00"><strong><?php echo $lang_admin_plugin_patcher['Warning'] ?>:</strong> <?php printf($lang_admin_plugin_patcher['Unsupported version'], $pun_config['o_cur_version'], pun_htmlspecialchars(implode(', ', $mod->works_on))) ?></p>
<?php endif; if (isset($mod_updates[$mod->id]['release']) && version_compare($mod_updates[$mod->id]['release'], $mod->version, '>')) : ?>
				<p style="color: #a00"><?php echo $lang_admin_plugin_patcher['Update info'].' <a href="'.PLUGIN_URL.'&update&mod_id='.urldecode($mod->id).'&version='.$mod_updates[$mod->id]['release'].'">'.sprintf($lang_admin_plugin_patcher['Download update'], pun_htmlspecialchars($mod_updates[$mod->id]['release'])) ?></a>.</p>
<?php endif; ?>
			</div>

<?php if (isset($requirements['failed'])) : ?>
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Unmet requirements'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_plugin_patcher['Unmet requirements info'] ?></p>
						</div>
					</fieldset>
<?php endif; ?>
<?php if ($action == 'uninstall') : ?>
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Warning'] ?></legend>
						<div class="infldset">
							<p style="color: #a00"><strong><?php echo $lang_admin_plugin_patcher['Uninstall warning'] ?></strong></p>
						</div>
					</fieldset>
<?php endif; ?>

			<form method="post" action="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod_id).'&action='.$action.(isset($_GET['skip_install']) ? '&skip_install' : '') ?>">
				<div class="inform">
					<p class="buttons">
<?php if (isset($requirements['failed'])) : ?>						<input type="submit" name="check_again" value="<?php echo $lang_admin_plugin_patcher['Check again'] ?>" /><?php endif; ?>
						<input type="submit" name="install" value="<?php echo $lang_admin_plugin_patcher[ucfirst($action)] ?>"<?php echo isset($requirements['failed']) ? ' disabled="disabled"' : '' ?> />
						<a href="<?php echo PLUGIN_URL ?>"><?php echo $lang_admin_plugin_patcher['Return to mod list'] ?></a>
					</p>
				</div>
			</form>
		</div>


<?php
		if (count($requirements['files_to_upload']) > 0 || count($requirements['directories']) > 0 || count($requirements['affected_files']) > 0)
		{
?>
		<h2><span><?php echo $lang_admin_plugin_patcher['Mod requirements'] ?></span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">

<?php
			$req_type = array(
				'files_to_upload' 	=> array($lang_admin_plugin_patcher['Files to upload'], $lang_admin_plugin_patcher['Files to upload info']),
				'directories' 		=> array($lang_admin_plugin_patcher['Directories'], $lang_admin_plugin_patcher['Directories info']),
				'affected_files' 	=> array($lang_admin_plugin_patcher['Affected files'], $lang_admin_plugin_patcher['Affected files info']),
				'missing_strings' 	=> array($lang_admin_plugin_patcher['Missing strings'], $lang_admin_plugin_patcher['Missing strings info'])
			);
			foreach ($requirements as $type => $curRequirements)
			{
				if (!is_array($curRequirements) || count($curRequirements) == 0)
					continue;

?>
					<fieldset>
						<legend><?php echo isset($req_type[$type][0]) ? $req_type[$type][0] : $type ?></legend>
						<div class="infldset">
							<p><?php echo isset($req_type[$type][1]) ? $req_type[$type][1] : $type ?></p>
							<table>
<?php
				foreach ($curRequirements as $curRequirement)
				{
					$status = '<strong style="color: '.($curRequirement[0] ? 'green' : 'red') .'">'.$curRequirement[2].'</strong>';

					echo '<tr><td style="width: 50%">'.pun_htmlspecialchars($curRequirement[1]).'</td><td>'.$status.'</td></tr>';
				}
?>
							</table>
						</div>
					</fieldset>
<?php
			}
?>


				</div>
			</div>
		</div>
	</div>

<?php
		}
	}
}

// Show patching log
elseif (isset($_GET['show_log']))
{
	$first = true;
	generate_admin_menu($plugin);

	if (!isset($_SESSION['patcher_logs']))
		message($lang_common['Bad request']);
	$logs = unserialize($_SESSION['patcher_logs']);

	$action_info = array(
		'install'	=> $lang_admin_plugin_patcher['Installing'],
		'uninstall'	=> $lang_admin_plugin_patcher['Uninstalling'],
		'enable'	=> $lang_admin_plugin_patcher['Enabling'],
		'disable'	=> $lang_admin_plugin_patcher['Disabling'],
		'update'	=> $lang_admin_plugin_patcher['Updating']
	);

	foreach ($logs as $curAction => $log)
	{
?>
	<div class="block blocktable">
		<h2><span><?php echo $action_info[$curAction] ?></span></h2>
	</div>
<?php

		foreach ($log as $curReadmeFile => $actions)
		{
			$curMod = substr($curReadmeFile, 0, strpos($curReadmeFile, '/'));
			$curReadme = substr($curReadmeFile, strpos($curReadmeFile, '/') + 1);
?>
	<div class="block blocktable">
		<h2<?php if ($first) $first = false; else echo ' class="block2"'; ?>><span><?php echo pun_htmlspecialchars($curMod).' » '.pun_htmlspecialchars($curReadme) ?></span></h2>
<?php
			foreach ($actions as $key => $curStep)
			{
				if (isset($curStep['command']) && isset($lang_admin_plugin_patcher[$curStep['command']]))
					$curStep['command'] = $lang_admin_plugin_patcher[$curStep['command']];

?>
		<div class="box">
			<div class="inbox">
				<table>
					<thead>
						<tr>
<?php if (isset($curStep['command']) && (isset($curStep['code']) || isset($curStep['substeps']))) : ?>
							<th id="a<?php echo $key ?>" style="<?php echo ($curStep['status'] == STATUS_NOT_DONE) ? 'font-weight: bold; color: #a00' : '' ?>"><span style="float: right"><a href="#a<?php echo $key ?>">#<?php echo $key ?></a></span><?php echo pun_htmlspecialchars($curStep['command']).' '.(isset($curStep['code']) ? pun_htmlspecialchars($curStep['code']) : '') ?></th>
<?php elseif (isset($curStep['command'])) : ?>
							<th><?php echo pun_htmlspecialchars($curStep['command']) ?></th>
<?php else : ?>
							<th><?php echo $lang_admin_plugin_patcher['Actions'] ?></th>
<?php endif; ?>
						</tr>
					</thead>
<?php if (isset($curStep['result'])) : ?>
					<tr>
						<td><?php echo /*pun_htmlspecialchars(*/$curStep['result']/*) Allow to run file show html output*/ ?></td>
					</tr>
<?php endif;
				if (isset($curStep['substeps']) && count($curStep['substeps']) > 0)
				{
?>
					<tbody>
<?php
					foreach ($curStep['substeps'] as $id => $curSubStep)
					{
						if (isset($curSubStep['command']) && isset($lang_admin_plugin_patcher[$curSubStep['command']]))
							$curSubStep['command'] = $lang_admin_plugin_patcher[$curSubStep['command']];

						$style = '';
						$comments = array();

						if (!isset($curSubStep['status']))
							$curSubStep['status'] = STATUS_UNKNOWN;

						switch ($curSubStep['status'])
						{
							case STATUS_NOT_DONE:		$style = 'font-weight: bold; color: #a00';/* $comments[] = $lang_admin_plugin_patcher['NOT DONE']*/; break;
							case STATUS_DONE:			$style = 'color: #0a0'; 		/*$comments[] = $lang_admin_plugin_patcher['DONE']*/; break;
							case STATUS_REVERTED:		$style = 'color: #00a'; 		/*$comments[] = $lang_admin_plugin_patcher['REVERTED']*/; break;
						}

						if (isset($curSubStep['comments']))
							$comments = array_merge($comments, $curSubStep['comments']);

?>
						<tr>
							<td>
<?php if (isset($curSubStep['command'])) : ?>								<span style="float: right; margin-right: 1em;"><a href="#a<?php echo $id ?>">#<?php echo $id ?></a></span>
								<span id="a<?php echo $id ?>" style="<?php echo $style ?>; display: block; margin-left: 1em"><?php echo pun_htmlspecialchars($curSubStep['command']).' '.((count($comments) > 0) ? '('.implode(', ', $comments).')' : '') ?></span><?php endif; ?>
<?php if (isset($curSubStep['code']) && trim($curSubStep['code']) != '') : ?>
								<div class="codebox"><pre style="max-height: 30em"><code><?php echo pun_htmlspecialchars($curSubStep['code']) ?></code></pre></div>
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

}

// Show modification list
else
{
	generate_admin_menu($plugin);
	echo $warning;
?>
	<div class="plugin blockform">
		<h2><span><?php echo sprintf($lang_admin_plugin_patcher['Patcher head'], PATCHER_VERSION) ?></span></h2>
		<div class="box">
			<form action="<?php echo PLUGIN_URL ?>" method="post">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Manage backups legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">
										<input type="hidden" name="redirect" value="1" />
										<?php echo $lang_admin_plugin_patcher['Backup filename'] ?><div><input type="submit"<?php echo $fs->is_writable(BACKUPS_DIR) ? '' : ' disabled="disabled"' ?> name="backup" value="<?php echo $lang_admin_plugin_patcher['Make backup'] ?>" tabindex="2" /></div>
									</th>
									<td>
										<input type="text" name="backup_name" value="<?php echo time() ?>" size="35" maxlength="80" tabindex="1" />
										<span><?php echo $lang_admin_plugin_patcher['Backup tool info'] ?></span>
									</td>
								</tr>


<?php
	$backups = array();
	$dir = dir(BACKUPS_DIR);
	while ($curFile = $dir->read())
	{
		if (substr($curFile, 0, 1) != '.' && substr($curFile, strlen($curFile) - 4) == '.zip')
		{
			$time = @filemtime(BACKUPS_DIR.$curFile);
			$backups[$time] = '<option value="'.pun_htmlspecialchars($curFile).'">'.pun_htmlspecialchars($curFile). ' ('.format_time($time).')</option>';
		}
	}
	@krsort($backups);

?>
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
									<td colspan="2"><?php echo $lang_admin_plugin_patcher['No backups'] ?></td>
<?php endif; ?>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>

			<form method="post" action="<?php echo PLUGIN_URL ?>" enctype="multipart/form-data">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_patcher['Upload modification legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">
										<?php echo $lang_admin_plugin_patcher['Upload package'] ?> <div><input type="submit"<?php echo (!$fs->is_writable(MODS_DIR)) ? ' disabled="disabled"' : '' ?> name="upload" value="<?php echo $lang_admin_plugin_patcher['Upload'] ?>" tabindex="6" /></div>
									</th>
									<td>
										<input type="file" name="upload_mod" tabindex="5" />
										<span><?php echo $lang_admin_plugin_patcher['Upload package info'] ?></span>
									</td>
								</tr>
<?php if (!$fs->is_writable(MODS_DIR)) : ?>
								<tr>
									<td colspan="2"><?php echo $lang_admin_plugin_patcher['Mods directory not writable'] ?></td>
								</tr>
<?php endif; ?>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>


	<div class="plugin blockform">
		<h2><span><?php echo $lang_admin_plugin_patcher['Modifications'] ?></span><span style="float: right; font-size: 12px"><a href="<?php echo PLUGIN_URL ?>&check_for_updates"><?php echo $lang_admin_plugin_patcher['Check for updates'] ?></a> <?php echo $lang_admin_plugin_patcher['Check for updates info'] ?></span></h2>
		<div class="box">
			<div class="fakeform">

<?php

	$patcher_config = array('installed_mods' => array(), 'steps' => array());
	if (file_exists(PUN_ROOT.'patcher_config.php'))
		require PUN_ROOT.'patcher_config.php';

	$mod_list = array('Mods failed to uninstall' => array(), 'Mods to update' => array(), 'Installed mods' => array(), 'Mods not installed' => array(), 'Mods to download' => array());

	// Get the mod list from mods directory
	$dir = dir(MODS_DIR);
	while ($mod_id = $dir->read())
	{
		if (substr($mod_id, 0, 1) == '.' || !is_dir(MODS_DIR.$mod_id) || $fs->is_empty_directory(MODS_DIR.$mod_id))
			continue;

		$mod = new Patcher_Mod($mod_id);
		if (!$mod->is_valid)
			continue;

		$mod->is_installed = isset($patcher_config['installed_mods'][$mod->id]['version']);
		$mod->is_enabled = isset($patcher_config['installed_mods'][$mod->id]) && !isset($patcher_config['installed_mods'][$mod->id]['disabled']);
		$section = $mod->is_installed ? 'Installed mods' : 'Mods not installed';

		if (isset($patcher_config['installed_mods'][$mod->id]['uninstall_failed']))
			$section = 'Mods failed to uninstall';

		// Look for updates
		if ($mod->is_installed)
		{
			$has_update = array();
			// new update in local copy
			if (isset($patcher_config['installed_mods'][$mod_id]['version']) && version_compare($mod->version, $patcher_config['installed_mods'][$mod_id]['version'], '>'))
				$has_update['local'] = $mod->version;

			// new update available to download from fluxbb.org repo
			if (isset($mod_repo['mods'][$mod->id]['last_release']['version']) && version_compare($mod_repo['mods'][$mod->id]['last_release']['version'], $patcher_config['installed_mods'][$mod_id]['version'], '>'))
				$has_update['repo'] = $mod_repo['mods'][$mod->id]['last_release']['version'];

			// get newest update
			$update_version = '';
			if (isset($has_update['local']) && isset($has_update['repo']))
			{
				if (version_compare($has_update['local'], $has_update['repo'], '>='))
				{
					$update_version = $has_update['local'];
					unset($has_update['repo']);
				}
				else
				{
					$update_version = $has_update['repo'];
					unset($has_update['local']);
				}
			}
			elseif (isset($has_update['local']))
				$update_version = $has_update['local'];
			elseif (isset($has_update['repo']))
				$update_version = $has_update['repo'];

			if ($update_version != '')
			{
				$updated_mod = new Patcher_Mod($mod_id);
				$updated_mod->is_installed = $mod->is_installed;
				$updated_mod->is_enabled = $mod->is_enabled;
				if (isset($has_update['local']))
					$updated_mod->has_local_update = true;
				else
					$updated_mod->has_repo_update = true;

				$updated_mod->version = $update_version;
				$mod_list['Mods to update'][$mod_id] = $updated_mod;

				if (isset($has_update['local']))
					$mod->version = $patcher_config['installed_mods'][$mod_id]['version'];
			}
		}
		else
		{
			// new update available to download from fluxbb.org repo
			if (isset($mod_repo['mods'][$mod->id]['last_release']['version']) && version_compare($mod_repo['mods'][$mod->id]['last_release']['version'], $mod->version, '>'))
				$mod->has_repo_update = $mod_repo['mods'][$mod->id]['last_release']['version'];
		}

		$mod_list[$section][$mod_id] = $mod;
	}

	// Get the mod list from the FluxBB repo
	if (isset($mod_repo['mods']))
		foreach ($mod_repo['mods'] as $curModId => $curMod)
			if ($curModId != 'patcher' && !isset($mod_list['Installed mods'][$curModId]) && !isset($mod_list['Mods not installed'][$curModId]))
				$mod_list['Mods to download'][$curModId] = new Patcher_RepoMod($curModId, $curMod);


	foreach ($mod_list as $section => $mods)
	{
		if (in_array($section, array('Mods failed to uninstall', 'Mods to update')) && count($mods) == 0)
			continue;

		$i = 0;

		// Sort mod list using mod_title_compare function
		uasort($mods, 'modTitleCompare');
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
										<th class="tcr" style="width: 30%"><?php echo $lang_admin_plugin_patcher['Action'] ?></th>
									</tr>
								</thead>
								<tbody>
<?php

			foreach ($mods as $mod)
			{
				if (!$mod->is_valid)
					continue;

				$info = array('<strong>'.pun_htmlspecialchars($mod->title).'</strong>');

				if (isset($mod->repository_url))
					$info[0] = '<a href="'.$mod->repository_url.'">'.$info[0].'</a>';

				if (isset($mod->version))
					$info[] = ' <strong>v'.pun_htmlspecialchars($mod->version).'</strong>';

				if (isset($mod->author_email) && isset($mod->author))
					$info[] = ' '.$lang_admin_plugin_patcher['by'].' <a href="mailto:'.pun_htmlspecialchars($mod->author_email).'">'.pun_htmlspecialchars($mod->author).'</a>';
				elseif (isset($mod->author))
					$info[] = ' '.$lang_admin_plugin_patcher['by'].' '.pun_htmlspecialchars($mod->author);

				if (isset($mod->description))
				{
					if (strlen($mod->description) > 400)
						$info[] = '<br />'.pun_htmlspecialchars(substr($mod->description, 0, 400)).'...';
					else
						$info[] = '<br />'.pun_htmlspecialchars($mod->description);
				}

				// Is the mod compatible with FluxBB version
				if (get_class($mod) == 'Patcher_Mod' && !$mod->isCompatible())
					$info[] = '<br /><span style="color: #a00; display: inline">'.$lang_admin_plugin_patcher['Unsupported version info'].'</span>';

				if (isset($mod->important))
					$info[] = '<br /><span style="color: #a00"><strong>'.$lang_admin_plugin_patcher['Important'].'</strong>: '.pun_htmlspecialchars($mod->important).'</span>';

				$works_on = '';
				if (get_class($mod) == 'Patcher_Mod' && isset($mod->works_on))
					$info[] = '<br /><strong>'.$lang_admin_plugin_patcher['Works on FluxBB'].'</strong>: '.pun_htmlspecialchars(implode(', ', $mod->works_on));

				$status = '';
				$actions = array(array(), array());
				if (get_class($mod) == 'Patcher_Mod')
				{
					if ($section == 'Mods failed to uninstall')
					{
						$status = '<strong style="color: red">'.$lang_admin_plugin_patcher['Uninstall failed'].'</strong>';
						$actions[1]['uninstall'] = $lang_admin_plugin_patcher['Try again to uninstall'];
					}
					elseif ($mod->is_installed)
					{
						if ($section == 'Mods to update')
						{
							if (isset($mod->has_repo_update))
								$actions[0][] = '<a href="'.PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod->id).'&download_update='.pun_htmlspecialchars($mod->version).'&update">'.$lang_admin_plugin_patcher['Download and install update'].'</a>';

							if (isset($mod->has_local_update))
								$actions[0]['update'] = $lang_admin_plugin_patcher['Update'];
						}
						else
						{
							if ($mod->is_enabled)
							{
								$status = '<strong style="color: green">'.$lang_admin_plugin_patcher['Enabled'].'</strong>';
								$actions[1]['disable'] = $lang_admin_plugin_patcher['Disable'];
							}
							else
							{
								$status = '<strong style="color: red">'.$lang_admin_plugin_patcher['Disabled'].'</strong>';
								$actions[1]['enable'] = $lang_admin_plugin_patcher['Enable'];
							}
							$actions[1]['uninstall'] = $lang_admin_plugin_patcher['Uninstall'];
						}
					}
					else
					{
						if (isset($mod->has_repo_update))
							$actions[0][] = '<a href="'.PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod->id).'&download_update='.pun_htmlspecialchars($mod->has_repo_update).'">'.sprintf($lang_admin_plugin_patcher['Download update'], $mod->has_repo_update).'</a>';

						$status = '<strong style="color: red">'.$lang_admin_plugin_patcher['Not installed'].'</strong>';
						$actions[1]['install'] = isset($mod->has_repo_update) ? $lang_admin_plugin_patcher['Install old version'] : $lang_admin_plugin_patcher['Install'];
					}

				}
				else
					$actions[1][] = '<a href="'.PLUGIN_URL.'&download='.pun_htmlspecialchars($mod->id).'">'.$lang_admin_plugin_patcher['Download and install'].'</a>';

				$actions_info = array();
				foreach ($actions as $type => $action_list)
				{
					if (count($action_list) == 0)
						continue;

					foreach ($action_list as $action => &$title)
					{
						if (!is_numeric($action))
							$title = '<a href="'.PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($mod->id).'&action='.$action.'">'.$title.'</a>';
					}
					$actions_info[] = implode(' | ', $action_list);
				}


?>
									<tr class="mod-info <?php echo ($i % 2 == 0) ? 'roweven' : 'rowodd' ?>">
										<td><?php echo implode("\n", $info) ?></td>
										<td class="tcr">
											<?php echo ($status != '') ? $status.'<br />' : '' ?>
											<?php echo implode('<br />'."\n", $actions_info) ?>
										</td>
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
