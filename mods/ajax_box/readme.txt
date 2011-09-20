##
##
##        Mod title:  Ajax box
##
##      Mod version:  1.0
##  Works on FluxBB:  1.4, 1.4-rc3
##     Release date:  2010-07-01
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Adds a browser and system icon into each new post
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
##
##   Affected files:  viewtopic.php
##                    post.php
##
##       Affects DB:  Yes
##
##            Notes:  This is just a template. Don't try to install it! Rows
##                    in this header should be no longer than 78 characters
##                    wide. Edit this file and save it as readme.txt. Include
##                    the file in the archive of your mod. The mod disclaimer
##                    below this paragraph must be left intact. This space
##                    would otherwise be a good space to brag about your mad
##                    modding skills :)
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

header.php

#
#---------[ 5. FIND (line: 10) ]---------------------------------------------
#

echo '<!--[if lte IE 6]><script type="text/javascript" src="style/imports/minmax.js"></script><![endif]-->'."\n";

#
#---------[ 6. BEFORE, ADD ]-------------------------------------------------
#

$page_head['jquery'] = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>';
echo '<script type="text/javascript" src="'.$pun_config['o_base_url'].'/include/ajax_box/ajax_box.js"></script><![endif]-->'."\n";

$tpl_main = str_replace('<body onload="', '<body onload="ab_onload();', $tpl_main);
$tpl_main = str_replace('<body>', '<body onload="ab_onload();">', $tpl_main);
