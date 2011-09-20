<?php

function generate_rank_image($post_count)
{
	global $pun_config;

	// Options 
	$stars_count = 6; // count of stars showed under username (default: 6)
	$posts = 100; // post count for new star (default: 100)
	// End options

	$result = '';
	
	for ($i = 0; $i < $stars_count; $i++)
	{
		if ($post_count >= ($i * $posts))
			$img = 'star.gif';
		else
			$img = 'star2.gif';

		$result .= '<img src="'.$pun_config['o_base_url'].'/img/rank/'.$img.'" style="margin-right: 1px" />';
	}
	return $result;
}