##
##
##        Mod title:  Friendly url
##
##      Mod version:  1.0.2
##  Works on FluxBB:  1.4.2, 1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-09-11
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Backport of FluxBB 1.3 friendly URL feature
##
##   Repository URL:  http://fluxbb.org/resources/mods/friendly-url/
##
##        Upgrading:  As there is no upgrade script, you have to revert
##                    FluxBB directory from backup (readme says that you
##                    have to backup files before running gen.php script)
##                    and run gen.php script again.
##
##             Note:  Install additional mods before installing this mod.
##
##   Affected files:  include/common.php
##                    include/functions.php
##                    include/cache.php
##                    viewtopic.php
##
##       Affects DB:  Yes
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at 
##                    your own risk. Backup your forum database and any and
##                    all applicable files before proceeding.
##
##


#
#---------[ 1. UPLOAD ]-------------------------------------------------
#

files/install_mod.php to /
files/gen.php to /
files/rewrite.php to /
files/.htaccess.dist to /.htaccess (remove .dist from end of filename)
files/include/friendly_url.php to /include/
files/include/url/ to /include/url/
files/include/js/quick_jump.js to /include/js/ (if directory does not exist, create it)
files/plugins/AP_Friendly_URL.php to /plugins/
files/lang/English/url_replace.php to /lang/English/
files/lang/English/admin_plugin_friendly_url.php to /lang/English/

#
#---------[ 2. RUN ]----------------------------------------------------------
#

install_mod.php

#
#---------[ 3. DELETE ]-------------------------------------------------------
#

install_mod.php

#
#---------[ 4. OPEN ]---------------------------------------------------------
#

include/common.php

#
#---------[ 5. FIND ]---------------------------------------------
#

if (!defined('PUN_ROOT'))

#
#---------[ 6. BEFORE, ADD ]-------------------------------------------------
#

// Do not load common.php twice
if (defined('FORUM_VERSION'))
	return;

#
#---------[ 7. FIND ]---------------------------------------------
#

require PUN_ROOT.'include/functions.php';

#
#---------[ 8. AFTER, ADD ]---------------------------------------------------
#

require PUN_ROOT.'include/friendly_url.php';

#
#---------[ 9. FIND ]---------------------------------------------
#

// Define standard date/time formats

#
#---------[ 10. BEFORE, ADD ]---------------------------------------------------
#

// Setup the URL rewriting scheme
if (file_exists(PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/forum_urls.php'))
	require PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/forum_urls.php';
else
	require PUN_ROOT.'include/url/Default/forum_urls.php';
	
if (!session_id())
	session_start();

// Workaround for HTTP_REFERER
$http_referer = '';
if (isset($_SESSION['HTTP_REFERER']))
	$http_referer = $_SESSION['HTTP_REFERER'];
elseif (isset($_SERVER['HTTP_REFERER']))
	$http_referer = $_SERVER['HTTP_REFERER'];

unset($_SESSION['HTTP_REFERER']);

#
#---------[ 11. OPEN ]---------------------------------------------------------
#

include/functions.php

#
#---------[ 12. FIND ]---------------------------------------------
#

function paginate($num_pages, $cur_page, $link)
{
	global $lang_common;

	$pages = array();
	$link_to_all = false;

	// If $cur_page == -1, we link to all pages (used in viewforum.php)
	if ($cur_page == -1)
	{
		$cur_page = 1;
		$link_to_all = true;
	}

	if ($num_pages <= 1)
		$pages = array('<strong class="item1">1</strong>');
	else
	{
		// Add a previous page link
		if ($num_pages > 1 && $cur_page > 1)
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.($cur_page - 1).'">'.$lang_common['Previous'].'</a>';

		if ($cur_page > 3)
		{
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p=1">1</a>';

			if ($cur_page > 5)
				$pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';
		}

		// Don't ask me how the following works. It just does, OK? :-)
		for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current)
		{
			if ($current < 1 || $current > $num_pages)
				continue;
			else if ($current != $cur_page || $link_to_all)
				$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.$current.'">'.forum_number_format($current).'</a>';
			else
				$pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';
		}

		if ($cur_page <= ($num_pages-3))
		{
			if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4))
				$pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';

			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.$num_pages.'">'.forum_number_format($num_pages).'</a>';
		}

		// Add a next page link
		if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages)
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.($cur_page +1).'">'.$lang_common['Next'].'</a>';
	}

	return implode(' ', $pages);
}

