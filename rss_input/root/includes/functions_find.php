<?php
/**
*
* @author PoPoutdoor
*
* @package RSS_input
* @version $Id:$
* @copyright (c) 2008-2011 PoPoutdoor
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}


/**
* Convert CR to LF, strip extra LFs and spaces, multiple newlines to 2
* Filter unsupported html tags
*
* @param $text
* @param $is_html (booleans), option to retains supported html tags
* @return string
*/
function _filter($text, $is_html = false)
{
	$text = str_replace(array("\r", '&nbsp;', '&#32;'), array("\n", ' ', ' '), $text);
	$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");

	if (!$is_html)
	{
		$text = strip_tags($text);
	}

	$text = preg_replace("# +?\n +?#u", "\n", $text);
	$text = preg_replace("#\n{3,}#u", "\n\n", $text);
	$text = preg_replace('# +#u', ' ', $text);
//	$text = preg_replace('#\n #u', "\n", $text);

	return trim($text);
}

/**
*	Main function
*/
function get_rss_content($sql_ids = '')
{
	global $phpbb_root_path, $phpEx, $db, $user;
	global $is_cjk;	// This pass local vars to called functions

	$user->add_lang('find');

	if (empty($sql_ids))
	{
		trigger_error('NO_IDS');
	}

//	include($phpbb_root_path . 'includes/rss_parser.'.$phpEx);

	// init return messages
	$msg = array('ok' => '', 'skip' => '', 'err' => '');

	$sql = 'SELECT f.*, u.user_colour, u.user_lang, b.bot_active, b.bot_name, b.bot_ip, n.forum_name
		FROM ' . FIND_TABLE . ' f, '  . USERS_TABLE . ' u, ' . BOTS_TABLE . ' b,' . FORUMS_TABLE . " n 
		WHERE f.feed_id $sql_ids
			AND u.user_id = b.user_id
			AND f.bot_id = b.user_id
			AND n.forum_id = f.post_forum 
		ORDER BY f.post_forum ASC";
	$result = $db->sql_query($sql);

//var_dump(date_default_timezone_get());
//return false;
	// fetch news from selected feeds
	while ($row = $db->sql_fetchrow($result))
	{
		$feedname = $row['feedname'];

		// feed not active, skipped to next
		if (!$row['status'])
		{
			$msg['skip'][] = sprintf($user->lang['NOT_ACTIVE'], $feedname);
			continue;
		}

		// strip off bot name identification string
		$bot_name = trim(str_replace(FIND_BOT_ID, '', $row['bot_name']));

		// Bot not active, skipped to next feed
		if (!$row['bot_active'])
		{
			$msg['skip'][] = sprintf($user->lang['BOT_NOT_ACTIVE'], $bot_name);
			continue;
		}

		// Set CJK flag by bot language
		$is_cjk = false;
		if (defined('FIND_CJK') && function_exists('cjk_tidy'))
		{
			$ary = explode(',', FIND_CJK);
			$is_cjk = (in_array($row['user_lang'] , $ary)) ? true : false;
		}

		// prepare some vars
		$feed_id				= $row['feed_id'];
		$feedname_topic	= $row['feedname_topic'];
		$last_update		= $row['last_import'];
		$item_limit			= (int) $row['post_items'];
		$post_limit			= $row['post_contents'];
		$inc_channel		= $row['inc_channel'];
		$inc_cat				= $row['inc_cat'];
		$inc_html			= $row['feed_html'];
		$topic_ttl			= $row['topic_ttl'];
		$forum_id			= $row['post_forum'];
		$forum_name			= $row['forum_name'];

		$user->ip							= $row['bot_ip'];
		$user->data['user_id']			= $row['bot_id'];	// also used for update forum tracking
		$user->data['username']			= $bot_name;
		$user->data['user_colour']		= $row['user_colour'];
		$user->data['is_registered']	= 1;	// also used for update forum tracking

/*
	Try use simplexml
		prepare_xml() - not used
//-----------------------	
		$xml = prepare_xml($row['url'], $row['encodings'], $feedname, $msg);
		if ($xml === false)
		{
			continue;
		}
*/
		if (function_exists('simplexml_load_file') && ini_get('allow_url_fopen'))
		{
			// suppress error
			//libxml_use_internal_errors(true);
			$xml = simplexml_load_file($row['url'], 'SimpleXMLElement', LIBXML_NOCDATA);
		}
		// no supporting methods, issue error message
		else
		{
			$msg['err'][] = $user->lang['NO_PHP_SUPPORT'];
			continue;
		}

		// null page? file not loaded, source issue
		if ($xml === false)
		{
			// TODO: Fix message, key=>val
			$msg['err'][] = sprintf($user->lang['FILE_NULL'], $feedname, $url);
			//$msg['err'][] = libxml_get_errors();
			//libxml_clear_errors();
			continue;
		}

		// check compliance
		$is_rss = ( isset($xml->channel) ) ? TRUE : FALSE;

		$validate = ($is_rss) ? ( !is_null($xml->channel->title) && !is_null($xml->channel->description) && !is_null($xml->channel->link) ) : ( !is_null($xml->id) && !is_null($xml->title) && !is_null($xml->updated) );

		if (!$validate)
		{
			// Not validate, issue error
			$msg['err'][] = sprintf($user->lang['NO_CHANNEL'], $feedname);
			continue;
		}

		// check source timestamp
		$now = time();

		if ($is_rss)
		{
			$ts = ( isset($xml->channel->lastBuildDate) ) ? strtotime($xml->channel->lastBuildDate) : strtotime($xml->channel->pubDate);
		}
		else
		{
			$ts = strtotime($xml->updated);
		}

		if ($ts === FALSE)
		{
			// timestamp error, should issue error.
			$ts = $now;
		}

		// RSS ttl support
		$ttl = ( isset($xml->channel->ttl) ) ? $ts + $xml->channel->ttl * 60 : 0;

		if ( $ts <= $last_update || $ttl >= $now)
		{
			// source not updated, skip to next
			$msg['skip'][] = sprintf($user->lang['FEED_NONE'], $feedname);
			continue;
		}

		/* We use Bot user language from here */
		$user->lang_name = $row['user_lang'];
		$user->add_lang('find_posting');

		// message body vars
		$processed = $skipped = $latest_ts = 0;
		if (empty($topic_ttl))
		{
			$post_ary = array();
		}
		else
		{
			$contents = '';
		}

		// handle item/entry
		$feed = ($is_rss) ? $xml->xpath('//item') : $xml->entry;

		$i = 0;
//		$items = array();

		foreach ($feed as $item)
		{
			// Respect item limit setting
			if ($item_limit && $i++ === $item_limit)
			{
				break;
			}

			// check item timestamp
			$item_ts = ($is_rss) ? strtotime($item->pubDate) : strtotime($item->updated);

			if ($item_ts === FALSE)
			{
				// timestamp error, should issue error.
				$item_ts = $ts;
			}

			if ($item_ts <= $last_update)
			{
				// item outdated, skipped
				continue;
			}

			if ($latest_ts < $item_ts)
			{
				$latest_ts =  $item_ts;
			}

			// skip to next item if outdated
			if ($last_update >= $item_ts)
			{
				$skipped++;
				continue;
			}

			// preprocess item values
			$title = _filter($item->title);
			$desc = ($is_rss) ? _filter($item->description, TRUE) : ( (isset($item->content)) ? _filter($item->content, TRUE) : _filter($item->summary, TRUE) );

			if (empty($title) && empty($desc))
			{
				// Not validate, issue error
				$msg['err'][] = sprintf($user->lang['NO_ITEM_INFO'], $feedname);
				continue;
			}
/* Do we need this?			
			// Check to see if we have a subject - some atom feeds like blogspot provide an empty title.
			$item_title = (!empty($title)) ? utf8_tidy($title) : utf8_tidy($desc);
*/
			$item_title = truncate_string(utf8_tidy($title), 60, 255, false, $user->lang['TRUNCATE']);
			
			// prepare the message text
		/* bb_message() */
			$message = '';

			// no timestamp (use channel timestamp)
			if ($item_ts != $ts)
			{
				$message .= sprintf($user->lang['BB_POST_AT'], $user->format_date($item_ts)) . "\n";
			}

			if ($inc_cat && isset($item->category))
			{
				if (!is_object($item->category))
				{
					$message .= sprintf($user->lang['BB_CAT'], utf8_tidy(_filter($item->category)));
				}
			}

			$author	= utf8_tidy(_filter($item->author));
			if (isset($item->author->name))
			{
				$author	= utf8_tidy(_filter($item->author->name));
				$author	.= (isset($item->author->email)) ? "\t" . _filter($item->author->email) : '';
			}
			$message .= (!empty($author)) ? sprintf($user->lang['BB_AUTHOR'], $author): '';

			// Now we add the content
			if ($inc_html)
			{
				html2bb($desc);
			}

			$desc = strip_tags($desc);

			$desc = utf8_tidy($desc, true);
			// Contents filter
			if (defined('FIND_STRIP'))
			{
				$desc = str_replace(explode(',', FIND_STRIP), '', $desc);	// Strip defined patterns
			}

			if ($post_limit)
			{
				$desc = truncate_string($desc, $limits, 255, false, $user->lang['TRUNCATE']);
			}

			$message .= "\n" . $desc . "\n";

			if (isset($item->link))
			{
				$link	= isset($item->link['href']) ? fix_url($item->link['href']) : fix_url($item->link);
			}

			if (isset($item->comments))
			{
				$comments = fix_url($item->comments);
			}

			if (!empty($link) && !empty($comments))
			{
				$message .= "\n" . sprintf($user->lang['BB_URL'], $link, $user->lang['READ_MORE']) . $user->lang['TAB'] . sprintf($user->lang['BB_URL'], $comments, $user->lang['COMMENTS']) . "\n";
			}
			else
			{
				$message .= (!empty($link)) ? "\n" . sprintf($user->lang['BB_URL'], $link, $user->lang['READ_MORE']) . "\n" : ((!empty($comments)) ? "\n" . sprintf($user->lang['BB_URL'], $comments, $user->lang['COMMENTS']) . "\n" : '');
			}

		/* end bb_message() */
			
			if (empty($topic_ttl))
			{
				$post_ary[] = array($item_title, $message);
			}
			else
			{
				$contents = sprintf($user->lang['BB_TITLE'], $item_title) . $message . "\n\n" . $contents;
			}

			$processed++;
		} // end process items
			
		if ($processed)
		{
			$heading = $feed_info = '';

			// should we include the channel info
			if (!empty($inc_channel))
			{
				$channel			= ($is_rss) ? $xml->channel->title : $xml->title;
				$channel			= utf8_tidy(_filter($channel));
				$channel_desc	= ($is_rss) ? $xml->channel->description : $xml->subtitle;
				$channel_desc	= utf8_tidy(_filter($channel_desc));
				$channel_link	= ($is_rss) ? fix_url($xml->channel->link) : fix_url($xml->link['href']);
				$image_url		= ($is_rss) ? fix_url($xml->channel->image->url) : fix_url($xml->logo);

				$heading .= (!empty($image_url)) ? sprintf($user->lang['BB_URL'], $channel_link, "[img]${image_url}[/img]") : (!empty($channel_link) ? sprintf($user->lang['BB_URL'], $channel_link, sprintf($user->lang['BB_CHANNEL'], $channel)) : sprintf($user->lang['BB_CHANNEL'], $channel));
				$heading .= "\n";

				if (!empty($channel_desc) && $channel_desc != $channel)
				{
					$heading .= sprintf($user->lang['BB_CHANNEL_DESC'], $channel_desc);
				}
			}

			// Always show the copyright notice if provided
			$channel_rights	= ($is_rss) ? $xml->channel->copyright : $xml->rights;
			$channel_rights	= utf8_tidy(_filter(str_replace("©", "&#169;", $channel_rights)));

			$feed_info .= (!empty($channel_rights)) ? sprintf($user->lang['BB_COPYRIGHT'], $channel_rights) : '';

			if ($ts != $now)
			{
				$feed_info .= sprintf($user->lang['BB_CHANNEL_DATE'], $user->format_date($ts)) . $user->lang['HR'];
			}

			if (!$latest_ts)
			{
				$latest_ts = $now; 
			}
			
			// submit each item as new post or reply
			if (empty($topic_ttl))
			{
				foreach ($post_ary as $not_used => $data)
				{
					list($subject, $post_contents) = $data;
					// If subject already post, make this as reply
					$sql = 'SELECT topic_id 
						FROM ' . TOPICS_TABLE . "
						WHERE topic_title = '" . $db->sql_escape($subject) . "'
							AND forum_id = " . (int) $forum_id . "
						ORDER BY topic_id DESC LIMIT 1";
					$query = $db->sql_query($sql);
					$topic_id = $db->sql_fetchfield('topic_id');
					$db->sql_freeresult($query);

					$mode = (empty($topic_id))? 'post' : 'reply';
					rss_autopost($forum_id, $forum_name, $mode, $subject, $heading . $feed_info . $post_contents);
				}
			}
			// pack all items and additional info in one post
			else
			{
				// should we use the acp feedname as subject?
				$subject = $feedname;
				if (empty($feedname_topic))
				{
					$subject = (!empty($channel)) ? $channel : ((!empty($channel_desc)) ? $channel_desc : $feedname);
				}
				$subject = truncate_string($subject, 60, 255, false, $user->lang['TRUNCATE']);

				// Get topic id and topic time
				$sql = 'SELECT topic_id, topic_time
					FROM ' . TOPICS_TABLE . "
					WHERE topic_title = '" . $db->sql_escape($subject) . "'
						AND forum_id = " . (int) $forum_id . "
					ORDER BY topic_id DESC LIMIT 1";
				$query		= $db->sql_query($sql);
				$topic_row	= $db->sql_fetchrow($query);

				$topic_id	= $topic_row['topic_id'];
				$topic_time	= $topic_row['topic_time'];

				$db->sql_freeresult($query);

				// New topic if first import
				if (empty($topic_id))
				{
					$mode = 'post';
				}
				else
				{
					if ($topic_ttl == 1)	// new topic on each day
					{
						$mode = ($user->format_date($latest_ts, 'd', true) == $user->format_date($topic_time, 'd', true)) ? 'reply' : 'post';
					}
					else	// new topic on each week/month
					{
						$format = ($topic_ttl == 30) ? 'm' : 'W';
						$mode = ($user->format_date($latest_ts, $format, true) <= $user->format_date($topic_time, $format, true)) ? 'reply' : 'post';
					}
				}

				$contents = $feed_info . $contents;

				// include headings for new post
				if ($mode == 'post')
				{
					$contents = $heading . $contents;
				}

				rss_autopost($forum_id, $forum_name, $mode, $subject, $contents, $topic_id);
			}

			// update feed last visit time
			$sql = 'UPDATE ' . FIND_TABLE . '
				SET last_import = ' . $latest_ts . "
				WHERE feed_id = $feed_id";
			$update_result = $db->sql_query($sql);
			$db->sql_freeresult($update_result);

			$msg['ok'][] = sprintf($user->lang['FEED_OK'], $feedname, $processed);
		}

		// message for skipped items
		if ($skipped)
		{
			$msg['skip'][] = sprintf($user->lang['FEED_SKIP'], $feedname, $skipped);
		}
	}
/*
	else
	{
		$error = $parser->parser_error;
		if (!empty($error))
		{
			$msg['err'][] = sprintf($user->lang['FEED_ERR'], $feedname, $error[0], $error[1], $error[2], $error[3], $error[4]);
		}
	}
*/

/*
	Try use simplexml
		rss_parser() - not used
//-----------------------
		$parser->destroy();
		//unset($parser);
	}
*/
	$db->sql_freeresult($result);

	return $msg;
}



