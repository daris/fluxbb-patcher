##
##
##        Mod title:  Stop Forum Spam
##
##      Mod version:  1.0
##  Works on FluxBB:  1.4.2, 1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-08-24
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Stop Forum Spam
##
##              URL:  http://fluxbb.org/forums/viewtopic.php?pid=34379#p34379
##
##   Affected files:  viewtopic.php
##                    post.php
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
#---------[ 1. UPLOAD ]-------------------------------------------------------
#

install_mod.php to /

files/include/nospam.php to /include/nospam.php
files/lang/English/stopforumspam.php to /lang/English/stopforumspam.php
files/plugins/AP_StopForumSpam.php.php to /plugins/AP_StopForumSpam.php.php

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

header.php

#
#---------[ 5. FIND (line: 10) ]---------------------------------------------
#

			if (elem.name && elem.name.substring(0, 4) == "req_")

#
#---------[ 6. REPLACE WITH ]-------------------------------------------------
#

			if (elem.name && elem.name != "req_username" && elem.name.substring(0, 4) == "req_")

#
#---------[ 4. OPEN ]---------------------------------------------------------
#

profile.php

#
#---------[ 7. FIND (line: 208) ]---------------------------------------------
#

require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

#
#---------[ 8. AFTER, ADD ]---------------------------------------------------
#

if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/stopforumspam.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/stopforumspam.php';
else
	require PUN_ROOT.'lang/English/stopforumspam.php';

#
#---------[ 7. FIND (line: 208) ]---------------------------------------------
#

else if (isset($_POST['delete_user']) || isset($_POST['delete_user_comply']))

#
#---------[ 8. REPLACE WITH ]---------------------------------------------------
#

else if (isset($_POST['delete_user']) || isset($_POST['delete_spammer']) || isset($_POST['delete_user_comply']) || isset($_POST['delete_spammer_comply']))

