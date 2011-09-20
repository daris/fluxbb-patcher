<?php

// Get topic/forum tracking data
if (!$pun_user['is_guest'])
	$tracked_topics = get_tracked_topics();

// Fetch list of topics to display on this page
if ($pun_user['is_guest'] || $pun_config['o_show_dot'] == '0')
{
	// Without "the dot"
	$sql = 'SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM '.$db->prefix.'topics WHERE id = '.$topic_id.' LIMIT 1';
}
else
{
	// With "the dot"
	$sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$pun_user['id'].' WHERE t.id = '.$topic_id.' LIMIT 1';
}

$result = $db->query($sql) or error('Unable to fetch topic', __FILE__, __LINE__, $db->error());

$topic_count = 0;
while ($cur_topic = $db->fetch_assoc($result))
{
	++$topic_count;
	$status_text = array();
	$item_status = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';
	$icon_type = 'icon';

	if ($cur_topic['moved_to'] == null)
		$last_post = '<a href="viewtopic.php?pid='.$cur_topic['last_post_id'].'#p'.$cur_topic['last_post_id'].'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>';
	else
		$last_post = '- - -';

	if ($pun_config['o_censoring'] == '1')
		$cur_topic['subject'] = censor_words($cur_topic['subject']);

	if ($cur_topic['sticky'] == '1')
	{
		$item_status .= ' isticky';
		$status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>';
	}

	if ($cur_topic['moved_to'] != 0)
	{
		$subject = '<a href="viewtopic.php?id='.$cur_topic['moved_to'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
		$status_text[] = '<span class="movedtext">'.$lang_forum['Moved'].'</span>';
		$item_status .= ' imoved';
	}
	else if ($cur_topic['closed'] == '0')
		$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
	else
	{
		$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
		$status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>';
		$item_status .= ' iclosed';
	}

	if (!$pun_user['is_guest'] && $cur_topic['last_post'] > $pun_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$id]) || $tracked_topics['forums'][$id] < $cur_topic['last_post']) && $cur_topic['moved_to'] == null)
	{
		$item_status .= ' inew';
		$icon_type = 'icon icon-new';
		$subject = '<strong>'.$subject.'</strong>';
		$subject_new_posts = '<span class="newtext">[ <a href="viewtopic.php?id='.$cur_topic['id'].'&amp;action=new" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';
	}
	else
		$subject_new_posts = null;

	// Insert the status text before the subject
	$subject = implode(' ', $status_text).' '.$subject;

	// Should we display the dot or not? :)
	if (!$pun_user['is_guest'] && $pun_config['o_show_dot'] == '1')
	{
		if ($cur_topic['has_posted'] == $pun_user['id'])
		{
			$subject = '<strong class="ipost">·&#160;</strong>'.$subject;
			$item_status .= ' iposted';
		}
	}

	$num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

	if ($num_pages_topic > 1)
		$subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, 'viewtopic.php?id='.$cur_topic['id']).' ]</span>';
	else
		$subject_multipage = null;

	// Should we show the "New posts" and/or the multipage links?
	if (!empty($subject_new_posts) || !empty($subject_multipage))
	{
		$subject .= !empty($subject_new_posts) ? ' '.$subject_new_posts : '';
		$subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
	}

?>
				<tr class="<?php echo $item_status ?>" id="t<?php echo $cur_topic['id'] ?>">
					<td class="tcl">
<?php $mod_tools = quick_mod_tools(); if (!empty($mod_tools)) : ?>						<div class="mod-tools"><?php echo implode("\n", $mod_tools) ?></div><?php endif; ?>
						<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo forum_number_format($topic_count + $start_from) ?></div></div>

						<div class="tclcon">
							<div>
								<?php echo $subject."\n" ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo ($cur_topic['moved_to'] == null) ? forum_number_format($cur_topic['num_replies']) : '-' ?></td>
<?php if ($pun_config['o_topic_views'] == '1'): ?>					<td class="tc3"><?php echo ($cur_topic['moved_to'] == null) ? forum_number_format($cur_topic['num_views']) : '-' ?></td>
<?php endif; ?>					<td class="tcr"><?php echo $last_post ?></td>
				</tr>
<?php

}