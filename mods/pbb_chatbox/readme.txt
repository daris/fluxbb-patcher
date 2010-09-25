##
##
##        Mod title:  Poki BB ChatBox
##
##      Mod version:  2.1
##  Works on FluxBB:  1.4.2, 1.4.1, 1.4, 1.4-rc3
##     Release date:  2010-08-11
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##   Orginal author:  Pokemon_JOJO - pokemonjojo@mibhouse.org
##
##      Description:  Adds a simple AJAX ChatBox
##
##   Repository URL:  http://fluxbb.org/resources/mods/xxx (Leave unedited)
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

files/chatbox.php to /
files/include/lib/js/prototype.js to include/lib/js/
files/include/lib/js/chatbox.js to include/lib/js/
files/img/chatbox/loading.gif to img/chatbox/
files/install_mod.php to /
files/update_mod_from_1.0.php to /
files/lang/English/chatbox.php to lang/English
files/plugins/AP_ChatBox.php to plugins/

You can found another langage for this chatbox
on http://www.punres.org (http://www.punres.org/desc.php?pid=102)

#
#---------[ 2. RUN ]---------------------------------------------------------
#

install_mod.php

#
#---------[ 3. DELETE ]---------------------------------------------------
#

install_mod.php

#
#---------[ 4. CONFIGURE ]---------------------------------------------------------
#

Open the admin plugin and it will allow you to configure the mod.

#
#---------[ 5. ADD ]---------------------------------------------------------
#

"Additional menu items" from your Administration -> Options

X = <a href="chatbox.php">ChatBox</a>

where X is the position at which the link should be inserted
(e.g. 0 to insert at the beginning and 2 to insert after "User list").

#
#---------[ 6. LOADING IMAGE ]----------------------------------------------------
#

You can create your own image loading on http://www.ajaxload.info/
and replace loading.gif by our new image.

#
#---------[ 7. INTEGRATION ]----------------------------------------------------
#

If you want to display chatbox on index page, just add the following code:
require PUN_ROOT.'chatbox.php';

#
#---------[ 8. HAVE FUN ]----------------------------------------------------
#
