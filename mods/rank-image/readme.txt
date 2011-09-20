##
##
##        Mod title:  Rank image
##
##      Mod version:  1.0
##  Works on FluxBB:  1.4.5, 1.4.4, 1.4.3, 1.4.2, 1.4.1, 1.4.0, 1.4-rc3
##     Release date:  2010-07-30
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##
##      Description:  Shows rank image based on user's post count
##
##   Repository URL:  http://fluxbb.org/resources/mods/rank-image/
##
##   Affected files:  viewtopic.php
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
#---------[ 1. UPLOAD ]---------------------------------------------------------
#

files/img/rank/star.gif to /img/rank/star.gif
files/img/rank/star2.gif to /img/rank/star2.gif

files/include/rank_image.php to /include/rank_image.php

#
#---------[ 2. OPEN ]---------------------------------------------------------
#

viewtopic.php

#
#---------[ 3. FIND ]---------------------------------------------
#

require PUN_ROOT.'include/parser.php';

#
#---------[ 4. AFTER, ADD ]-------------------------------------------------
#

require PUN_ROOT.'include/rank_image.php';

#
#---------[ 5. FIND ]---------------------------------------------
#

						<dt><strong><?php echo $username ?></strong></dt>

#
#---------[ 6. AFTER, ADD ]-------------------------------------------------
#

						<dd class="userrank"><?php echo generate_rank_image($cur_post['num_posts']) ?></dd>
