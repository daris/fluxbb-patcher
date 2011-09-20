var var_cookieid = "";
var var_cookie_domain = "";
var var_cookie_path   = "/";

//==========================================
// Get cookie
//==========================================

function my_getcookie( name )
{
	cname = var_cookieid + name + '=';
	cpos  = document.cookie.indexOf( cname );
	
	if ( cpos != -1 )
	{
		cstart = cpos + cname.length;
		cend   = document.cookie.indexOf(";", cstart);
		
		if (cend == -1)
			cend = document.cookie.length;
	
		return unescape( document.cookie.substring(cstart, cend) );
	}
	
	return null;
}

//==========================================
// Set cookie
//==========================================

function my_setcookie(name, value, sticky)
{
	expire = "";
	domain = "";
	path   = "/";
	
	if ( sticky )
		expire = "; expires=Wed, 1 Jan 2020 00:00:00 GMT";
	
	if ( var_cookie_domain != "" )
		domain = '; domain=' + var_cookie_domain;
	
	if ( var_cookie_path != "" )
		path = var_cookie_path;
	
	document.cookie = var_cookieid + name + "=" + value + "; path=" + path + expire + domain + ';';
}

//==========================================
// Get element by id
//==========================================

function my_getbyid(id)
{
	itm = null;
	
	if (document.getElementById)
		itm = document.getElementById(id);

	else if (document.all)
		itm = document.all[id];

	else if (document.layers)
		itm = document.layers[id];

	
	return itm;
}


//==========================================
// Toggle category
//==========================================

function togglecategory(fid)
{
	saved = new Array();
	clean = new Array();
	
	//-----------------------------------
	// Get any saved info
	//-----------------------------------
	
	if (tmp = my_getcookie('collapseprefs'))
		saved = tmp.split(",");
	
	//-----------------------------------
	// Remove bit if exists
	//-----------------------------------
	
	for(i = 0 ; i < saved.length; i++)
	{
		if (saved[i] != fid && saved[i] != "")
			clean[clean.length] = saved[i];
	}
	
	//-----------------------------------
	// Add?
	//-----------------------------------
	
	if (my_getbyid('box_'+fid).style.display == "")
	{
		clean[clean.length] = fid;
		my_getbyid('box_'+fid).style.display = "none";
		my_getbyid('img_'+fid).src = my_getbyid('img_'+fid).src.replace('up','down');
	}
	else
	{
		my_getbyid('box_'+fid).style.display = "";
		my_getbyid('img_'+fid).src = my_getbyid('img_'+fid).src.replace('down','up');
	}
	
	my_setcookie('collapseprefs', clean.join(','), 1);
}