#
#---------[ 9. FIND (line: 319) ]--------------------------------------------
#

	$result = $db->query('SELECT group_id, username FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	list($group_id, $username) = $db->fetch_row($result);

#
#---------[ 10. REPLACE WITH ]-------------------------------------------------
#

	$result = $db->query('SELECT group_id, username, email, registration_ip FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	list($group_id, $username, $email, $registration_ip) = $db->fetch_row($result);

#
#---------[ 11. FIND (line: 346) ]--------------------------------------------
#

	if (isset($_POST['delete_user_comply']))
	{

#
#---------[ 12. REPLACE WITH ]-------------------------------------------------
#

	if (isset($_POST['delete_user_comply']) || isset($_POST['delete_spammer_comply']))
 	{
		if (isset($_POST['delete_spammer_comply']))
		{
			// Include the antispam library
			require PUN_ROOT.'include/nospam.php';

 			// Lets report the bastard!
 			stopforumspam_report($registration_ip, $email, $username);
		}

#
#---------[ 11. FIND (line: 346) ]--------------------------------------------
#

		redirect('index.php', $lang_profile['User delete redirect']);

#
#---------[ 12. REPLACE WITH ]-------------------------------------------------
#

		redirect('index.php', isset($_POST['delete_spammer_comply']) ? $lang_stopforumspam['Spammer delete redirect'] : $lang_profile['User delete redirect']);

#
#---------[ 11. FIND (line: 346) ]--------------------------------------------
#

						<p class="warntext"><strong><?php echo $lang_profile['Delete warning'] ?></strong></p>

#
#---------[ 12. REPLACE WITH ]-------------------------------------------------
#

<?php if (isset($_POST['delete_spammer'])): ?>						<p><?php echo $lang_stopforumspam['Delete spammer note'] ?></p>
<?php endif; ?>						<p class="warntext"><strong><?php echo $lang_profile['Delete warning'] ?></strong></p>

#
#---------[ 11. FIND (line: 346) ]--------------------------------------------
#

							<input type="submit" name="delete_user" value="<?php echo $lang_profile['Delete user'] ?>" /> <input type="submit" name="ban" value="<?php echo $lang_profile['Ban user'] ?>" />

#
#---------[ 12. REPLACE WITH ]-------------------------------------------------
#

							<input type="submit" name="delete_user" value="<?php echo $lang_profile['Delete user'] ?>" /> <input type="submit" name="delete_spammer" value="<?php echo $lang_stopforumspam['Delete spammer'] ?>" /> <input type="submit" name="ban" value="<?php echo $lang_profile['Ban user'] ?>" />

#
#
#---------[ 13. OPEN ]---------------------------------------------------------
#

register.php

#
#---------[ 14. FIND (line: 159) ]---------------------------------------------
#

	$username = pun_trim($_POST['req_user']);

#
#---------[ 15. REPLACE WITH ]-------------------------------------------------
#

	$username = pun_trim($_POST['req_honeypot']);

#
#---------[ 16. FIND (line: 175) ]---------------------------------------------
#

	// Did everything go according to plan?

#
#---------[ 17. BEFORE, ADD ]---------------------------------------------------
#

	// Include the antispam library
	require PUN_ROOT.'include/nospam.php';

	$req_username = empty($username) ? pun_trim($_POST['req_username']) : $username;
	if (!empty($_POST['req_username']))
		$spam = SPAM_HONEYPOT;
	else if (stopforumspam_check(get_remote_address(), $email1, $req_username))
		$spam = SPAM_BLACKLIST;
	else
		$spam = SPAM_NOT;

	// Log the register attempt
	//$db->query('INSERT INTO test_registrations (username, email, email_setting, timezone, dst, ip, referer, user_agent, date, spam, errors) VALUES(\''.$db->escape($req_username).'\', \''.$db->escape($email1).'\', '.$email_setting.', '.$timezone.', '.$dst.', \''.get_remote_address().'\', \''.$db->escape($_SERVER['HTTP_REFERER']).'\', \''.$db->escape($_SERVER['HTTP_USER_AGENT']).'\', '.time().', '.$spam.', '.count($errors).')') or error('Unable to log user registration', __FILE__, __LINE__, $db->error());

	if ($spam != SPAM_NOT)
	{
		// Since we found a spammer, lets report the bastard!
		stopforumspam_report(get_remote_address(), $email1, $req_username);

		message($lang_register['Spam catch'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.');
	}

#
#---------[ 18. FIND (line: 278) ]--------------------------------------------
#

$required_fields = array('req_user' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email1' => $lang_common['Email'], 'req_email2' => $lang_common['Email'].' 2');
$focus_element = array('register', 'req_user');

#
#---------[ 19. REPLACE WITH ]-------------------------------------------------
#

$required_fields = array('req_honeypot' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email1' => $lang_common['Email'], 'req_email2' => $lang_common['Email'].' 2');
$focus_element = array('register', 'req_honeypot');
$page_head = array('<style type="text/css">#register label.usernamefield { display: none }</style>');

#
#---------[ 18. FIND (line: 278) ]--------------------------------------------
#

						<label class="required"><strong><?php echo $lang_common['Username'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_user" value="<?php if (isset($_POST['req_user'])) echo pun_htmlspecialchars($_POST['req_user']); ?>" size="25" maxlength="25" /><br /></label>

#
#---------[ 19. REPLACE WITH ]-------------------------------------------------
#

						<label class="required usernamefield"><strong><?php echo $lang_stopforumspam['If human'] ?></strong><br /><input type="text" name="req_username" value="" size="25" maxlength="25" /><br /></label>
						<label class="required"><strong><?php echo $lang_common['Username'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_honeypot" value="<?php if (isset($_POST['req_honeypot'])) echo pun_htmlspecialchars($_POST['req_honeypot']); ?>" size="25" maxlength="25" /><br /></label>

#
#---------[ 20. SAVE/UPLOAD ]-------------------------------------------------
#