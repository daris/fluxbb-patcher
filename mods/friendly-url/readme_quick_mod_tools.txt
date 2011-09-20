
#
#---------[ 4. OPEN ]---------------------------------------------------------
#

include/common.php

#
#---------[ 5. FIND ]---------------------------------------------
#

unset($_SESSION['HTTP_REFERER']);

#
#---------[ 6. REPLACE WITH ]-------------------------------------------------
#

if (!defined('PUN_NO_REF'))
	unset($_SESSION['HTTP_REFERER']);

#
#---------[ 4. OPEN ]---------------------------------------------------------
#

include/quick_mod_tools/moderate.php

#
#---------[ 12. FIND ]---------------------------------------------
#

require PUN_ROOT.'include/common.php';

#
#---------[ 13. BEFORE, ADD ]---------------------------------------------------
#
	
define('PUN_NO_REF', 1); // do not change referer for Friendly URL mod
