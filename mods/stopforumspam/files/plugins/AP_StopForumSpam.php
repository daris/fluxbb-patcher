<?php

/**
 * Copyright (C) 2008-2010 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Load the admin_plugin_example.php language file
//require PUN_ROOT.'lang/'.$admin_language.'/admin_plugin_example.php';

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

//
// The rest is up to you!
//

// If the "Show text" button was clicked
if (isset($_POST['update']))
{
	// Make sure something was entered
	$stopforumspam_api = $_POST['stopforumspam_api'];
	
	$db->query('UPDATE '.$db->prefix.'config SET conf_value=\''.$db->escape($stopforumspam_api).'\' WHERE conf_name=\'o_stopforumspam_api\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());

	// Regenerate the config cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();

	redirect($_SERVER['REQUEST_URI'], 'Settings updated');
}
else // If not, we show the "Show text" form
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div class="plugin blockform">
		<h2><span>Stop Forum Spam</span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo pun_htmlspecialchars($_SERVER['REQUEST_URI']).'&amp;action=foo' ?>">
				<div class="inform">
					<fieldset>
						<legend>Stop Forum Spam API</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Stop Forum Spam API:</th>
									<td>
										<input type="text" name="stopforumspam_api" value="<?php echo isset($pun_config['o_stopforumspam_api']) ? pun_htmlspecialchars($pun_config['o_stopforumspam_api']) : '' ?>" tabindex="1">
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="update" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
			</form>
		</div>
	</div>
<?php

}

// Note that the script just ends here. The footer will be included by admin_loader.php