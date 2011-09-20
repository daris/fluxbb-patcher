
/*
	Shows popup menu
*/
function ape_show_menu(id)
{
	if (ape_id == id)
		return false;

	var menu_span = document.getElementById('menu' + id);
	var pos = findPos(menu_span);
	pos[0] += menu_span.offsetWidth;
	pos[1] += menu_span.offsetHeight + 3;

	var menu = document.createElement('div');
	menu.setAttribute('id', 'ape_menu');

	menu.style.left =  pos[0] + 'px';
	menu.style.top =  pos[1] + 'px';

	menu.className = 'popup';
	menu.onmouseover = function () {ape_menu_hovered = true}
	menu.onmouseout = function () {ape_menu_hovered = false}

	if ('edit_url' in ape)
		var edit_url = base_url + '/' + ape['edit_url'].replace('$1', id);
	else
		var edit_url = base_url + '/edit.php?id=' + id;
	
	menu.innerHTML =
		'<ul>' +
			'<li><a id="edit' + id + '" href="' + edit_url + '" onclick="if (ape_quick_edit(' + id + ')) {return true;} else {return false;}">' + ape['Quick edit'] + '</a></li>' +
			'<li><a href="' + edit_url + '">' + ape['Full edit'] + '</a></li>' +
		'</ul>';

	document.body.appendChild(menu);
	
	document.getElementById('ape_menu').style.left = pos[0] + 4 - document.getElementById('ape_menu').clientWidth + 'px';
	
	document.onclick = function() {
			if (ape_menu_hovered == false)
				$('#ape_menu').remove(); // Hide menu
		};

	return false;
}


/*
	Sends request for post message
*/
function ape_quick_edit(id)
{
	// checking if link 'Quick Edit' exists, if not exists user probably typed on adress bar javascript:ape_quick_edit(id)
	if (!$('#edit' + id) || ape_id == id)
		return false;

	// if other post is editing, cancel edit
	if (ape_id != -1)
	{
		if (confirm(ape['Cancel edit confirm']))
			ape_cancel_edit();

		else
		{
			$('#ape_menu').remove(); // Hide menu
			return;
		}
	}

	$('#ape_menu').remove(); // Hide menu
	ape_id = id;
	ape_temp_post = $('#post' + ape_id).html();
	
	// Show loading info
	$('#post' + ape_id).html($('#post' + ape_id).html() + '<div style="float:right"><img src="' + base_url + '/img/ajax_post_edit/loading.gif"> ' + ape['Loading'] + '</div>');

	var values = {
		action: 'get',
		id: ape_id,
		csrf_token: ape['csrf_token']
	};
	$.post(ape_url, values, function(data) {ape_on_ready_get_post(data)});
	
	return false;
}


/*
	Function executed after receiving post data
*/
function ape_on_ready_get_post(data)
{
	var parsed_message = match(data, 'parsed_message');

	// If there aren't any errors
	if (parsed_message != '')
	{
		var entry_content_html = data.substring(0, data.indexOf('<!-- END FORM -->'));
		
		$('#post' + ape_id).html(entry_content_html);
		$('#postedit').focus();

		return 1;
	}

	alert(data);
	
	$('#post' + ape_id).html(ape_temp_post);
	id = -1;
}


/*
	Sends ajax request with message
*/
function ape_update_post()
{
	$('#ape-edit input, #ape-edit textarea').attr('disabled', 'disabled');

	var ape_update_values = {
		action: 'update',
		id: ape_id,
		req_message: $('#ape-message').val()
	};

	if ($('#ape-silent'))
		ape_update_values['silent'] = $('#ape-silent').attr('checked') ? 1 : 0;

	$.post(ape_url, ape_update_values, function(data) {ape_on_ready_update_post(data)});

	// Show saving info
	$('#edit_info').show();
}


/*
	Function executed after reciving update request
*/
function ape_on_ready_update_post(data)
{
	var message = match(data, 'message');
	if (message != '')
	{
		var last_edit = match(data, 'last_edit');

		$('#post' + ape_id).html(message + last_edit);
		ape_id = -1;
		
		return 1;
	}

	// Something goes wrong, show an error
	alert(data);
	
	$('#ape-edit input, #ape-edit textarea').removeAttr('disabled');
	
	// Hide saving info
	$('#edit_info').hide();
}


/*
	Hides edit box and shows recently used post content
*/
function ape_cancel_edit()
{
	$('#post' + ape_id).html(ape_temp_post);
	ape_id = -1;
}


/**********************************************************/


/*
	Returns obj absolute position [x,y]
*/
function findPos(obj)
{
	var curleft = curtop = 0;
	if (obj.offsetParent)
	{
		curleft = obj.offsetLeft;
		curtop = obj.offsetTop;
		while (obj = obj.offsetParent)
		{
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
		}
	}
	return [curleft,curtop];
}


/*
	This function matches text from str beetwen <substr> and </substr>
*/
function match(str, substr)
{
	// if str contains <substr>
	if (str.indexOf('<' + substr + '>') != -1)
	{
		newstr = str.substring(str.indexOf('<' + substr + '>') + substr.length+2);
		newstr = newstr.substring(0, newstr.indexOf('</' + substr + '>'));
		return newstr;
	}
	else
		return '';
}

var ape_temp_post;		// post message with html
var ape_id = -1;		// currently edited post id
var ape_menu_hovered = false;	// if menu is hovered
var ape;
var ape_url = base_url + '/include/ajax_post_edit/edit.php';
