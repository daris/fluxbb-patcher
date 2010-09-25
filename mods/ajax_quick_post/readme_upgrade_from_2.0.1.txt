
#
#---------[ 1. OPEN ]---------------------------------------------------------
#

footer.php

#
#---------[ 2. FIND ]---------------------------------------------
#

	if (isset($_GET['ajax']))
		exit($message);

#
#---------[ 3. REPLACE WITH ]-------------------------------------------------
#

	if (isset($_GET['ajax']))
	{
		echo $message;
		exit;
	}

#
#---------[ 4. OPEN ]---------------------------------------------------------
#

post.php

#
#---------[ 5. FIND ]---------------------------------------------
#

		if (isset($_GET['ajax']) && isset($_GET['lpid']))
			header('Location: viewtopic.php?ajax&id='.$tid.'&pcount='.intval($_GET['pcount']).'&lpid='.intval($_GET['lpid']));

#
#---------[ 6. REPLACE WITH ]---------------------------------------------------
#

		if (isset($_GET['ajax']) && isset($_GET['lpid']))
		{
			$db->end_transaction();
			$db->close();
			header('Location: viewtopic.php?ajax&id='.$tid.'&pcount='.intval($_GET['pcount']).'&lpid='.intval($_GET['lpid']));
		}

#
#---------[ 7. FIND ]---------------------------------------------
#

if (isset($_GET['ajax']) && !empty($errors))
	exit(implode("\n", $errors));

#
#---------[ 8. REPLACE WITH ]---------------------------------------------------
#

if (isset($_GET['ajax']) && !empty($errors))
{
	echo implode("\n", $errors);
	exit;
}