/**
*	Convert html tags to BBCode, supported tags are: strong,b,u,em,i,ul,ol,li,img,a,p (11)
*/
function html2bb(&$html)
{
	// <strong>...</strong>, <b>...</b>, <u>...</u>, <em>...</em>, <i>...</i>, <p>...</p>, <li>...</li>, <ul>...</ul>, </ol>
	//	to [b]...[/b], [b]...[/b], [u]...[/u], [i]...[/i], [i]...[/i], \n\n...\n\n, [*]...\n, [list]...[/list], [/list]
	$search = array('<strong>', '</strong>', '<b>', '</b>', '<u>', '</u>', '<em>', '</em>', '<i>', '</i>',
		'<p>', '</p>', '<li>', '</li>', '<ul>', '</ul>', '</ol>',
	);
	$replace = array('[b]', '[/b]', '[b]', '[/b]', '[u]', '[/u]', '[i]', '[/i]', '[i]', '[/i]',
		"\n\n", "\n\n", '[*]', "\n", '[list]', '[/list]', '[/list]',
	);
	$html = str_replace($search, $replace, $html);
	$html = preg_replace('#<ul\s.*?>#is', '[list]', $html);	// <ul ...>
	$html = preg_replace('#<ol\s+type="(\w+)">#is', '[list=\\1]', $html);	// <ol ...> to [list=...]
	$html = preg_replace('#<ol\s+style=".*?decimal">#is', '[list=1]', $html);	//detect <ol style="list-style-type: decimal">
	$html = preg_replace('#<p\s.*?>#is', "\n\n", $html);	// <p ...>
	$html = preg_replace('#<li\s.*?>#is', '[*]', $html);	// <li ...>

	// br2nl
	$html = preg_replace('#<br ?/?>#is', "\n", $html);	

/*	Not working, need to preview first then the quote's OK.
// TODO: need to hack preview code
	// process phpbb.com <blockquote>...<cite>@somebody wrote:</cite>...</blockquote>
	if (preg_match_all('#<blockquote.+?>(?:<cite>(.*?)</cite>)?(.*?)</blockquote>#is', $html, $tag, PREG_PATTERN_ORDER))
	{
		$i = 0;
		foreach ($tag[0] as $not_used => $q_tag)
		{
			$wrote = str_replace(' wrote:', '', $tag[1][$i]);

			if (empty($wrote))	// [quote]text[/quote]
			{
				$bbcode = '[quote]' . $tag[2][$i] . '[/quote]';
			}
			else
			{
				$bbcode = '[quote="' . $wrote . '"]' . $tag[2][$i] . '[/quote]';
			}

			$html = str_replace($q_tag, $bbcode, $html);
			$i++;
		}
	}
*/
	// filter inline img tag from doyouhike.net
	$html = preg_replace('#<img class="attach"[^>].+?/>#is', "\n[image]\n", $html);
	// process <img> tags
	if (preg_match_all('#<img[^>]*(?:src="(http[^"]+\.(?:gif|jp[2g]|png|xbm))).+?>#is', $html, $tag, PREG_PATTERN_ORDER))
	{
		global $config;

		$i = 0;
		foreach ($tag[0] as $not_used => $img_tag)
		{
			$url = fix_url($tag[1][$i]);
			$bbcode = '[img]' . $url . '[/img]';
			// Note: This is from function bbcode_img()
			// if the embeded image exceeds limits, return $url
			if (($config['max_post_img_height'] || $config['max_post_img_width']) && ini_get('allow_url_fopen'))
			{
				$stats = @getimagesize($url);

				if ($stats !== false)
				{
					if ($config['max_post_img_height'] && $config['max_post_img_height'] < $stats[1]
						 || $config['max_post_img_width'] && $config['max_post_img_width'] < $stats[0])
					{
						$bbcode = $url;
					}
				}
			}

			$html = str_replace($img_tag, $bbcode, $html);
			$i++;
		}
	}

	// process <a> tags
	if (preg_match_all('#<a[^>]*(?:href="(http[^"]+)).+? >(.+?)</a>#is', $html, $tag, PREG_PATTERN_ORDER))
	{
		$i = 0;
		foreach ($tag[0] as $not_used => $a_tag)
		{
			$url = fix_url($tag[1][$i]);
			$txt = str_replace(array(' ', "\n"), '', $tag[2][$i]);

			if (empty($txt))	// [url]link[/url]
			{
				$bbcode = "[url]${url}[/url]";
			}
			else	// [url=link]text[/url]
			{
				$bbcode = "[url=${url}]${txt}[/url]";
			}

			$html = str_replace($a_tag, $bbcode, $html);
			$i++;
		}
	}

	
	$html = _filter($html);

	return;
}



