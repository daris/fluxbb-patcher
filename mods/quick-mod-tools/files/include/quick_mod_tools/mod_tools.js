
var mod_tools_url = 'include/quick_mod_tools/moderate.php';

function edit_subject(fid, id, submit_name)
{
	topic_id = id;
	tmp_topic_html = $('#t' + topic_id).html();
	subject = $('#t' + id + ' .tclcon div a').html();
	subject = subject.replace(/"/g, '&quot;');
	$('#t' + id + ' .mod-tools').html('');
	$('#t' + id + ' .tclcon div').html('<form onsubmit="update_subject(' + fid + ', ' + id + '); return false;"><input type="text" id="subject" name="subject" size="35" value="' + subject + '" /> <input type="submit" value="' + submit_name + '" /> <a href="javascript:void(null)" onclick="cancel_edit()">Cancel</a></form>');
	$('#subject').focus();
}

function cancel_edit()
{
	$('#t' + topic_id).html(tmp_topic_html);
}

function update_subject(fid, id)
{
	topic_id = id;
	
	subject = $('#subject').val();
	
	$.post(mod_tools_url + '?fid=' + fid + '&tid=' + id, {subject: subject}, function(data) {moderate_topic_onready(data)});
	$('#t' + topic_id + ' .mod-tools').html('<img style="float: right" src="img/quick_mod_tools/loading.gif" />');
}

function moderate_topic(fid, id, action)
{
	topic_id = id;
	
	if (action == 'delete' && !confirm('Are you sure, you want to delete this topic?'))
		return;
	
	$.get(mod_tools_url + '?fid=' + fid + '&tid=' + id + '&action=' + action, function(data) {moderate_topic_onready(data)});
	$('#t' + topic_id + ' .mod-tools').html('<img style="float: right" src="img/quick_mod_tools/loading.gif" />');
}

function moderate_topic_onready(data)
{
	if (data == 'reload')
	{
		window.location.reload();
		return;
	}
	else if (data.indexOf('<tr class="') == -1)
	{
		alert(data);
		$('#t' + topic_id + ' .mod-tools').html('');
		return;
	}
	
	data = data.substring(data.indexOf('class="') + 7);
	cl = data.substring(0, data.indexOf('"'));
	data = data.substring(data.indexOf('>') + 1);
	data = data.substring(0, data.indexOf('</tr>'));

	$('#t' + topic_id).attr('class', cl);
	$('#t' + topic_id).html(data);
}