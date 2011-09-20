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
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/admin_plugin_friendly_url.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/admin_plugin_friendly_url.php';
else
	require PUN_ROOT.'lang/English/admin_plugin_friendly_url.php';

// If the "Show text" button was clicked
if (isset($_POST['save']))
{
	// Make sure something was entered
	$url_scheme = basename($_POST['url_scheme']);
	
	$db->query('UPDATE '.$db->prefix.'config SET conf_value=\''.$db->escape($url_scheme).'\' WHERE conf_name=\'o_sef\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());

	// Load new url scheme (needed for generate_quickjump_cache function)
	$pun_config['o_sef'] = $url_scheme;
	if (file_exists(PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/forum_urls.php'))
		require PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/forum_urls.php';
	else
		require PUN_ROOT.'include/url/Default/forum_urls.php';
	
	// Regenerate the config cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();
	generate_quickjump_cache();

	redirect($_SERVER['REQUEST_URI'], $lang_admin_plugin_friendly_url['Settings updated']);
}
else // If not, we show the "Show text" form
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div class="plugin blockform">
		<h2><span>Friendly URL</span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo pun_htmlspecialchars($_SERVER['REQUEST_URI']).'&amp;action=foo' ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_plugin_friendly_url['Friendly URL scheme'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_plugin_friendly_url['URL scheme'] ?></th>
									<td>
										<select name="url_scheme" tabindex="1">
<?php

	$d = dir(PUN_ROOT.'include/url/');
	while ($f = $d->read())
	{
		if (substr($f, 0, 1) != '.' && is_dir(PUN_ROOT.'include/url/'.$f))
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$f.'"'.($pun_config['o_sef'] == $f ? ' selected="selected"' : '').'>'.str_replace('_', ' ', $f).'</option>'."\n";
	}
?>
										</select>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="buttons"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
			</form>
		</div>
	</div>
<?php

}

// Note that the script just ends here. The footer will be included by admin_loader.php