#
#---------[ 13. REPLACE WITH ]---------------------------------------------------
#
	
function paginate($num_pages, $cur_page, $link, $args = null)
{
	global $lang_common, $forum_url;

	$pages = array();
	$link_to_all = false;

	// If $cur_page == -1, we link to all pages (used in viewforum.php)
	if ($cur_page == -1)
	{
		$cur_page = 1;
		$link_to_all = true;
	}

	if ($num_pages <= 1)
		$pages = array('<strong class="item1">1</strong>');
	else
	{
		// Add a previous page link
		if ($num_pages > 1 && $cur_page > 1)
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], ($cur_page - 1), $args).'">'.$lang_common['Previous'].'</a>';

		if ($cur_page > 3)
		{
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], 1, $args).'">1</a>';

			if ($cur_page > 5)
				$pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';
		}

		// Don't ask me how the following works. It just does, OK? :-)
		for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current)
		{
			if ($current < 1 || $current > $num_pages)
				continue;
			else if ($current != $cur_page || $link_to_all)
				$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], $current, $args).'">'.forum_number_format($current).'</a>';
			else
				$pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';
		}

		if ($cur_page <= ($num_pages-3))
		{
			if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4))
				$pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';

			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], $num_pages, $args).'">'.forum_number_format($num_pages).'</a>';
		}

		// Add a next page link
		if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages)
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], ($cur_page + 1), $args).'">'.$lang_common['Next'].'</a>';
	}

	return implode(' ', $pages);
}


#
#---------[ 12. FIND ]---------------------------------------------
#

	global $pun_config, $lang_common;

	if (!preg_match('#^'.preg_quote(str_replace('www.', '', $pun_config['o_base_url']).'/'.$script, '#').'#i', str_replace('www.', '', (isset($_SERVER['HTTP_REFERER']) ? urldecode($_SERVER['HTTP_REFERER']) : ''))))

#
#---------[ 16. REPLACE WITH ]---------------------------------------------------
#

	global $pun_config, $lang_common, $http_referer;

	if (!preg_match('#^'.preg_quote(str_replace('www.', '', $pun_config['o_base_url']).'/'.$script, '#').'#i', str_replace('www.', '', urldecode($http_referer))))

#
#---------[ 14. OPEN ]---------------------------------------------------------
#

include/cache.php

#
#---------[ 15. FIND ]---------------------------------------------
#

		$output .= "\t\t\t\t".'<form id="qjump" method="get" action="viewforum.php">'."\n\t\t\t\t\t".'<div><label><span><?php echo $lang_common[\'Jump to\'] ?>'.'<br /></span>'."\n\t\t\t\t\t".'<select name="id" onchange="window.location=(\'viewforum.php?id=\'+this.options[this.selectedIndex].value)">'."\n";


		$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$group_id.') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

		$cur_category = 0;
		while ($cur_forum = $db->fetch_assoc($result))
		{
			if ($cur_forum['cid'] != $cur_category) // A new category since last iteration?
			{
				if ($cur_category)
					$output .= "\t\t\t\t\t\t".'</optgroup>'."\n";

				$output .= "\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">'."\n";
				$cur_category = $cur_forum['cid'];
			}

			$redirect_tag = ($cur_forum['redirect_url'] != '') ? ' &gt;&gt;&gt;' : '';
			$output .= "\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'"<?php echo ($forum_id == '.$cur_forum['fid'].') ? \' selected="selected"\' : \'\' ?>>'.pun_htmlspecialchars($cur_forum['forum_name']).$redirect_tag.'</option>'."\n";
		}

		$output .= "\t\t\t\t\t\t".'</optgroup>'."\n\t\t\t\t\t".'</select>'."\n\t\t\t\t\t".'<input type="submit" value="<?php echo $lang_common[\'Go\'] ?>" accesskey="g" />'."\n\t\t\t\t\t".'</label></div>'."\n\t\t\t\t".'</form>'."\n";

