##
##
##        Mod title:  Easy BBCode
##
##      Mod version:  1.0.2
##  Works on FluxBB:  1.4.2, 1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-08-25
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##   Orginal author:  Rickard Andersson
##
##      Description:  This mod adds buttons for easy insertion of BBCode and
##                    smilies when posting and editing messages.
##                    Quick quote mod included.
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##   Affected files:  post.php
##                    edit.php
##                    viewtopic.php
##
##       Affects DB:  No
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at 
##                    your own risk. Backup your forum database and any and
##                    all applicable files before proceeding.
##
##


#
#---------[ 1. UPLOAD ]-------------------------------------------------------
#

files/mod_easy_bbcode.php to /
files/lang/English/easy_bbcode.php to lang/English/easy_bbcode.php

#
#---------[ 2. OPEN ]---------------------------------------------------------
#

post.php

#
#---------[ 3. FIND (line: 490) ]---------------------------------------------
#

<?php endif; ?>						<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 4. REPLACE WITH ]-------------------------------------------------
#

<?php endif; require PUN_ROOT.'mod_easy_bbcode.php'; ?>						<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 5. OPEN ]---------------------------------------------------------
#

edit.php

#
#---------[ 6. FIND (line: 210) ]---------------------------------------------
#

<?php endif; ?>						<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 7. REPLACE WITH ]-------------------------------------------------
#

<?php endif; $bbcode_form = 'edit'; $bbcode_field = 'req_message'; require PUN_ROOT.'mod_easy_bbcode.php'; ?>						<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 8. OPEN ]---------------------------------------------------------
#

viewtopic.php

#
#---------[ 9. FIND (line: 24) ]---------------------------------------------
#

require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

#
#---------[ 10. AFTER, ADD ]-------------------------------------------------
#

if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/easy_bbcode.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/easy_bbcode.php';
else
	require PUN_ROOT.'lang/English/easy_bbcode.php';

#
#---------[ 11. FIND (line: 318) ]---------------------------------------------
#

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

#
#---------[ 12. BEFORE, ADD ]-------------------------------------------------
#

	if ($pun_config['o_quickpost'] == '1' &&
		!$pun_user['is_guest'] &&
		($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1')) &&
		($cur_topic['closed'] == '0' || $is_admmod))
		$post_actions[] = '<li class="postquickquote"><span><a onmousedown="get_quote_text();" onclick="Quote(\''.pun_htmlspecialchars($cur_post['username']).'\', \''.pun_htmlspecialchars(mysql_escape_string($cur_post['message'])).'\'); return false;" href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_easy_bbcode['Quick quote'].'</a></span></li>';

#
#---------[ 13. FIND (line: 406) ]---------------------------------------------
#

<?php endif; ?>						<label><textarea name="req_message" rows="7" cols="75" tabindex="1"></textarea></label>

#
#---------[ 14. REPLACE WITH ]-------------------------------------------------
#

<?php endif; $bbcode_form = 'quickpostform'; $bbcode_field = 'req_message'; require PUN_ROOT.'mod_easy_bbcode.php'; ?>						<label><textarea name="req_message" rows="7" cols="75" tabindex="1"></textarea></label>

#
#---------[ 15. SAVE/UPLOAD ]--------------------------------------------------
#