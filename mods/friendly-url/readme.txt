##
##
##        Mod title:  Friendly URL
##
##      Mod version:  1.0.6
##  Works on FluxBB:  1.4.5, 1.4.4, 1.4.3, 1.4.2, 1.4.1, 1.4, 1.4-rc3
##     Release date:  2011-05-27
##      Review date:  2011-05-27
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Backport of FluxBB 1.3 friendly URL feature
##
##   Repository URL:  http://fluxbb.org/resources/mods/friendly-url/
##
##         Updating:  Use update_1.0.5_to_1.0.6.diff patch file
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
files/.htaccess to /
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

// Convert rewritten url back to normal url
if (isset($_SERVER['HTTP_REFERER']))
{
	$_SERVER['HTTP_REFERER_REWRITTEN'] = $_SERVER['HTTP_REFERER'];
	$_SERVER['HTTP_REFERER'] = fix_referer();
}

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

#
#---------[ 14. OPEN ]---------------------------------------------------------
#

include/cache.php

#
#---------[ 15. FIND ]---------------------------------------------
#

				$output .= "\t\t\t\t".'<form id="qjump" method="get" action="viewforum.php">'."\n\t\t\t\t\t".'<div><label><span><?php echo $lang_common[\'Jump to\'] ?>'.'<br /></span>'."\n\t\t\t\t\t".'<select name="id" onchange="window.location=(\'viewforum.php?id=\'+this.options[this.selectedIndex].value)">'."\n";
#
#---------[ 16. REPLACE WITH ]---------------------------------------------------
#

				$output .= '<script type="text/javascript" src="<?php echo function_exists(\'forum_link\') ? forum_link(\'include/js/quick_jump.js\') : \'include/js/quick_jump.js\' ?>"></script>'."\n";
				$output .= "\t\t\t\t".'<form id="qjump" method="get" action="<?php echo function_exists(\'forum_link\') ? forum_link(\'viewforum.php\') : \'viewforum.php\' ?>">'."\n\t\t\t\t\t".'<div><label><span><?php echo $lang_common[\'Jump to\'] ?>'.'<br /></span>'."\n\t\t\t\t\t".'<select name="id" onchange="return doQuickjumpRedirect(forum_quickjump_url, sef_friendly_url_array);">'."\n";
				$sef_friendly_names = array();

#
#---------[ 17. FIND ]---------------------------------------------
#

					$redirect_tag = ($cur_forum['redirect_url'] != '') ? ' &gt;&gt;&gt;' : '';

#
#---------[ 18. BEFORE, ADD ]---------------------------------------------------
#

					$sef_friendly_names[$cur_forum['fid']] = sef_friendly($cur_forum['forum_name']);

#
#---------[ 19. FIND ]---------------------------------------------
#

				$output .= "\t\t\t\t\t\t".'</optgroup>'."\n\t\t\t\t\t".'</select>'."\n\t\t\t\t\t".'<input type="submit" value="<?php echo $lang_common[\'Go\'] ?>" accesskey="g" />'."\n\t\t\t\t\t".'</label></div>'."\n\t\t\t\t".'</form>'."\n";

#
#---------[ 20. REPLACE WITH ]---------------------------------------------------
#

				$output .= "\t\t\t\t\t\t".'</optgroup>'."\n\t\t\t\t\t".'</select>'."\n\t\t\t\t\t".'<input type="submit" value="<?php echo $lang_common[\'Go\'] ?>" accesskey="g" onclick="return doQuickjumpRedirect(forum_quickjump_url, sef_friendly_url_array);" />'."\n\t\t\t\t\t".'</label></div>'."\n\t\t\t\t".'</form>'."\n";
				
				$output .= '<script type="text/javascript">'."\n".'var forum_quickjump_url = "<?php echo function_exists(\'forum_link\') ? forum_link($GLOBALS[\'forum_url\'][\'forum\']) : \'viewforum.php?id=$1\' ?>";'."\n".'var sef_friendly_url_array = new Array('.$db->num_rows($result).');';
				
				foreach ($sef_friendly_names as $forum_id => $forum_name)
					$output .= "\n".'sef_friendly_url_array['.$forum_id.'] = "'.pun_htmlspecialchars($forum_name).'";';

				$output .= "\n".'</script>'."\n";

#
#---------[ 21. OPEN ]---------------------------------------------------------
#

viewtopic.php

#
#---------[ 22. FIND ]---------------------------------------------
#

define('PUN_ALLOW_INDEX', 1);

#
#---------[ 23. REPLACE WITH ]-------------------------------------------------
#

if (!$pid)
	define('PUN_ALLOW_INDEX', 1);

#
#---------[ 24. RUN (WARNING: MAKE BACKUP OF FLUXBB DIRECTORY as this script replaces all links with forum_link function!) ]------
#

gen.php

#
#---------[ 25. DELETE ]-------------------------------------------------
#

gen.php

#
#---------[ 26. SAVE/UPLOAD ]-------------------------------------------------
#

#
#---------[ 27. NOTE (for nginx server) ]-------------------------------------------------
#

For nginx server add the following code to server section of the nginx.conf file (assuming you have fluxbb in forum directory)

location /forum {
	index  index.html index.htm index.php;
	if (!-e $request_filename) {
		rewrite ^/(.+)$ /forum/rewrite.php last;
		break;
	}
}