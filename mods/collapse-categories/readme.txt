##
##
##        Mod title:  Collapse categories
##
##      Mod version:  1.5
##  Works on FluxBB:  1.4.5, 1.4.4
##     Release date:  2011-04-16
##      Review date:  YYYY-MM-DD (Leave unedited)
##           Author:  Daris (daris91@gmail.com)
##   Orginal author:  Mike Lanman(justgiz@gmail.com)
##
##      Description:  Adds the ability to collapse/expand categories. (like in Invision Power Board)
##
##   Repository URL:  http://fluxbb.org/resources/mods/collapse-categories/
##
##   Affected files:  header.php
##                    index.php
##
##       Affects DB:  No
##
##            Notes:  1.) There are 2 other sets of icons included if you don't
##                        like the default. just upload them to img/ instead of
##                        the default ones. 
##
##                    2.) Per style icons are available. just add them to 
##                        "img/<your style>/" and keep the same file names.
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by  
##                    FluxBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##
##


#
#---------[ 1. UPLOAD ]---------------------------------------------------
#

files/img/exp_down.png to /img/exp_down.png
files/img/exp_up.png to /img/exp_up.png
files/include/collapse.js to /include/collapse.js

#
#---------[ 2. OPEN ]----------------------------------------------------------
#

header.php

#
#---------[ 3. FIND (line:65)]-------------------------------------------------
#

<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'].'.css' ?>" />

#
#---------[ 4. AFTER ADD ]-----------------------------------------------------
#

<script type="text/javascript" src="include/collapse.js"></script>

#
#---------[ 5. OPEN ]----------------------------------------------------------
#

index.php

#
#---------[ 6. FIND (line:45)]-------------------------------------------------
#

$cur_category = 0;
$cat_count = 0;

#
#---------[ 7. AFTER ADD ]-----------------------------------------------------
#

// stuff for toggling categories
$cat_ids = (isset($_COOKIE['collapseprefs'])) ? explode(',', $_COOKIE['collapseprefs']) : array();

#
#---------[ 8. FIND (line:57)]-------------------------------------------------
#

?>
<div id="idx<?php echo $cat_count ?>" class="blocktable">

#
#---------[ 9. BEFORE, ADD ]--------------------------------------------------
#

		// Setting varibles for toggling categories
		$div_box = in_array($cat_count, $cat_ids) ? ' style="display:none"' : '';
		$exp_img_name = in_array($cat_count, $cat_ids) ? 'exp_down.png' : 'exp_up.png';
		$exp_img = (is_file(PUN_ROOT.'img/'.$pun_user['style'].'/exp_down.png') ? 'img/'.$pun_user['style'].'/' : 'img/') . $exp_img_name;

#
#---------[ 10. FIND ]-------------------------------------------------
#

					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>

#
#---------[ 11. REPLACE WITH ]--------------------------------------------------
#

					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?><img src="<?php echo $exp_img?>" onclick="togglecategory(<?php echo $cat_count?>)" style="float: right;" alt="Collapse" id="img_<?php echo $cat_count?>" /></th>
				</tr>
			</thead>
			<tbody id="box_<?php echo $cat_count ?>"<?php echo $div_box?>>

#
#---------[ 10. SAVE/UPLOAD ]--------------------------------------------------
#