/**
*	URL filter
*/
function fix_url($url)
{
	$url = trim($url);

	// Strip yahoo redirect links
	$pos = strpos($url, '*http://');
	if ($pos !== false)
	{
		$url = substr($url, $pos+1);
	}

	// Additional filters below
	$url = str_replace(' ', '%20', $url);

	return $url;
}


/**
*	Cleanup spacing and newlines, with aditional spacing fix-ups for CJK text
*/
function utf8_tidy($text, $newline = false)
{
	global $is_cjk;

	if (function_exists('cjk_tidy') && $is_cjk)
	{
		$text = cjk_tidy($text);
	}

	$text = ($newline) ? preg_replace("#\n{3,}#", "\n\n", $text) : str_replace("\n", '', $text);

	return trim($text);
}



/**
* Bot Submit Post
*
* $mode: post/reply, 'approve' always "true'.
* No edit, no poll, no attachment, no quote, no globalising, no indexing
*	no notifications. tracking/markread for poster.
*/
function rss_autopost($forum_id, $forum_name, $mode, $subject, $message, $topic_id = 0)
{
	global $user, $phpEx, $phpbb_root_path;

	if (!function_exists('submit_post'))
	{
		include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
	}

	// censor_text()
	$subject = censor_text($subject);
	$message = censor_text($message);

	// variables to hold the parameters
	$uid = $bitfield = $options = '';
	generate_text_for_storage($subject, $uid, $bitfield, $options, false, false, false);
	generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

	$icon_id = (defined('FIND_ICON')) ? FIND_ICON : false;

	// prepare $data array
	$data = array(
		'forum_id'			=> $forum_id,
		'icon_id'			=> $icon_id,
		'enable_bbcode'	=> 1,
		'enable_smilies'	=> 0,
		'enable_urls'		=> 1,
		'enable_sig'		=> 0,
		'enable_indexing'	=> 0,	// do not index
		'notify_set'		=> 0, // do not notify user
		'notify'				=> 0, // do not notify user
		'bbcode_bitfield'	=> $bitfield,
		'bbcode_uid'		=> $uid,
		'poster_ip'			=> $user->ip,	// set to post bot ip
		'message'			=> $message,
		'message_md5'		=> md5($message),
		'topic_id'			=> $topic_id,	// used only for 'reply', auto set when 'post'
		'topic_title'		=> $subject,	// not used for post/reply, only for user_notification
		'forum_name'		=> $forum_name,	// not used for post/reply, only for user_notification

		'force_approved_state'	=> 1,	// set TRUE to force set approval
		'post_edit_locked'		=> 1
	);

	$not_used = submit_post($mode, $subject, '', POST_NORMAL, $poll, $data);

	return;
}


