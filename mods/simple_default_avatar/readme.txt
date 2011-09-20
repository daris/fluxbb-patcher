##
##
##        Mod title:  Simple Default Avatar
##
##      Mod version:  1.0
##  Works on FluxBB:  1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-07-01
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Adds default avatar to users that doesn't have their avatar
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##   Affected files:  include/functions.php
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
#---------[ 4. OPEN ]---------------------------------------------------------
#

include/functions.php

#
#---------[ 5. FIND (line: 10) ]---------------------------------------------
#

	return $avatar_markup;
}

#
#---------[ 6. BEFORE, ADD ]-------------------------------------------------
#

	if (empty($avatar_markup) && file_exists(PUN_ROOT.$pun_config['o_avatars_dir'].'/default.png') && $img_size = getimagesize(PUN_ROOT.$pun_config['o_avatars_dir'].'/default.png'))
		$avatar_markup = '<img src="'.$pun_config['o_base_url'].'/'.$pun_config['o_avatars_dir'].'/default.png'.'?m='.filemtime(PUN_ROOT.$pun_config['o_avatars_dir'].'/default.png').'" '.$img_size[3].' alt="" />';
