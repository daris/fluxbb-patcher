
function aqp_post(the_form)
{
	$('#aqp-icon').show();
	$('#quickpost form input').attr('disabled', 'disabled');

	var values = {
		form_sent: 1,
		form_user: the_form.form_user.value,
		req_message: the_form.req_message.value
	};

	$.post('post.php?tid=' + aqp_tid + '&lpid=' + aqp_last_post_id + '&pcount=' + aqp_post_count + '&ajax=1', values, function(data) 
		{
			if (data.indexOf('<div id="p') != -1)
			{
				data = data.substring(data.indexOf('<div id="p')); // trim top crumbs
				data = data.substring(0, data.indexOf('<div id="aqp">')); // posts end
			
				$('#aqp').html(data);
				document.getElementsByName('req_message')[0].value = '';
			}
			else
				alert(data);

			
			$('#aqp-icon').hide();
			$('#quickpost form input').removeAttr('disabled');
		}
	);
	
	return false;
}