#
#---------[ 16. REPLACE WITH ]---------------------------------------------------
#
	
		$output .= "\t\t\t\t".'<form id="qjump" method="get" action="'.forum_link('viewforum.php').'">'."\n\t\t\t\t\t".'<div><label><span><?php echo $lang_common[\'Jump to\'] ?>'.'<br /></span>'."\n\t\t\t\t\t".'<select name="id" onchange="return doQuickjumpRedirect(forum_quickjump_url, sef_friendly_url_array);">'."\n";

		$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$group_id.') WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

		$cur_category = 0;
		while ($cur_forum = $db->fetch_assoc($result))
		{
			if ($cur_forum['cid'] != $cur_category) // A new category since last iteration?
			{
				if ($cur_category)
					$output .= "\t\t\t\t\t\t".'</optgroup>'."\n";

				$output .= "\t\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">'."\n";
				$cur_category = $cur_forum['cid'];
			}

			$sef_friendly_names[$cur_forum['fid']] = sef_friendly($cur_forum['forum_name']);
			$redirect_tag = ($cur_forum['redirect_url'] != '') ? ' &gt;&gt;&gt;' : '';
			$output .= "\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'"<?php echo ($forum_id == '.$cur_forum['fid'].') ? \' selected="selected"\' : \'\' ?>>'.pun_htmlspecialchars($cur_forum['forum_name']).$redirect_tag.'</option>'."\n";
		}

		$output .= "\t\t\t\t\t\t".'</optgroup>'."\n\t\t\t\t\t".'</select>'."\n\t\t\t\t\t".'<input type="submit" value="<?php echo $lang_common[\'Go\'] ?>" onclick="return doQuickjumpRedirect(forum_quickjump_url, sef_friendly_url_array);" accesskey="g" />'."\n\t\t\t\t\t".'</label></div>'."\n\t\t\t\t".'</form>'."\n";
		$output .= '<script type="text/javascript" src="'.forum_link('include/js/quick_jump.js').'"></script>'."\n";
		$output .= '<script type="text/javascript">'."\n".'var forum_quickjump_url = "'.forum_link($GLOBALS['forum_url']['forum']).'";'."\n".'var sef_friendly_url_array = new Array('.$db->num_rows($result).');';
		
		foreach ($sef_friendly_names as $forum_id => $forum_name)
			$output .= "\n".'sef_friendly_url_array['.$forum_id.'] = "'.pun_htmlspecialchars($forum_name).'";';

		$output .= "\n".'</script>'."\n";

#
#---------[ 17. OPEN ]---------------------------------------------------------
#

viewtopic.php

#
#---------[ 18. FIND ]---------------------------------------------
#

define('PUN_ALLOW_INDEX', 1);

#
#---------[ 19. REPLACE WITH ]-------------------------------------------------
#

if (!$pid)
	define('PUN_ALLOW_INDEX', 1);

#
#---------[ 20. OPEN ]---------------------------------------------------------
#

header.php

#
#---------[ 21. FIND ]---------------------------------------------
#

if (isset($page_head))

#
#---------[ 22. BEFORE, ADD ]-------------------------------------------------
#

$page_head['base_href'] = '<base href="'.$pun_config['o_base_url'].'/" />';

#
#---------[ 23. RUN (WARNING: MAKE BACKUP OF FLUXBB DIRECTORY as this script replaces all links with forum_link function!) ]------
#

gen.php

#
#---------[ 24. DELETE ]-------------------------------------------------
#

gen.php

#
#---------[ 25. SAVE/UPLOAD ]-------------------------------------------------
#