/**
* Fix CJK full-width punct-alpnum spacing
*
* utf8 ncr values for CJK full-width symbols:
*	12288 - 12290, 12298 - 12318
*	65281 - 65312
*	65313 - 65338		excludes english capital letters
*	65339 - 65344
*	65345 - 65370		excludes english letters
*	65371 - 65377
*	65504 - 65510
*
*	HK font				37032, 24419, 22487
*/
function cjk_tidy($text)
{
	// decode first!
	$text = utf8_decode_ncr($text);

	// Preserve space around [] , for posting with bbcode tags
	$text = preg_replace('#\] +([[:punct:]])#', ']&#32;\\1', $text);
	$text = preg_replace('#([[:punct:]]) +\[#', '\\1&#32;[', $text);
	// encolsed words with spaces
	$text = preg_replace('#([[:alnum:][:punct:]\-\+]+)#', '&#32;\\1&#32;', $text);

	$text = utf8_encode_ncr($text);

	$text = preg_replace('/(?:(&#[0-9]{5};)(\w+)|(\w+)(&#[0-9]{5};))/', '\\1 \\2', $text);
	$text = preg_replace('/\]&#32;(&#[0-9]{5};)/', ']\\1', $text);
	$text = preg_replace('/(&#[0-9]{5};)&#32;\[/', '\\1[', $text);
	// trim full-width spaces
	$text = preg_replace('/^\p{Zs}+/u', '', $text);
	$text = preg_replace('/\p{Zs}+$/u', '', $text);

	// restore space
	$text = str_replace('&#32;', ' ', $text);

	// process spacings
	$val = 12287;
	while ($val <= 12318)
	{
		$val++;
		if ($val > 12290 && $val < 12298)
		{
			continue;
		}

		$text = preg_replace(array("/(&#$val;) +/", "/ +(&#$val;)/"), '\\1', $text);
	}

	$val = 65280;
	while ($val <= 65378)
	{
		$val++;
		if ( ($val > 65312 && $val < 65339) || ($val > 65344 && $val < 65371) )
		{
			// skip Full-width letters and part not in range
			continue;
		}

		$text = preg_replace(array("/(&#$val;) +/", "/ +(&#$val;)/"), '\\1', $text);
	}

	$val = 65503;
	while ($val <= 65510)
	{
		$val++;

		$text = preg_replace(array("/(&#$val;) +/", "/ +(&#$val;)/"), '\\1', $text);
	}

	$text = utf8_decode_ncr($text);

	return $text;
}


?>
