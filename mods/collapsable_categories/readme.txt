##
##
##        Mod title:  Collapsable Categories
##
##      Mod version:  1.5
##  Works on FluxBB:  1.2.*
##     Release date:  2010-09-06
##           Author:  Mike Lanman(justgiz@gmail.com)
##
##      Description:  Adds the abillity to collapse/expand categories. (like in Invision Power Board)
##
##   Affected files:  header.php
##                    index.php
##
##       Affects DB:  No
##
##            Notes:  1.) There are 2 other sets of icons included if you dont
##                        like the default. just upload thoes to img/ insted of
##                        the default ones. 
##
##                    2.) Per style icons are avaible. just add them to 
##                        "img/<your style>/" and keep the same file names.
##
##                    3.) In this version there is no way to use text, only
##                        images. In a sub version, ill try to find a way to be
##                        able to use text.
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

files/img/ to /img/
files/include/ to /include/


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

<script type="text/javascript" src="include/global.js"></script>


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
		$exp_img_name = strpos($div_box,'none') !== false ? 'exp_down.png' : 'exp_up.png';
		$exp_img = (is_file('img/'.$pun_user['style'].'/exp_down.png') ? 'img/'.$pun_user['style'].'/': 'img/') . $exp_img_name;

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