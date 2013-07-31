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
*	Main function
*/
/*

FIXME: 

- bbc chinese, multiple feed category/feed entry categlory

TODO: 

- yahoo hk, item extra tags

	channel->source
 
- bbc uk, item extra tag not parsed

   <media:thumbnail width="66" height="49" url="http://news.bbcimg.co.uk/media/images/69031000/jpg/_69031488_69030640.jpg"/>  
      <media:thumbnail width="144" height="81" url="http://news.bbcimg.co.uk/media/images/69031000/jpg/_69031489_69030640.jpg"/> 
      
      
- bbc chinese, multiple entry link with 

object(SimpleXMLElement)#16 (8) {
	["category"]=> array(2) {
		[0]=> object(SimpleXMLElement)#109 (1) {
			["@attributes"]=> array(2) {
				["term"]=> string(19) "chinese_traditional" 
				["label"]=> string(19) "chinese_traditional" } } 
		[1]=> object(SimpleXMLElement)#110 (1) {
			["@attributes"]=> array(2) {
				["term"]=> string(8) "business" 
				["label"]=> string(6) "財經" } } } 
	["link"]=> array(4) {
		[0]=> object(SimpleXMLElement)#111 (1) {
			["@attributes"]=> array(4) {
				["rel"]=> string(9) "alternate" 
				["type"]=> string(9) "text/html" 
				["title"]=> string(5) "story" 
				["href"]=> string(92) "http://www.bbc.co.uk/zhongwen/trad/business/2013/07/130730_press_china_nicaragua_canal.shtml" } } 
		[1]=> object(SimpleXMLElement)#112 (1) {
			["@attributes"]=> array(4) {
				["rel"]=> string(7) "related" 
				["type"]=> string(9) "text/html" 
				["title"]=> string(5) "story" 
				["href"]=> string(96) "http://www.bbc.co.uk/zhongwen/trad/press_review/2013/07/130702_ukpress_wang_jing_nicaragua.shtml" } } 
		[2]=> object(SimpleXMLElement)#113 (1) {
			["@attributes"]=> array(4) {
				["rel"]=> string(7) "related" 
				["type"]=> string(9) "text/html" 
				["title"]=> string(5) "story" 
				["href"]=> string(83) "http://www.bbc.co.uk/zhongwen/trad/china/2013/06/130625_china_nicaragua_canal.shtml" } } 
		[3]=> object(SimpleXMLElement)#114 (1) {
			["@attributes"]=> array(4) {
				["rel"]=> string(7) "related" 
				["type"]=> string(9) "text/html" 
				["title"]=> string(5) "story" 
				["href"]=> string(96) "http://www.bbc.co.uk/zhongwen/trad/china/2013/06/130611_nicaragua_chinese_fund_enterprises.shtml" } } } }


<media:content>
    <media:thumbnail url="http://wscdn.bbc.co.uk/worldservice/ic/106x60/wscdn.bbc.co.uk/worldservice/assets/images/2013/07/29/130729191739_gsk_144x81_getty_nocredit.jpg"
                     width="106"
                     height="60">
       <img alt="" width="106" height="60"
            src="http://wscdn.bbc.co.uk/worldservice/ic/106x60/wscdn.bbc.co.uk/worldservice/assets/images/2013/07/29/130729191739_gsk_144x81_getty_nocredit.jpg"/>
    </media:thumbnail>
    <media:thumbnail url="http://wscdn.bbc.co.uk/worldservice/assets/images/2013/07/29/130729191739_gsk_144x81_getty_nocredit.jpg"
                     width="144"
                     height="81">
       <img alt="GSK" width="144" height="81"
            src="http://wscdn.bbc.co.uk/worldservice/assets/images/2013/07/29/130729191739_gsk_144x81_getty_nocredit.jpg"/>
    </media:thumbnail>
</media:content>

*/
function get_rss_content($sql_ids = '')
{
	global $db, $user;
	global $is_cjk, $html_filter, $text_filter, $url_filter;	// This pass local vars to called functions

	$user->add_lang('find');

	if (empty($sql_ids))
	{
		trigger_error('NO_IDS');
	}

	$is_cjk = false;
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

	// load custom filter variables
	if (!function_exists('rss_filter'))
	{
		global $phpbb_root_path, $phpEx;
		include($phpbb_root_path . 'includes/find_filters.' . $phpEx);
	}

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
		if (defined('FIND_CJK') && function_exists('cjk_tidy'))
		{
			$ary = explode(',', FIND_CJK);
			$is_cjk = (in_array($row['user_lang'] , $ary)) ? true : false;
		}

		// prepare some vars
		$feed_id				= $row['feed_id'];
		$feedname_topic	= $row['feedname_topic'];
		$last_update		= (int) $row['last_import'];
		$item_limit			= (int) $row['post_items'];
		$post_limit			= (int) $row['post_contents'];
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

		if (function_exists('simplexml_load_file') && ini_get('allow_url_fopen'))
		{
			// suppress error
			//		libxml_use_internal_errors(true);
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
			// used if suppressed
			//		$msg['err'][] = libxml_get_errors();
			//		libxml_clear_errors();
			continue;
		}
//var_dump($xml);
//return false;
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
			$title = rss_filter($item->title);
			$desc = ($is_rss) ? $item->description : ( (isset($item->content)) ? $item->content : $item->summary );

			if (empty($title) || empty($desc))
			{
				// Not validate, issue error
				$msg['err'][] = sprintf($user->lang['NO_ITEM_INFO'], $feedname);
				continue;
			}

			$item_title = truncate_string($title, 60, 255, false, $user->lang['TRUNCATE']);
			
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
					$message .= sprintf($user->lang['BB_CAT'], rss_filter($item->category));
				}
				// else - for bbc chinese
			}

			if (isset($item->author->name))
			{
				$author	= rss_filter($item->author->name);
				$author	.= (isset($item->author->email)) ? "\t" . rss_filter($item->author->email) : '';
			}
