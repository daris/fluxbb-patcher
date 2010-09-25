
/* create a proper redirect URL (if we're using SEF friendly URLs) and go there */
function doQuickjumpRedirect(url, forum_names)
{
	var qjump_select = document.getElementById('qjump').elements['id'];
	var selected_forum_id = qjump_select[qjump_select.selectedIndex].value;
	url = url.replace('$1', selected_forum_id);
	url = url.replace('$2', forum_names[selected_forum_id]);
	document.location = url;
	return false;
}
