##
##
##        Mod title:  Ajax Post Edit
##
##      Mod version:  1.6.2
##  Works on FluxBB:  1.4.5, 1.4.4
##     Release date:  2011-04-22
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  This modification allows edit post without refreshing page (using ajax)
##
##   Repository URL:  http://fluxbb.org/resources/mods/ajax-post-edit/
##
##   Affected files:  viewtopic.php
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

files/include/ajax_post_edit/ajax_post_edit.js to /include/ajax_post_edit/ajax_post_edit.js
files/include/ajax_post_edit/edit.php to /include/ajax_post_edit/edit.php
files/include/ajax_post_edit/style.css to /include/ajax_post_edit/style.css
files/img/ajax_post_edit/loading.gif to /img/ajax_post_edit/loading.gif
files/img/ajax_post_edit/shadow.png to /img/ajax_post_edit/shadow.png
files/lang/English/ajax_post_edit.php to /lang/English/ajax_post_edit.php

#
#-------------[ 2. OPEN ]----------------
#

header.php

#
#-------------[ 3. FIND ]----
#

echo implode("\n", $page_head)."\n";

#
#-------------[ 4. BEFORE, ADD ]----------------
#

if (basename($_SERVER['PHP_SELF']) == 'viewtopic.php')
{
	$page_head['jquery'] = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>';
	
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/ajax_post_edit.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/ajax_post_edit.php';
	else
		require PUN_ROOT.'lang/English/ajax_post_edit.php';
	
	$ape = 'var base_url = \''.$pun_config['o_base_url'].'\';';
	$ape .= "\n".'var ape = {\'Loading\' : \''.$lang_ape['Loading'].'\'';
	$ape .= ', \'Quick edit\' : \''.$lang_ape['Quick Edit'].'\'';
	$ape .= ', \'Full edit\' : \''.$lang_ape['Full Edit'].'\'';
	$ape .= ', \'Cancel edit confirm\' : \''.$lang_ape['Cancel edit confirm'].'\'';
	if (isset($GLOBALS['forum_url'])) // friendly url integration
		$ape .= ', \'edit_url\' : \''.$GLOBALS['forum_url']['edit'].'\'';
	$ape .= '}';
	
	$page_head['ape'] = '<script type="text/javascript">'."\n".$ape."\n".'</script>';
	$page_head['ape_js'] = '<script type="text/javascript" src="include/ajax_post_edit/ajax_post_edit.js"></script>';
	$page_head['ape_css'] = '<link rel="stylesheet" type="text/css" href="include/ajax_post_edit/style.css" />';
}

#
#-------------[ 5. OPEN ]----------------
#

viewtopic.php

#
#-------------[ 6. FIND ]----
#

				if ($pun_user['g_edit_posts'] == '1')
					$post_actions[] = '<li class="postedit"><span><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';

#
#-------------[ 7. REPLACE WITH ]----------------
#

				if ($pun_user['g_edit_posts'] == '1') 
					$post_actions[] = '<li class="postedit"><span id="menu'.$cur_post['id'].'"><a onmouseover="ape_menu_hovered = true;" onmouseout="ape_menu_hovered = false;" onclick="if (ape_show_menu('.$cur_post['id'].')) {return true;} else {return false;}" href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';

#
#-------------[ 8. FIND ]----
#

		$post_actions[] = '<li class="postedit"><span><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
		$post_actions[] = '<li class="postquote"><span><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a></span></li>';

#
#-------------[ 9. REPLACE WITH ]----------------
#

		$post_actions[] = '<li class="postedit"><span id="menu'.$cur_post['id'].'"><a onmouseover="ape_menu_hovered = true;" onmouseout="ape_menu_hovered = false;" onclick="if (ape_show_menu('.$cur_post['id'].')) {return true;} else {return false;}" href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
		$post_actions[] = '<li class="postquote"><span><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a></span></li>';

#
#-------------[ 10. FIND ]----
#

				<div class="postmsg">

#
#-------------[ 11. REPLACE WITH ]----------------
#

				<div class="postmsg" id="post<?php echo $cur_post['id'] ?>">

#
#-------------[ 12. SAVE AND UPLOAD ]----------------
#