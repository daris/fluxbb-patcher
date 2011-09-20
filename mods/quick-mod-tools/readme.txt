##
##
##        Mod title:  Quick mod tools
##
##      Mod version:  1.0.1
##  Works on FluxBB:  1.4.2, 1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-09-08
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Allows moderators quickly moderate topics (edit/delete/close/stick etc.)
##
##   Repository URL:  http://fluxbb.org/resources/mods/quick-mod-tools/
##
##        Upgrading:  Convert include/quick_mod_tools/topic.php file to UTF-8 encoding.
##
##   Affected files:  viewforum.php
##                    header.php
##
##       Affects DB:  No
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at 
##                    your own risk. Backup your forum database and any and
##                    all applicable files before proceeding.
##

#
#-------------[ 1. UPLOAD ]-----------------------
#

include/ajax_post_edit/ajax_post_edit.js
include/ajax_post_edit/edit.php
include/ajax_post_edit/style.css

#
#-------------[ 2. OPEN ]----------------
#

header.php

#
#-------------[ 3. FIND ]----
#

if (isset($page_head))

#
#-------------[ 4. BEFORE, ADD ]----------------
#

if (basename($_SERVER['PHP_SELF']) == 'viewforum.php')
{
	$page_head['jquery'] = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>';
	
	$page_head['qmt_js'] = '<script type="text/javascript" src="include/quick_mod_tools/mod_tools.js"></script>';
	$page_head['qmt_css'] = '<link rel="stylesheet" type="text/css" href="include/quick_mod_tools/style.css" />';
	
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/quick_mod_tools.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/quick_mod_tools.php';
	else
		require PUN_ROOT.'lang/English/quick_mod_tools.php';
		
	$lang_qmt_js = array();
	foreach ($lang_quick_mod_tools as $key => $value)
		$lang_qmt_js[] = '\''.$key.'\': \''.$value.'\'';
	
	$page_head['qmt_lang_js'] = '<script type="text/javascript">var lang_quick_mod_tools = {'.implode(', ', $lang_qmt_js).'}</script>';
}

#
#-------------[ 5. OPEN ]----------------
#

viewforum.php

#
#-------------[ 6. FIND ]----
#

require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';


#
#-------------[ 4. AFTER, ADD ]----------------
#

require PUN_ROOT.'lang/'.$pun_user['language'].'/misc.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';
require PUN_ROOT.'include/quick_mod_tools/functions.php';

#
#-------------[ 6. FIND ]----
#

				<tr class="<?php echo $item_status ?>">
					<td class="tcl">
						<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo forum_number_format($topic_count + $start_from) ?></div></div>

#
#-------------[ 7. REPLACE WITH ]----------------
#

				<tr class="<?php echo $item_status ?>" id="t<?php echo $cur_topic['id'] ?>">
					<td class="tcl">
<?php $mod_tools = quick_mod_tools(); if (!empty($mod_tools)) : ?>						<div class="mod-tools"><?php echo implode("\n", $mod_tools) ?></div><?php endif; ?>
						<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo forum_number_format($topic_count + $start_from) ?></div></div>


#-------------[ 14. SAVE AND UPLOAD ]----------------
#