//			elseif (isset($item->author))
			else
			{
				$author	= rss_filter($item->author);
			}

			$message .= (!empty($author)) ? sprintf($user->lang['BB_AUTHOR'], $author): '';

			// Now we add the content
			if ($inc_html)
			{
				html2bb($desc);
			}

			$desc = strip_tags($desc);

			// Apply custom filters
			foreach ($text_filter as $filter)
			{
				$desc = str_replace($filter[0], $filter[1], $desc);
			}
			
			$desc = rss_filter($desc, $inc_html, true);

			if ($is_cjk && function_exists('cjk_tidy'))
			{

				$desc = cjk_tidy($desc);
				$item_title = cjk_tidy($item_title);
			}

			if ($post_limit)
			{
				$desc = truncate_strings($desc, $post_limit, 255, FALSE, $user->lang['TRUNCATE']);
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
				$channel			= rss_filter($channel);
				$channel_desc	= ($is_rss) ? $xml->channel->description : $xml->subtitle;
				$channel_desc	= rss_filter($channel_desc);
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
			$channel_rights	= rss_filter(str_replace("©", "&#169;", $channel_rights));

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

	$db->sql_freeresult($result);

	return $msg;
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
*	Convert html tags to BBCode, supported tags are: strong,b,u,em,i,ul,ol,li,img,a,p (11)
*/
function html2bb(&$html)
{
	global $html_filter;

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
	// apply custom html filters
	foreach ($html_filter as $filter)
	{
		$html = preg_replace($filter[0], $filter[1], $html);
	}
	
	// process <img> tags
	if (preg_match_all('#<img[^>]*(?:src="(http[^"]+\.(?:gif|jp[2g]|png|xbm))).+?>#is', $html, $tag, PREG_PATTERN_ORDER))
	{
		global $config;

		$i = 0;
		foreach ($tag[0] as $not_used => $img_tag)
		{
			$bbcode = '';
			$url = fix_url($tag[1][$i]);

			if ($url)
			{
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
			$bbcode = '';
			$url = fix_url($tag[1][$i]);

			if ($url)
			{
				$txt = str_replace(array(' ', "\n"), '', $tag[2][$i]);

				if (empty($txt))	// [url]link[/url]
				{
					$bbcode = "[url]${url}[/url]";
				}
				else	// [url=link]text[/url]
				{
					$bbcode = "[url=${url}]${txt}[/url]";
				}
			}

			$html = str_replace($a_tag, $bbcode, $html);
			$i++;
		}
	}

	return;
}


?>
