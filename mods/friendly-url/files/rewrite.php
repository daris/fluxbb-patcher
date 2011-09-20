<?php
/**
 * Rewrites SEF URLs to their actual files.
 *
 * @copyright Copyright (C) 2008 FluxBB.org, based on code copyright (C) 2002-2008 PunBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package FluxBB
 */

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// Bring in all the rewrite rules
if (file_exists(PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/rewrite_rules.php'))
	require PUN_ROOT.'include/url/'.$pun_config['o_sef'].'/rewrite_rules.php';
else
	require PUN_ROOT.'include/url/Default/rewrite_rules.php';

// If query string is not set properly, create one and set $_GET
// E.g. lighttpd's 404 handler does not pass query string
if ((!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING'])) && strpos($_SERVER['REQUEST_URI'], '?') !== false)
{
	$_SERVER['QUERY_STRING'] = parse_url('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
	$_SERVER['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']['query']) ? $_SERVER['QUERY_STRING']['query'] : '';
	parse_str($_SERVER['QUERY_STRING'], $_GET);
}

// We determine the path to the script, since we need to separate the path from the data to be rewritten
$path_to_script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if (substr($path_to_script, -1) != '/')
	$path_to_script  = $path_to_script.'/';

// We create our own request URI with the path removed and only the parts to rewrite included
$request_uri = substr(urldecode($_SERVER['REQUEST_URI']), strlen($path_to_script));
if (strpos($request_uri, '?') !== false)
	$request_uri = substr($request_uri, 0, strpos($request_uri, '?'));

$rewritten_url = '';
$url_parts = array();
// We go through every rewrite rule
foreach ($forum_rewrite_rules as $rule => $rewrite_to)
{
	// We have a match!
	if (preg_match($rule, $request_uri))
	{
		$rewritten_url = preg_replace($rule, $rewrite_to, $request_uri);
		$url_parts = explode('?', $rewritten_url);

		// If there is a query string
		if (isset($url_parts[1]))
		{
			$query_string = explode('&', $url_parts[1]);

			// Set $_GET properly for all of the variables
			// We also set $_REQUEST if it's not already set
			foreach ($query_string as $cur_param)
			{
				$param_data = explode('=', $cur_param);

				// Sometimes, parameters don't set a value (eg: script.php?foo), so we set them to null
				$param_data[1] = isset($param_data[1]) ? $param_data[1] : null;

				// We don't want to be overwriting values in $_REQUEST that were set in POST or COOKIE
				if (!isset($_POST[$param_data[0]]) && !isset($_COOKIE[$param_data[0]]))
					$_REQUEST[$param_data[0]] = urldecode($param_data[1]);

				$_GET[$param_data[0]] = urldecode($param_data[1]);
			}
		}
		break;
	}
}

// If we don't know what to rewrite to, we show a bad request messsage
if (empty($rewritten_url))
{
	define('FORUM_HTTP_RESPONSE_CODE_SET', 1);
	header('HTTP/1.1 404 Not Found');

	error('Page Not found (Error 404): The requested page <em>'.pun_htmlspecialchars($request_uri).'</em> could not be found.');
}

// We change $_SERVER['PHP_SELF'] so that it reflects the file we're actually loading
$_SERVER['PHP_SELF'] = str_replace('rewrite.php', $url_parts[0], $_SERVER['PHP_SELF']);

require PUN_ROOT.$url_parts[0];