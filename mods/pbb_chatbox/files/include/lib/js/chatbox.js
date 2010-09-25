var server = 'chatbox.php';
var posting = false;

// Function to Disable/Enable input fields
function input_disable(type) 
{
	disableThis = document.getElementsByTagName("input");
	for (i=0; i< disableThis.length; i++) 
	{
		disableThis[i].disabled = type;
	}
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
	var req_message = $F('req_message');
	var form_user = $F('form_user');
	var req_username = $F('req_username');
	var req_email = $F('req_email');
	var email = $F('email');

	// Send message
	var args = 'ajax=1&form_sent=1&first_msg_id='+FirstMsgId+'&last_msg_id='+LastMsgId+ '&form_user=' + encodeURIComponent(form_user) + '&req_username=' + encodeURIComponent(req_username) + '&req_email=' + encodeURIComponent(req_email) + '&email=' + encodeURIComponent(email) + '&req_message=' + encodeURIComponent(req_message);
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
	}
	
	// If we was posting !
	if (posting == true) 
	{
		// Re-enable input fields after posting but we need min 500ms beetween each post for good timestamp order
		setTimeout('input_disable(false); document.formulaire.submit.focus();document.formulaire.req_message.focus();', 500);
		// If no error, we delete "req_message" value
		if (values[0] != 'error')
			$('req_message').value = '';
		// Let the script know that we're not trying to post.
		posting = false; 
	}

	// Auto Scroll chatbox if is checked
	if ($('autoscroll').checked == true)
		$('chatbox').scrollTop = $('chatbox').scrollHeight;
}
