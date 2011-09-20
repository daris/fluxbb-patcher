
var refresh_time = 10000; // 10 seconds

function ab_onload()
{
	$('#brdmenu ul li#navmessages').addClass('ajax-box ajax-pms');
//	$('#brdstats .inbox #onlinelist').addClass('ajax-box ajax-online');

	$.each($('.ajax-box'), function()
		{
			file = '';
			classes = this.className.split(' ');
			for (i = 0; i < classes.length; i++)
			{
				if (classes[i].substring(0, 5) == 'ajax-')
				{
					sec_part = classes[i].substring(5);
					if (sec_part != 'box')
						file = sec_part;
				}
			}
				
			if (file != '')
				setTimeout('ab_execute("' + file + '")', refresh_time);
		}
	);
}

function ab_execute(file)
{
	$.get('include/ajax_box/ajax/' + file + '.php', function(data)
		{
			cur_content = $('.ajax-' + file).html();
			
			if (data != '' && data != cur_content)
				$('.ajax-' + file).html(data);
			
			setTimeout('ab_execute("' + file + '")', refresh_time);
		}
	);
}