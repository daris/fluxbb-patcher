##
##
##        Mod title:  Error reporting
##
##      Mod version:  1.0
##   Works on PunBB:  1.2.x
##     Release date:  2010-02-24
##           Author:  daris (daris91@gmail.com)
##
##      Description:  Error reporting MOD
##
##   Affected files:  include/functions.php
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    PunBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##
##

#
#---------[ 1. OPEN ]---------------------------------------------------------
#

include/functions.php

#
#---------[ 2. FIND (line: 321) ]-------------------------------------------------
#

<?php $page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), 'Error') ?>

#
#---------[ 3. BEFORE, ADD ]---------------------------------------------------------
#

<link rel="stylesheet" type="text/css" href="<?php echo PUN_ROOT ?>style/<?php echo (isset($GLOBALS['pun_user']['style']) ? $GLOBALS['pun_user']['style'] : 'Prosilver').'.css' ?>" />

#
#---------[ 1. OPEN ]---------------------------------------------------------
#

include/common.php

#
#---------[ 2. FIND (line: 321) ]-------------------------------------------------
#

require PUN_ROOT.'include/functions.php';

#
#---------[ 3. AFTER, ADD ]---------------------------------------------------------
#

function shutdown()
{
	if ($error = error_get_last())
	{
		switch ($error['type'])
		{
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
				error($error['message'].' (Errno: '.$error['type'].')', $error['file'], $error['line']);
				break;
		}
	}
}

register_shutdown_function('shutdown');

#
#---------[ 4. SAVE/UPLOAD ]-------------------------------------------------
#