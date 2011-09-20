
#
#---------[ 2. OPEN ]---------------------------------------------------------
#

include/pms_new/mdl/post.php

#
#---------[ 3. FIND (line: 490) ]---------------------------------------------
#

							<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 4. REPLACE WITH ]-------------------------------------------------
#

<?php require PUN_ROOT.'mod_easy_bbcode.php'; ?>						<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 5. OPEN ]---------------------------------------------------------
#

include/pms_new/mdl/edit.php

#
#---------[ 6. FIND (line: 210) ]---------------------------------------------
#

							<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 7. REPLACE WITH ]-------------------------------------------------
#

<?php $bbcode_form = 'edit'; $bbcode_field = 'req_message'; require PUN_ROOT.'mod_easy_bbcode.php'; ?>							<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />

#
#---------[ 8. OPEN ]---------------------------------------------------------
#

include/pms_new/mdl/topic.php

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

	if ($quickpost)
		$post_actions[] = '<li class="postquickquote"><span><a onmousedown="get_quote_text();" onclick="Quote(\''.pun_htmlspecialchars($cur_post['username']).'\', \''.pun_htmlspecialchars(mysql_escape_string($cur_post['message'])).'\'); return false;" href="pmsnew.php?mdl=post&amp;tid='.$tid.'&amp;qid='.$cur_post['id'].$sidamp.'">'.$lang_easy_bbcode['Quick quote'].'</a></span></li>';

#
#---------[ 13. FIND (line: 406) ]---------------------------------------------
#

							<label><textarea name="req_message" rows="7" cols="75"  tabindex="<?php echo $cur_index++ ?>"></textarea></label>

#
#---------[ 14. REPLACE WITH ]-------------------------------------------------
#

<?php $bbcode_form = 'quickpostform'; $bbcode_field = 'req_message'; require PUN_ROOT.'mod_easy_bbcode.php'; ?>
							<label><textarea name="req_message" rows="7" cols="75"  tabindex="<?php echo $cur_index++ ?>"></textarea></label>
