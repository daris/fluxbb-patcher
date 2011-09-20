var server = 'chatbox.php';
var posting = false;

// Function to Disable/Enable input fields
function input_disable(type)
{
	disableThis = document.getElementsByName('chatbox_form')[0].getElementsByTagName("input");
	for (i=0; i< disableThis.length; i++) 
		disableThis[i].disabled = type;
}

function getElementsByClassName(classname, node) 
{
    if (!node)
		node = document.getElementsByTagName("body")[0];
    var a = [];
    var re = new RegExp('\\b' + classname + '\\b');
    var els = node.getElementsByTagName("*");
    for (var i=0, j=els.length; i<j; i++)
        if (re.test(els[i].className))
			a.push(els[i]);
    return a;
}

// Request function for get possible new messages
function get_messages()
{
	var args = 'ajax=1&first_msg_id='+FirstMsgId+'&last_msg_id='+LastMsgId;
	var do_ajax = new Ajax.Request(server, {method: 'get', parameters: args, onComplete: handle_response});
	Element.show('loading');
}

// Request function for get possible new messages
function delete_message(msg_id)
{
	var args = 'ajax=1&delete_message='+msg_id;
	var do_ajax = new Ajax.Request(server, {method: 'get', parameters: args, onComplete: handle_response});
	Element.show('loading');
}

// Request function for send new message
function send_message()
{
	var args = 'ajax=1&first_msg_id='+FirstMsgId+'&last_msg_id='+LastMsgId;
	
	for (var i = 0; i < document.forms['chatbox_form'].elements.length; i++)
	{
		var el = document.forms['chatbox_form'].elements[i];
		if (el.name != 'undefined' && el.value != 'undefined')
			args += '&' + el.name + '=' + encodeURIComponent(el.value);
	}
	
	// Send message
	var do_ajax = new Ajax.Request(server, {method: 'post', parameters: args, onComplete: handle_response});
	
	// Disable input fields while posting
	input_disable(true);
	// Display loading image
	Element.show('loading');
	// Let the script know that we're trying to post
	posting = true;
}

// Get the response server
function handle_response(request)
{
	// Hide loading image
	Element.hide('loading');
	
	// We're getting a valid response, first get the latest timestamp
	var response = request.responseText;
	
	var scroll_height = $('chatbox').scrollHeight;

	// There are deleted messages
	if (response.substring(0, 7) == 'deleted')
	{
		response = response.substring(8);
		var del_msg = response.substring(0, response.indexOf("\n"));
		response = response.substring(response.indexOf("\n") + 1);

		var del_msg_ids = del_msg.split(',');
		for (i = 0; i < del_msg_ids.length; i++)
			if ($('msg' + del_msg_ids[i]))
				Element.hide('msg' + del_msg_ids[i]);
	}

	if (response.indexOf("\n") != -1)
	{
		var firstLine = response.substring(0, response.indexOf("\n"));
		response = response.substring(response.indexOf("\n") + 1);
	}
	else
		var firstLine = response;

	var values = firstLine.split(';');
	
	// If error, we display error message
	if (values[0] == 'error')
		 $('chatbox').innerHTML += response.substring(values[0].length + 1) + '\n';

	// Delete message
	else if (values[0] == 'delete')
		Element.hide('msg' + values[1]);
	
	// If Response TimeStamp > Send TimeStamp we display display new message
	else if (values[1] > LastMsgId)
	{
		$('chatbox').innerHTML += response + '\n';

		FirstMsgId = values[0];
		LastMsgId = values[1];
		
		if (MaxMessages > 0)
		{
			var messages = $('chatbox').getElementsByClassName('msg');
			var msg_count = messages.length;
			if (msg_count > MaxMessages)
			{
				var scroll_top = $('chatbox').scrollTop;

				var num_msg_to_delete = msg_count - MaxMessages;
				for (i = 0; i < num_msg_to_delete; i++)
					$('chatbox').removeChild(messages[i]);

				$('chatbox').scrollTop = scroll_top;
			}
		}
	}
	
	// If we was posting !
	if (posting == true)
	{
		// Re-enable input fields after posting but we need min 500ms beetween each post for good timestamp order
		setTimeout('input_disable(false); document.chatbox_form.submit.focus();document.chatbox_form.req_message.focus();', 500);
		// If no error, we delete "req_message" value
		if (values[0] != 'error')
			$('req_message').value = '';
		// Let the script know that we're not trying to post.
		posting = false; 
	}

	// Auto Scroll chatbox (only if scrollbar is at the bottom)
	if ($('chatbox').scrollTop + $('chatbox').clientHeight + 5 + ($('chatbox').scrollHeight - scroll_height) > $('chatbox').scrollHeight)
		$('chatbox').scrollTop = $('chatbox').scrollHeight;
}
