##
##
##        Mod title:  User agent
##
##      Mod version:  1.2.1
##  Works on FluxBB:  1.4.5, 1.4.4, 1.4.3, 1.4.2, 1.4.1, 1.4.0, 1.4-rc3
##     Release date:  2011-05-11
##      Review date:  2011-05-11
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Adds a browser and system icon into each new post
##
##   Repository URL:  http://fluxbb.org/resources/mods/user-agent/
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
files/img/user_agent to /img/user_agent/
files/include/user_agent.php to include/user_agent.php

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

viewtopic.php

#
#---------[ 5. FIND (line: 10) ]---------------------------------------------
#

require PUN_ROOT.'include/common.php';

#
#---------[ 6. AFTER, ADD ]-------------------------------------------------
#

require PUN_ROOT.'include/user_agent.php';

#
#---------[ 7. FIND (line: 208) ]---------------------------------------------
#

$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.id IN ('.implode(',', $post_ids).') ORDER BY p.id', true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

#
#---------[ 8. REPLACE WITH ]---------------------------------------------------
#

$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, p.user_agent, g.g_id, g.g_user_title, o.user_id AS is_online FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.id IN ('.implode(',', $post_ids).') ORDER BY p.id', true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

#
#---------[ 9. FIND (line: 346) ]--------------------------------------------
#

<?php if (count($user_contacts)) echo "\t\t\t\t\t\t".'<dd class="usercontacts">'.implode(' ', $user_contacts).'</dd>'."\n"; ?>

#
#---------[ 10. AFTER, ADD ]-------------------------------------------------
#

<?php echo get_useragent_icons($cur_post['user_agent']); ?>

#
#---------[ 11. OPEN ]---------------------------------------------------------
#

post.php

#
#---------[ 12. FIND (line: 159) ]---------------------------------------------
#

				$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($username).'\', '.$pun_user['id'].', \''.get_remote_address().'\', \''.$db->escape($message).'\', '.$hide_smilies.', '.$now.', '.$tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());

#
#---------[ 13. REPLACE WITH ]-------------------------------------------------
#

				$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, user_agent) VALUES(\''.$db->escape($username).'\', '.$pun_user['id'].', \''.get_remote_address().'\', \''.$db->escape($message).'\', '.$hide_smilies.', '.$now.', '.$tid.', \''.$db->escape($_SERVER['HTTP_USER_AGENT']).'\')') or error('Unable to create post', __FILE__, __LINE__, $db->error());

#
#---------[ 14. FIND (line: 175) ]---------------------------------------------
#

				$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($username).'\', \''.get_remote_address().'\', '.$email_sql.', \''.$db->escape($message).'\', '.$hide_smilies.', '.$now.', '.$tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());

#
#---------[ 15. REPLACE WITH ]---------------------------------------------------
#

				$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id, user_agent) VALUES(\''.$db->escape($username).'\', \''.get_remote_address().'\', '.$email_sql.', \''.$db->escape($message).'\', '.$hide_smilies.', '.$now.', '.$tid.', \''.$db->escape($_SERVER['HTTP_USER_AGENT']).'\')') or error('Unable to create post', __FILE__, __LINE__, $db->error());

#
#---------[ 16. FIND (line: 278) ]--------------------------------------------
#

				$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($username).'\', '.$pun_user['id'].', \''.get_remote_address().'\', \''.$db->escape($message).'\', '.$hide_smilies.', '.$now.', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());

#
#---------[ 17. REPLACE WITH ]-------------------------------------------------
#

				$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id, user_agent) VALUES(\''.$db->escape($username).'\', '.$pun_user['id'].', \''.get_remote_address().'\', \''.$db->escape($message).'\', '.$hide_smilies.', '.$now.', '.$new_tid.', \''.$db->escape($_SERVER['HTTP_USER_AGENT']).'\')') or error('Unable to create post', __FILE__, __LINE__, $db->error());

#
#---------[ 18. SAVE/UPLOAD ]-------------------------------------------------
#