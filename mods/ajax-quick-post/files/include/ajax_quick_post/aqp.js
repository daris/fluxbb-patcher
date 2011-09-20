
function aqp_post(the_form)
{
	$('#aqp-icon').show();
	$('#quickpost form input').attr('disabled', 'disabled');

	var values = {};
	for (var i = 0; i < the_form.elements.length; i++)
	{
		var el = the_form.elements[i];
		if (el.name != 'undefined' && el.value != 'undefined' && el.name != 'preview')
			values[el.name] = el.value;
	}

	$.post((typeof base_url == 'undefined' ? '' : base_url + '/') + 'post.php?tid=' + aqp_tid + '&lpid=' + aqp_last_post_id + '&pcount=' + aqp_post_count + '&ajax=1', values, function(data) 
		{
			if (data.indexOf('<div id="p') != -1)
			{
				data = data.substring(data.indexOf('<div id="p')); // trim top crumbs
				data = data.substring(0, data.indexOf('<div id="aqp">')); // posts end
			
				$('#aqp').html(data);
				the_form['req_message'].value = '';
				if (the_form['req_username']) the_form['req_username'].value = '';
				if (the_form['req_email']) the_form['req_email'].value = '';
				if (the_form['email']) the_form['email'].value = '';
			}
			else
				alert(data);

			$('#aqp-icon').hide();
			$('#quickpost form input').removeAttr('disabled');
		}
	);
	
	return false;
}

