<?php
/**
 * FluxBB Patcher 2.0-alpha
 *
 * @copyright (C) 2012
 * @license GPL - GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @package Patcher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_URL', 'admin_loader.php?plugin=AP_Patcher.php');

if (!defined('PATCHER_ROOT'))
	define('PATCHER_ROOT', PUN_ROOT.'plugins/patcher/');

if (!defined('PATCHER_ROOT_URL'))
	define('PATCHER_ROOT_URL', $pun_config['o_base_url'].'/'.substr(realpath(PATCHER_ROOT.'../../'), strlen(PUN_ROOT)));

// Enable debug mode for now (remove when releasing stable version)
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

require PATCHER_ROOT.'functions.php';

// Enable error reporting when we're in the debug mode
if (defined('PUN_DEBUG'))
{
	error_reporting(E_ALL);
	set_error_handler('patcherErrorHandler');
}

// Status constants
define('STATUS_UNKNOWN', -1);
define('STATUS_NOT_DONE', 0);
define('STATUS_DONE', 1);
define('STATUS_REVERTED', 3);
define('STATUS_NOTHING_TO_DO', 5);

// Repository constants
define('PATCHER_REPO_MOD_URL', 'http://fluxbb.org/resources/mods/%s/');
define('PATCHER_REPO_RELEASE_URL', 'http://fluxbb.org/resources/mods/%s/releases/%s/%s');
define('PATCHER_MOD_API_URL', 'http://fluxbb.org/api/json/resources/mods/%s/');
define('PATCHER_MODS_API_URL', 'http://fluxbb.org/api/json/resources/mods/');

// Load configuration file
if (file_exists(PATCHER_ROOT.'config.php'))
	$config = require PATCHER_ROOT.'config.php';

require PATCHER_ROOT.'Mod.php';
require PATCHER_ROOT.'Patcher.php';
require PATCHER_ROOT.'FileSystem.php';
require PATCHER_ROOT.'Zip.php';

// Load the language file (related to PATCHER_ROOT instead of PUN_ROOT as I have placed it somewhere else :P )
if (file_exists(PATCHER_ROOT.'../../lang/'.$pun_user['language'].'/patcher.php'))
	$langPatcher = require PATCHER_ROOT.'../../lang/'.$pun_user['language'].'/patcher.php';
else
	$langPatcher = require PATCHER_ROOT.'../../lang/English/patcher.php';

if (!isset($config))
	$config = array('filesystem' => array('type' => 'Native', 'options' => array()), 'zip' => array('type' => 'Native', 'options' => array()));

$fs = Patcher_FileSystem::load($config['filesystem']['type'], $config['filesystem']['options']);

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

if (!$fs->isWritable(PUN_ROOT))
	message($langPatcher['Root directory not writable message']);

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

// Need session for storing and retrieving patching log
if (!session_id())
	session_start();

$patchActions = array('install', 'uninstall', 'update', 'enable', 'disable');

$modId = isset($_GET['mod_id']) ? basename($_GET['mod_id']) : null;
$action = isset($_GET['action']) && in_array($_GET['action'], array_merge($patchActions, array('show_log'))) ? $_GET['action'] : 'install';
$file = isset($_GET['file']) ? $_GET['file'] : 'readme.txt';

// Revert from backup
if (isset($_POST['revert']))
{
	$revertFile = isset($_POST['revert_file']) ? basename($_POST['revert_file']) : null;
	revert($revertFile);
}

// Upload modification package
if (isset($_POST['upload']))
	uploadMod();

// Download an update of mod from FluxBB repository
if (isset($_GET['download_update']))
{
	if (!isset($modId) || empty($modId))
		message($lang_common['Bad request']);

	if ($modId == 'patcher')
		downloadPatcherUpdate($_GET['download_update']);
	else
		downloadUpdate($modId, $_GET['download_update']);
}

// Download modification from FluxBB repository
if (isset($_GET['download']))
{
	if (empty($_GET['download']))
		message($lang_common['Bad request']);

	downloadMod(basename($_GET['download']));
}

// Create initial backup
if ($fs->isWritable(BACKUPS_DIR) && !file_exists(BACKUPS_DIR.'fluxbb-'.FORUM_VERSION.'.zip'))
	createBackup('fluxbb-'.FORUM_VERSION);

if (isset($_POST['backup']))
{
	$backup_name = isset($_POST['backup_name']) ? basename($_POST['backup_name']) : 'fluxbb_'.time();
	createBackup($backup_name);

	redirect(PLUGIN_URL, $langPatcher['Backup created redirect']);
}
$notes = array();

// Get modification repository
$mod_repo = getModRepo(isset($_GET['check_for_updates']));

// Check for patcher updates
$patcher_version = isset($mod_repo['mods']['patcher']['last_release']['version']) ? $mod_repo['mods']['patcher']['last_release']['version'] : null;

if (version_compare($patcher_version, Patcher::VERSION, '>'))
	$notes[] = sprintf($langPatcher['New Patcher version available'], '<a href="'.PLUGIN_URL.'&amp;mod_id=patcher&download_update='.pun_htmlspecialchars($patcher_version).'">'.$langPatcher['Download and install update'].'</a>', $patcher_version, '<a href="'.sprintf(PATCHER_REPO_MOD_URL, 'patcher').'">'.$langPatcher['Resources page'].'</a>');

// Check needed directories to be writable
$dirsNotWritable = array();
$checkDirs = array(
	'root' 			=> PUN_ROOT,
	'include' 		=> PUN_ROOT.'include/',
	'lang' 			=> PUN_ROOT.'lang/',
	'lang/English' 	=> PUN_ROOT.'lang/English/',
	'backups' 		=> BACKUPS_DIR,
	'mods' 			=> MODS_DIR
);
foreach ($checkDirs as $name => $curDir)
{
	if (!$fs->isWritable($curDir))
		$dirsNotWritable[] = pun_htmlspecialchars($name);
}

// Show a warning info if there are some directories not writable
if (count($dirsNotWritable) > 0)
	$notes[] = '<strong>'.$langPatcher['Directories not writable info'].'</strong>: '.implode(', ', $dirsNotWritable).'<br />'.$langPatcher['Disabled features info'];

$warning = '';
if (count($notes) > 0)
{
	$warning .= '<div class="blockform">'."\n\t".'<h2></h2>'."\n\t".'<div class="box">'."\n\t\t".'<div class="fakeform">'."\n\t\t".'<div class="inform">'."\n\t\t\t".'<div class="forminfo">'."\n\t\t\t\t";
	foreach ($notes as $curNote)
		$warning .= '<p>'.$curNote.'</p>';
	$warning .= "\n\t\t\t".'</div>'."\n\t\t".'</div>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>';
}

$donate_button = '<form style="float: right" action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="ZEAHSYTUXTTFJ"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1"></form>';

// User wants to do some action?
if (isset($modId) && file_exists(MODS_DIR.$modId) || isset($_POST['mods']))
{
	// Load patcher configuration from file
	$patcherConfig = loadPatcherConfig();

	$mods = isset($_POST['mods']) ? array_keys($_POST['mods']) : array($modId);
	if (isset($_POST['mods']))
	{
		foreach ($patchActions as $curAction)
			if (isset($_POST[$curAction]))
				$action = $curAction;
	}

	$installResult = array();

	foreach ($mods as $modId)
	{
		$isInstalled = isset($patcherConfig['installed_mods'][$modId]);
		$isEnabled = !isset($patcherConfig['installed_mods'][$modId]['disabled']);

		$message = '';
		// Mod is installed and we want to install again
		if ($action == 'install' && $isInstalled)
			$message = sprintf($langPatcher['Mod already installed'], $modId);

		// Do not allow to uninstall mod if it is not installed
		elseif ($action == 'uninstall' && !$isInstalled)
			$message = sprintf($langPatcher['Mod already uninstalled'], $modId);

		// Mod is already enabled
		elseif ($action == 'enable' && $isEnabled)
			$message = sprintf($langPatcher['Mod already enabled'], $modId);

		// Mod is disabled and we want to disable again
		elseif ($action == 'disable' && !$isEnabled)
			$message = sprintf($langPatcher['Mod already disabled'], $modId);

		if (!empty($message))
		{
			if (isset($_POST['mods']))
			{
				$result = array(
					$action.':'.$modId => array(
						'skipped' => array(
							'status' => STATUS_DONE,
							'message' => $message
						)
					)
				);
				$installResult = array_merge($installResult, $result);
				// Store logs in session as we may want to view logs in another page
				$_SESSION['patcher_logs'] = serialize($installResult);
				$success = true;
				continue;
			}
			else
				message($message);
		}

		$mod = Patcher_Mod::load($modId);
		if (!$mod)
			message($langPatcher['Invalid mod dir']);

		// Get the requirement list
		$requirements = $mod->checkRequirements();

		$logs = array();

		$patcher = new Patcher($mod);
		$patcher->config = $patcher->configOrg = $patcherConfig;

		$success = $isValid = true;

		// If user wants to update mod, first remove its code from files (disable mod) and then update it
		if ($action == 'update' && !isset($patcherConfig['installed_mods'][$modId]['disabled']))
			$success &= $patcher->executeAction('disable', true);

		$success &= $patcher->executeAction($action, true);

		// Do the patching
		$logs = $patcher->log;

		if (!$success)
		{
			$requirements['failed'] = true;
			$requirements = array_merge($requirements, $patcher->unmetRequirements());
			$_SESSION['patcher_steps'] = serialize($patcher->steps);
			$isValid = false;

			if (isset($_POST['mods']))
			{
				$result = array(
					$action.':'.$modId => array(
						'skipped' => array(
							'status' => STATUS_NOT_DONE,
							'message' => 'Failed to install <a href="'.PLUGIN_URL.'&amp;mod_id='.pun_htmlspecialchars($modId).'&amp;action=install">More details</a>',
						)
					)
				);
				$installResult = array_merge($installResult, $result);
				$_SESSION['patcher_logs'] = serialize($installResult);
				continue;
			}
		}

		// Do patching! :)
		if ($success && (isset($_POST['mods']) || (!in_array($action, array('install', 'uninstall')) || isset($_POST[$action])))) // user clicked button on previous page or wants to enable/disable mod
		{
			$patcher->makeChanges();
			$logs = $patcher->log;
		}

		unset($_SESSION['patcher_steps']);

		// Store logs in session as we may want to view logs in another page
		$installResult = array_merge($installResult, $logs);
		$_SESSION['patcher_logs'] = serialize($installResult);

		$patcherConfig = $patcher->config;
	}

	if (isset($_POST['mods']) || (!in_array($action, array('install', 'uninstall')) && $isValid) || isset($_POST[$action]))
	{
		generate_admin_menu($plugin);


?>
	<div class="blockform">
		<h2><span><?php echo $langPatcher['Mod installation'].$donate_button ?></span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">
					<fieldset>
						<legend><?php echo $langPatcher['Mod installation status'] ?></legend>
						<div class="infldset">
<?php

		$notes = array();

		$doneInfo = array(
			'install'	=> $langPatcher['Mod installed'],
			'uninstall'	=> $langPatcher['Mod uninstalled'],
			'enable'	=> $langPatcher['Mod enabled'],
			'disable'	=> $langPatcher['Mod disabled'],
			'update'	=> $langPatcher['Mod updated'],
		);

		$failedInfo = array(
			'install'	=> $langPatcher['Install failed'],
			'uninstall'	=> $langPatcher['Uninstall failed'],
			'enable'	=> $langPatcher['Enable failed'],
			'disable'	=> $langPatcher['Disable failed'],
			'update'	=> $langPatcher['Update failed']
		);

		$actionInfo = array(
			'install'	=> $langPatcher['Installing'],
			'uninstall'	=> $langPatcher['Uninstalling'],
			'enable'	=> $langPatcher['Enabling'],
			'disable'	=> $langPatcher['Disabling'],
			'update'	=> $langPatcher['Updating']
		);
		// Loop through each action
		foreach ($installResult as $curAct => $log)
		{
			list($curAction, $curMod) = explode(':', $curAct);
			echo "\n\t\t\t\t\t\t".'<p>'.$actionInfo[$curAction].' <strong>'.pun_htmlspecialchars($curMod).'</strong>...<br />';

			if (isset($log['skipped']))
				echo "\n\t\t\t\t\t\t".'<strong style="color: '.($log['skipped']['status'] ? 'green' : 'red').'">'.$log['skipped']['message'].'</strong><br />';

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
						$numFiles = count(explode("\n", $curStep['substeps'][0]['code']));
						if ($curAction == 'uninstall')
							$actions[] = array($langPatcher['Deleting files'], $curStep['status'] != STATUS_NOT_DONE, '('.sprintf($langPatcher['Num files deleted'], $numFiles).')');
						elseif (in_array($curAction, array('install', 'update')))
							$actions[] = array($langPatcher['Uploading files'], $curStep['status'] != STATUS_NOT_DONE, '('.sprintf($langPatcher['Num files uploaded'], $numFiles).')');
					}

					// Opening...
					elseif ($curStep['command'] == 'OPEN')
					{
						$stepsFailed = array();
						$numChanges = $numFailed = 0;

						if (isset($curStep['substeps']))
						{
							// We're looking for any steps that failed to do
							foreach ($curStep['substeps'] as $key => $curSubStep)
							{
								if ($curSubStep['status'] == STATUS_DONE || $curSubStep['status'] == STATUS_REVERTED)
									$numChanges++;
								elseif ($curSubStep['status'] == STATUS_NOT_DONE)
								{
									if (isset($curStep['substeps'][$key-1]['command']) && $curStep['substeps'][$key-1]['command'] == 'FIND')
										$stepsFailed[$key] = $key-1;
									else
										$stepsFailed[$key] = $key;
								}
							}
						}
						if ($curStep['status'] == STATUS_NOT_DONE)
							$stepsFailed[$id] = $id;

						$color = (count($stepsFailed) > 0) ? 'red' : 'green';

						$subMsg = array();
						if ($numChanges > 0)
							$subMsg[] = sprintf($langPatcher['Num changes'.(in_array($curAction, array('uninstall', 'disable')) ? ' reverted' : '')], $numChanges);
						if (count($stepsFailed) > 0)
						{
							$stepsFailedInfo = array();
							foreach ($stepsFailed as $key => $s)
								$stepsFailedInfo[] = '<a href="'.PLUGIN_URL.'&show_log#a'.$s.'">#'.$key.'</a>';
							$subMsg[] = sprintf($langPatcher['Num failed'], count($stepsFailed)).': '.implode(', ', $stepsFailedInfo);
						}

						$actions[] = array(sprintf($langPatcher['Patching file'], pun_htmlspecialchars($curStep['code'])), count($stepsFailed) == 0, (count($subMsg) > 0 ? '('.implode(', ', $subMsg).')' : ''));
					}

					// Running...
					elseif ($curStep['command'] == 'RUN' && !in_array($curAction, array('enable', 'disable')))
					{
						$newAction =  array(sprintf($langPatcher['Running'], pun_htmlspecialchars($curStep['code'])), $curStep['status'] != STATUS_NOT_DONE);
						if (isset($curStep['result']))
						{
							$result = $curStep['result'];
							if (strpos($result, "\n") !== false)
								$result = substr($result, 0, strpos($result, "\n"));
							$newAction[] = $result;
						}
						$actions[] = $newAction;
					}

					// Deleting...
					elseif ($curStep['command'] == 'DELETE' && !in_array($curAction, array('enable', 'disable')))
						$actions[] = array(sprintf($langPatcher['Deleting'], pun_htmlspecialchars($curStep['code'])), $curStep['status'] != STATUS_NOT_DONE);

					// Running code...
					elseif ($curStep['command'] == 'RUN CODE' && !in_array($curAction, array('enable', 'disable')))
						$actions[] = array($langPatcher['Running code'], $curStep['status'] != STATUS_NOT_DONE);

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
<?php if ($success) : ?>
							<p><strong><?php echo $langPatcher['Congratulations'] ?></strong><br /><?php echo $doneInfo[$action] ?></p>
<?php else: ?>
							<p><strong><?php echo $failedInfo[$action] ?></strong><br /><?php echo $langPatcher['Mod patching failed'] ?></p>
							<p><strong><?php echo $langPatcher['What to do now'] ?></strong><br /><?php echo $langPatcher['Mod patching failed info 1'] ?></p>
<?php endif; ?>
	<?php 	if (count($notes) > 0)
			{
				echo "\n\t\t\t\t\t\t".'<p><strong>'.$langPatcher['Final instructions'].'</strong>';
				foreach ($notes as $curNote)
					echo "\n\t\t\t\t\t\t\t".'<code><pre style="white-space: pre-wrap">'.pun_htmlspecialchars($curNote).'</pre></code>';
				echo "\n\t\t\t\t\t\t".'</p>';
			} ?>
							<p>
								<a href="<?php echo PLUGIN_URL ?>&amp;show_log"><?php echo $langPatcher['Show log'] ?></a> |
<?php if (!isset($_POST['mods']) && in_array($action, array('install', 'update'))) : ?>								<a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($modId) ?>&amp;action=update"><?php echo $langPatcher['Update'] ?></a> | <?php endif; ?>
<?php if (!isset($_POST['mods']) && $action != 'uninstall') : ?>								<a href="<?php echo PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($modId) ?>&amp;action=uninstall"><?php echo $langPatcher['Uninstall'] ?></a> |  <?php endif; ?>
								<a href="<?php echo PLUGIN_URL ?>"><?php echo $langPatcher['Return to mod list'] ?></a>
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

		$mod = Patcher_Mod::load($modId);
		if (!$mod)
			message($langPatcher['Invalid mod dir']);

		$detailedInfo = array();
		// Generate mod info
		$info = '<strong>'.pun_htmlspecialchars($mod->title).' v'.pun_htmlspecialchars($mod->version).'</strong>';

		if (isset($mod->repositoryUrl))
			$info = '<a href="'.$mod->repositoryUrl.'">'.$info.'</a>';;

		if (isset($mod->authorEmail))
			$info .= ' '.$langPatcher['by'].' <a href="mailto:'.pun_htmlspecialchars($mod->authorEmail).'">'.pun_htmlspecialchars($mod->author).'</a>';
		elseif (isset($mod->author))
			$info .= ' '.$langPatcher['by'].' '.pun_htmlspecialchars($mod->author);

		if (isset($mod->description))
			$info .= '<br />'.pun_htmlspecialchars($mod->description);

		$detailedInfo[$langPatcher['Description']] = $info;

		if (isset($mod->worksOn))
			$detailedInfo[$langPatcher['Supports FluxBB']] = pun_htmlspecialchars(implode(', ', $mod->worksOn));

		if (isset($mod->releaseDate))
			$detailedInfo[$langPatcher['Release date']] = pun_htmlspecialchars($mod->releaseDate);

		if (isset($mod->affectsDb))
			$detailedInfo[$langPatcher['Affects DB']] = pun_htmlspecialchars($mod->affectsDb);

		generate_admin_menu($plugin);

		echo $warning;
?>
	<div class="blockform">
		<h2><span><?php echo $langPatcher['Modification overview'].$donate_button ?></span></h2>
		<div id="adstats" class="box">
			<form method="post" action="<?php echo PLUGIN_URL.'&amp;mod_id='.pun_htmlspecialchars($modId).'&amp;action='.$action ?>">
				<div class="inbox">
					<dl>
						<?php foreach ($detailedInfo as $name => $curInfo) echo "\n\t\t\t".'<dt>'.$name.':</dt><dd>'.$curInfo.'</dd>'; ?>
					</dl>
<?php if (!$mod->isCompatible()): ?>
					<p style="color: #a00"><strong><?php echo $langPatcher['Warning'] ?>:</strong> <?php printf($langPatcher['Unsupported version'], $pun_config['o_cur_version'], pun_htmlspecialchars(implode(', ', $mod->worksOn))) ?></p>
<?php endif; if (isset($mod_repo[$mod->id]['release']) && version_compare($mod_repo[$mod->id]['release'], $mod->version, '>')) : ?>
					<p style="color: #a00"><?php echo $langPatcher['Update info'].' <a href="'.PLUGIN_URL.'&amp;update&amp;mod_id='.urldecode($mod->id).'&amp;version='.$mod_repo[$mod->id]['release'].'">'.sprintf($langPatcher['Download update'], pun_htmlspecialchars($mod_repo[$mod->id]['release'])) ?></a>.</p>
<?php endif; ?>
<?php if ($action == 'install') : ?>					<p><label><input type="checkbox" name="skip_install" value="1" /> <?php echo $langPatcher['Skip install'] ?></label></p>
<?php endif; ?>
				</div>


<?php if (isset($requirements['failed'])) : ?>
				<fieldset>
					<legend><?php echo $langPatcher['Unmet requirements'] ?></legend>
					<div class="infldset">
						<p><?php echo $langPatcher['Unmet requirements info'] ?></p>
					</div>
				</fieldset>
<?php endif; ?>
<?php if ($action == 'uninstall') : ?>
				<fieldset>
					<legend><?php echo $langPatcher['Warning'] ?></legend>
					<div class="infldset">
						<p style="color: #a00"><strong><?php echo $langPatcher['Uninstall warning'] ?></strong></p>
					</div>
				</fieldset>
<?php endif; ?>

				<div class="inform">

					<p class="buttons">
<?php if (isset($requirements['failed'])) : ?>						<input type="submit" name="check_again" value="<?php echo $langPatcher['Check again'] ?>" /><?php endif; ?>
						<input type="submit" name="<?php echo pun_htmlspecialchars($action) ?>" value="<?php echo $langPatcher[ucfirst($action)] ?>"<?php echo isset($requirements['failed']) ? ' disabled="disabled"' : '' ?> />
						<a href="<?php echo PLUGIN_URL ?>"><?php echo $langPatcher['Return to mod list'] ?></a>
					</p>
				</div>
			</form>
		</div>


<?php
		if (count($requirements['files_to_upload']) > 0 || count($requirements['directories']) > 0 || count($requirements['affected_files']) > 0)
		{
?>
		<h2><span><?php echo $langPatcher['Mod requirements'] ?></span></h2>
		<div class="box">
			<div class="fakeform">
				<div class="inform">

<?php
			$reqType = array(
				'files_to_upload' 	=> array($langPatcher['Files to upload'], $langPatcher['Files to upload info']),
				'directories' 		=> array($langPatcher['Directories'], $langPatcher['Directories info']),
				'affected_files' 	=> array($langPatcher['Affected files'], $langPatcher['Affected files info']),
				'missing_strings' 	=> array($langPatcher['Missing strings'], $langPatcher['Missing strings info'])
			);
			foreach ($requirements as $type => $curRequirements)
			{
				if (!is_array($curRequirements) || count($curRequirements) == 0)
					continue;

?>
					<fieldset>
						<legend><?php echo isset($reqType[$type][0]) ? $reqType[$type][0] : $type ?></legend>
						<div class="infldset">
							<p><?php echo isset($reqType[$type][1]) ? $reqType[$type][1] : $type ?></p>
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

	$actionInfo = array(
		'install'	=> $langPatcher['Installing'],
		'uninstall'	=> $langPatcher['Uninstalling'],
		'enable'	=> $langPatcher['Enabling'],
		'disable'	=> $langPatcher['Disabling'],
		'update'	=> $langPatcher['Updating']
	);

	foreach ($logs as $curAction => $log)
	{
?>
	<div class="block blocktable">
		<h2><span><?php echo $actionInfo[$curAction].$donate_button ?></span></h2>
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
				if (isset($curStep['command']) && isset($langPatcher[$curStep['command']]))
					$curStep['command'] = $langPatcher[$curStep['command']];

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
							<th><?php echo $langPatcher['Actions'] ?></th>
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
						if (isset($curSubStep['command']) && isset($langPatcher[$curSubStep['command']]))
							$curSubStep['command'] = $langPatcher[$curSubStep['command']];

						$style = '';
						$comments = array();

						if (!isset($curSubStep['status']))
							$curSubStep['status'] = STATUS_UNKNOWN;

						switch ($curSubStep['status'])
						{
							case STATUS_NOT_DONE:		$style = 'font-weight: bold; color: #a00';/* $comments[] = $langPatcher['NOT DONE']*/; break;
							case STATUS_DONE:			$style = 'color: #0a0'; 		/*$comments[] = $langPatcher['DONE']*/; break;
							case STATUS_REVERTED:		$style = 'color: #00a'; 		/*$comments[] = $langPatcher['REVERTED']*/; break;
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
		<h2><span><?php echo sprintf($langPatcher['Patcher head'], Patcher::VERSION).$donate_button ?></span></h2>

		<div class="box">
			<form action="<?php echo PLUGIN_URL ?>" method="post">
				<div class="inform">
					<fieldset>
						<legend><?php echo $langPatcher['Manage backups legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">
										<input type="hidden" name="redirect" value="1" />
										<?php echo $langPatcher['Backup filename'] ?><div><input type="submit"<?php echo $fs->isWritable(BACKUPS_DIR) ? '' : ' disabled="disabled"' ?> name="backup" value="<?php echo $langPatcher['Make backup'] ?>" tabindex="2" /></div>
									</th>
									<td>
										<input type="text" name="backup_name" value="<?php echo time() ?>" size="35" maxlength="80" tabindex="1" />
										<span><?php echo $langPatcher['Backup tool info'] ?></span>
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
										<?php echo $langPatcher['Revert from backup'] ?> <div><input type="submit" name="revert" value="<?php echo $langPatcher['Revert'] ?>" tabindex="4" /></div>
									</th>
									<td>
										<select name="revert_file" tabindex="3"><?php echo implode("\n\t\t\t\t", $backups); ?></select>
										<span><?php echo $langPatcher['Revert info'] ?><br /><strong><?php echo $langPatcher['Warning'] ?></strong>: <?php echo $langPatcher['Revert info 2'] ?></span>
									</td>
<?php else : ?>
									<td colspan="2"><?php echo $langPatcher['No backups'] ?></td>
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
						<legend><?php echo $langPatcher['Upload modification legend'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">
										<?php echo $langPatcher['Upload package'] ?> <div><input type="submit"<?php echo (!$fs->isWritable(MODS_DIR)) ? ' disabled="disabled"' : '' ?> name="upload" value="<?php echo $langPatcher['Upload'] ?>" tabindex="6" /></div>
									</th>
									<td>
										<input type="file" name="upload_mod" tabindex="5" />
										<span><?php echo $langPatcher['Upload package info'] ?></span>
									</td>
								</tr>
<?php if (!$fs->isWritable(MODS_DIR)) : ?>
								<tr>
									<td colspan="2"><?php echo $langPatcher['Mods directory not writable'] ?></td>
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
		<h2><span><?php echo $langPatcher['Modifications'] ?></span><span style="float: right; font-size: 12px"><a href="<?php echo PLUGIN_URL ?>&amp;check_for_updates"><?php echo $langPatcher['Check for updates'] ?></a> <?php echo $langPatcher['Check for updates info'] ?></span></h2>
		<div class="box">
			<form method="post">

<?php

	$patcherConfig = loadPatcherConfig();

	$modList = array('Mods failed to uninstall' => array(), 'Mods to update' => array(), 'Installed mods' => array(), 'Mods not installed' => array(), 'Mods to download' => array());

	// Get the mod list from mods directory
	$dir = dir(MODS_DIR);
	while ($modId = $dir->read())
	{
		if (substr($modId, 0, 1) == '.' || !is_dir(MODS_DIR.$modId) || $fs->isEmptyDir(MODS_DIR.$modId))
			continue;

		$mod = Patcher_Mod::load($modId);
		if (!$mod)
			continue;

		$mod->isInstalled = isset($patcherConfig['installed_mods'][$mod->id]['version']);
		$mod->isEnabled = isset($patcherConfig['installed_mods'][$mod->id]) && !isset($patcherConfig['installed_mods'][$mod->id]['disabled']);
		$section = $mod->isInstalled ? 'Installed mods' : 'Mods not installed';

		if (isset($patcherConfig['installed_mods'][$mod->id]['uninstall_failed']))
			$section = 'Mods failed to uninstall';

		// Look for updates
		if ($mod->isInstalled)
		{
			$hasUpdate = array();
			// new update in local copy
			if (isset($patcherConfig['installed_mods'][$modId]['version']) && version_compare($mod->version, $patcherConfig['installed_mods'][$modId]['version'], '>'))
				$hasUpdate['local'] = $mod->version;

			// new update available to download from fluxbb.org repo
			if (isset($mod_repo['mods'][$mod->id]['last_release']['version']) && version_compare($mod_repo['mods'][$mod->id]['last_release']['version'], $patcherConfig['installed_mods'][$modId]['version'], '>'))
				$hasUpdate['repo'] = $mod_repo['mods'][$mod->id]['last_release']['version'];

			// get newest update
			$updateVersion = '';
			if (isset($hasUpdate['local']) && isset($hasUpdate['repo']))
			{
				if (version_compare($hasUpdate['local'], $hasUpdate['repo'], '>='))
				{
					$updateVersion = $hasUpdate['local'];
					unset($hasUpdate['repo']);
				}
				else
				{
					$updateVersion = $hasUpdate['repo'];
					unset($hasUpdate['local']);
				}
			}
			elseif (isset($hasUpdate['local']))
				$updateVersion = $hasUpdate['local'];
			elseif (isset($hasUpdate['repo']))
				$updateVersion = $hasUpdate['repo'];

			if ($updateVersion != '')
			{
				$updatedMod = Patcher_Mod::load($modId);
				$updatedMod->isInstalled = $mod->isInstalled;
				$updatedMod->isEnabled = $mod->isEnabled;
				if (isset($hasUpdate['local']))
					$updatedMod->hasLocalUpdate = true;
				else
					$updatedMod->hasRepoUpdate = true;

				$updatedMod->version = $updateVersion;
				$modList['Mods to update'][$modId] = $updatedMod;

				if (isset($hasUpdate['local']))
					$mod->version = $patcherConfig['installed_mods'][$modId]['version'];
			}
		}
		else
		{
			// new update available to download from fluxbb.org repo
			if (isset($mod_repo['mods'][$mod->id]['last_release']['version']) && version_compare($mod_repo['mods'][$mod->id]['last_release']['version'], $mod->version, '>'))
				$mod->hasRepoUpdate = $mod_repo['mods'][$mod->id]['last_release']['version'];
		}

		$modList[$section][$modId] = $mod;
	}

	// Get the mod list from the FluxBB repo
	if (isset($mod_repo['mods']))
		foreach ($mod_repo['mods'] as $curModId => $curMod)
			if ($curModId != 'patcher' && !isset($modList['Installed mods'][$curModId]) && !isset($modList['Mods not installed'][$curModId]))
				$modList['Mods to download'][$curModId] = new Patcher_RepoMod($curModId, $curMod);


	foreach ($modList as $section => $mods)
	{
		if (in_array($section, array('Mods failed to uninstall', 'Mods to update')) && empty($mods))
			continue;

		$i = 0;

		// Sort mod list using mod_title_compare function
		uasort($mods, 'modTitleCompare');
?>
				<div class="inform">
					<fieldset>
<?php if (!empty($mods) && $section != 'Mods to download') : ?>						<div style="float: right; padding: 6px 0px 0px;"><?php echo $langPatcher['With selected'] ?>: <input type="submit" name="install" value="<?php echo $langPatcher['Install'] ?>" /> <input type="submit" name="uninstall" value="<?php echo $langPatcher['Uninstall'] ?>" /> <input type="submit" name="enable" value="<?php echo $langPatcher['Enable'] ?>" /> <input type="submit" name="disable" value="<?php echo $langPatcher['Disable'] ?>" /></div>
<?php endif; ?>
						<legend><?php echo $langPatcher[$section] ?></legend>
						<div class="infldset">
<?php if (empty($mods)) : ?>							<p><?php echo $langPatcher['No '.strtolower($section)] ?></p>
<?php else : ?>
							<table>
								<thead>
									<tr>
										<th class="tcl" colspan="2"><?php echo $langPatcher['Mod title'] ?></th>
										<th class="tcr" style="width: 170px"><?php echo $langPatcher['Action'] ?></th>
										<th style="width: 40px"><?php echo ($section != 'Mods to download') ? $langPatcher['Select'] : '&nbsp;' ?></th>
								</tr>
								</thead>
								<tbody>
<?php

			foreach ($mods as $curMod)
			{
				if (!$curMod->isValid)
					continue;

				$info = array('<strong>'.pun_htmlspecialchars($curMod->title).'</strong>');

				if (isset($curMod->repositoryUrl))
					$info[0] = '<a href="'.$curMod->repositoryUrl.'">'.$info[0].'</a>';

				if (isset($curMod->version))
					$info[] = ' <strong>v'.pun_htmlspecialchars($curMod->version).'</strong>';

				if (isset($curMod->authorEmail) && isset($curMod->author))
					$info[] = ' '.$langPatcher['by'].' <a href="mailto:'.pun_htmlspecialchars($curMod->authorEmail).'">'.pun_htmlspecialchars($curMod->author).'</a>';
				elseif (isset($curMod->author))
					$info[] = ' '.$langPatcher['by'].' '.pun_htmlspecialchars($curMod->author);

				if (isset($curMod->description))
				{
					if (utf8_strlen($curMod->description) > 400)
						$info[] = '<br />'.pun_htmlspecialchars(utf8_substr($curMod->description, 0, 400)).'...';
					else
						$info[] = '<br />'.pun_htmlspecialchars($curMod->description);
				}

				if (isset($curMod->important))
					$info[] = '<br /><span style="color: #a00"><strong>'.$langPatcher['Important'].'</strong>: '.pun_htmlspecialchars($curMod->important).'</span>';

				$works_on = '';
				if (isset($curMod->worksOn))
					$info[] = '<br /><strong>'.$langPatcher['Supports FluxBB'].'</strong>: '.pun_htmlspecialchars(implode(', ', $curMod->worksOn));

				$status = '';
				$actions = array(array(), array());
				if (get_class($curMod) != 'Patcher_RepoMod')
				{
					if ($section == 'Mods failed to uninstall')
					{
						$status = '<strong style="color: red">'.$langPatcher['Uninstall failed'].'</strong>';
						$actions[1]['uninstall'] = $langPatcher['Try again to uninstall'];
					}
					elseif ($curMod->isInstalled)
					{
						if ($section == 'Mods to update')
						{
							if (isset($curMod->hasRepoUpdate))
								$actions[0][] = '<a href="'.PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($curMod->id).'&download_update='.pun_htmlspecialchars($curMod->version).'&update">'.$langPatcher['Download and install update'].'</a>';

							if (isset($curMod->hasLocalUpdate))
								$actions[0]['update'] = $langPatcher['Update'];
						}
						else
						{
							if ($curMod->isEnabled)
							{
								$status = '<strong style="color: green">'.$langPatcher['Enabled'].'</strong>';
								$actions[1]['disable'] = $langPatcher['Disable'];
							}
							else
							{
								$status = '<strong style="color: red">'.$langPatcher['Disabled'].'</strong>';
								$actions[1]['enable'] = $langPatcher['Enable'];
							}
							$actions[1]['uninstall'] = $langPatcher['Uninstall'];
						}
					}
					else
					{
						if (isset($curMod->hasRepoUpdate))
							$actions[0][] = '<a href="'.PLUGIN_URL.'&mod_id='.pun_htmlspecialchars($curMod->id).'&download_update='.pun_htmlspecialchars($curMod->hasRepoUpdate).'">'.sprintf($langPatcher['Download update'], $curMod->hasRepoUpdate).'</a>';

						$status = '<strong style="color: red">'.$langPatcher['Not installed'].'</strong>';
						$actions[1]['install'] = isset($curMod->hasRepoUpdate) ? $langPatcher['Install old version'] : $langPatcher['Install'];
					}

				}
				else
					$actions[1][] = '<a href="'.PLUGIN_URL.'&amp;download='.pun_htmlspecialchars($curMod->id).'">'.$langPatcher['Download and install'].'</a>';

				$actionsInfo = array();
				foreach ($actions as $type => $actionList)
				{
					if (count($actionList) == 0)
						continue;

					foreach ($actionList as $action => &$title)
					{
						if (!is_numeric($action))
							$title = '<a href="'.PLUGIN_URL.'&amp;mod_id='.pun_htmlspecialchars($curMod->id).'&amp;action='.$action.'">'.$title.'</a>';
					}
					$actionsInfo[] = implode(' | ', $actionList);
				}


?>
									<tr class="mod-info <?php echo ($i % 2 == 0) ? 'roweven' : 'rowodd' ?>">
										<td style="width: 20px; background-repeat: no-repeat; background-position: center center; background-image: url(<?php echo PATCHER_ROOT_URL ?>/img/patcher/bullet_<?php echo $curMod->isCompatible() ? 'green' : 'red' ?>.png)"<?php echo $curMod->isCompatible() ? '' : ' title="'.$langPatcher['Unsupported version info'].'"' ?>>&nbsp;</td>
										<td><?php echo implode("\n", $info) ?></td>
										<td class="tcr">
											<?php echo ($status != '') ? $status.'<br />' : '' ?>
											<?php echo implode('<br />'."\n", $actionsInfo) ?>
										</td>
										<td><?php echo (get_class($curMod) != 'Patcher_RepoMod') ? '<input type="checkbox" name="mods['.pun_htmlspecialchars($curMod->id).']" value="1" />' : '&nbsp;' ?></td>
									</tr>
<?php
				$i++;
			}
endif;

?>
								</tbody>
							</table>
						</div>
					</fieldset>
				</div>
<?php

	}
?>

			</form>
		</div>
	</div>
<?php

}
