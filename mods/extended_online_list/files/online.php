<?php

/**
 * Copyright (C) 2008-2010 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);
	
if ($pun_user['is_guest'])
	message($lang_common['No permission']);

// Load the userlist.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/userlist.php';

// Load the search.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';

if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/online.php'))
	require_once PUN_ROOT.'lang/'.$pun_user['language'].'/online.php';
else
	require_once PUN_ROOT.'lang/English/online.php';

require_once PUN_ROOT.'include/extended_online_list.php';
update_user_action();	
	
// Fetch user count
$result = $db->query('SELECT COUNT(ident) FROM '.$db->prefix.'online AS o WHERE idle=0') or error('Unable to fetch online list count', __FILE__, __LINE__, $db->error());
$num_users = $db->result($result);

// Determine the user offset (based on $_GET['p'])
$num_pages = ceil($num_users / 50);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = 50 * ($p - 1);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_online['Users online']);

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'online.php');

//$page_head[] = '<meta http-equiv="refresh" content="20;URL=online.php">';

define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'userlist');
require PUN_ROOT.'header.php';

?>

<div class="linkst crumbsplus">
	<div class="inbox">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="online.php"><?php echo $lang_online['Users online'] ?></a></li>
		</ul>
		<p class="pagelink"><?php echo $paging_links ?></p>
		<div class="clearer"></div>
	</div>
</div>

<div id="users1" class="blocktable">
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Username'] ?></th>
					<th class="tc3" scope="col" style="width: 60px"></th>
					<th class="tc2" scope="col" style="width: 45%"><?php echo $lang_online['Action'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_online['Last updated'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

// User agent integration
if (file_exists(PUN_ROOT.'include/user_agent.php'))
	require PUN_ROOT.'include/user_agent.php';
$user_agent_cache = array();

// Grab the users
$result = $db->query('SELECT o.user_id, o.ident, o.logged, o.action, o.url, o.user_agent FROM '.$db->prefix.'online AS o WHERE idle=0 ORDER BY o.logged DESC LIMIT '.$start_from.', 50') or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result))
{
	while ($user_data = $db->fetch_assoc($result))
	{
		$user_link = pun_htmlspecialchars($user_data['ident']);
		
		// Show IP only for admin/mod
		if ($user_data['user_id'] == 1 && !$pun_user['is_admmod'])
			$user_link = $lang_common['Guest'];
		
		if ($user_data['user_id'] > 1)
			$user_link = '<a href="profile.php?id='.$user_data['user_id'].'">'.$user_link.'</a>';
		
		if (function_exists('get_useragent_icons'))
		{
			if (isset($user_agent_cache[$user_data['user_agent']]))
				$user_agent = $user_agent_cache[$user_data['user_agent']];
			else
			{
				$user_agent = get_useragent_icons($user_data['user_agent']);
				$user_agent_cache[$user_data['user_agent']] = $user_agent;
			}
		}
		else
			$user_agent = '<span title="'.pun_htmlspecialchars($user_data['user_agent']).'">'.$lang_online['User agent'].'</span>';

?>
				<tr>
					<td class="tcl"><?php echo $user_link ?></td>
					<td class="tc3"><?php echo $user_agent ?></td>
					<td class="tc2"><?php echo '<a href="'.$user_data['url'].'">'.$user_data['action'].'</a>' ?></td>
					<td class="tcr"><?php echo format_time($user_data['logged']) ?></td>
				</tr>
<?php

	}
}
else
	echo "\t\t\t".'<tr>'."\n\t\t\t\t\t".'<td class="tcl" colspan="'.(($show_post_count) ? 4 : 3).'">'.$lang_search['No hits'].'</td></tr>'."\n";

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb crumbsplus">
	<div class="inbox">
		<p class="pagelink"><?php echo $paging_links ?></p>
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="online.php"><?php echo $lang_online['Users online'] ?></a></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>
<?php

require PUN_ROOT.'footer.php';
