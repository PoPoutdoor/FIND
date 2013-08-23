<?php
/**
*
* @author PoPoutdoor
*
* @package RSS_input
* @version $Id:$
* @copyright (c) 2008-2013 PoPoutdoor
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
/*
	// load custom filter variables
	if (!function_exists('rss_filter'))
	{
		global $phpbb_root_path, $phpEx;
		include($phpbb_root_path . 'includes/find_filters.' . $phpEx);
	}
*/
	// current custom filter keys
	$filter_keys = array('URL', 'TEXT', 'HTML');

	// fetch news from selected feeds
	while ($row = $db->sql_fetchrow($result))
	{
		$feedname = $row['feed_name'];

		// feed not active, skipped to next
		if (!$row['feed_state'])
		{
			$msg['skip'][] = sprintf($user->lang['FEED_NOT_ACTIVE'], $feedname);
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
		$feed_id				= (int) $row['feed_id'];
		$feedname_topic	= (bool) $row['feed_name_subject'];
		$last_update		= (int) $row['last_update'];
		$max_articles		= (int) $row['max_articles'];
		$max_contents		= (int) $row['max_contents'];
		$inc_info			= (bool) $row['feed_info'];
		$inc_cat				= (bool) $row['article_cat'];
		$inc_html			= (bool) $row['article_html'];
		$post_mode			= (int) $row['post_mode'];
		$forum_id			= (int) $row['post_forum'];
		$forum_name			= (string) $row['forum_name'];

		$user->ip							= $row['bot_ip'];
		$user->data['user_id']			= (int) $row['bot_id'];	// also used for update forum tracking
		$user->data['username']			= $bot_name;
		$user->data['user_colour']		= $row['user_colour'];
		$user->data['is_registered']	= 1;	// also used for update forum tracking

		if (function_exists('simplexml_load_file') && ini_get('allow_url_fopen'))
		{
			// Set the user agent if remote block access by this.
			@ini_set('user_agent', 'FIND Bot');
			@ini_set('session.use_cookies', 0);

			// suppress error
			//libxml_use_internal_errors(true);
			// FIXME: session cookie is set for Google feed which block access to xml file, don't know how to disable cookie
			$xml = simplexml_load_file($row['feed_url'], 'SimpleXMLElement', LIBXML_NOCDATA);
//			$xml = simplexml_load_string($file, 'SimpleXMLElement', LIBXML_NOCDATA);
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
			$msg['err'][] = sprintf($user->lang['FEED_FETCH_ERR'], $feedname, $row['feed_url']);
			// used if suppressed and handle error in this code
			//		$msg['err'][] = libxml_get_errors();
			libxml_clear_errors();
			continue;
		}

		// build feed filter
		$feed_filters = json_decode($row['feed_filters'], true);

		foreach ($filter_keys as $key)
		{
			$key = strtolower($key);
			$vals = $feed_filters[${key}];
			${$key . '_filter'} = array();

			if (!is_null($vals))
			{
				foreach ($vals as $value)
				{
					${$key . '_filter'}[] = $value;
				}
			}
		}
//if ($feed_id == 6) {
//	var_dump($feed_filters);
//}
		// check compliance
		$is_rss = ( isset($xml->channel) ) ? TRUE : FALSE;
/*
		$validate = ($is_rss) ? ( !is_null($xml->channel->title) && !is_null($xml->channel->description) && !is_null($xml->channel->link) ) : ( !is_null($xml->id) && !is_null($xml->title) && !is_null($xml->updated) );
		if (!$validate)
		{
			// Not validate, issue error
			$msg['err'][] = sprintf($user->lang['FEED_NOT_VALID'], $feedname);
			continue;
		}
*/
		// check source timestamp
		$now = time();

		if ($is_rss)
		{
			$feed_ts = ( isset($xml->channel->lastBuildDate) ) ? strtotime($xml->channel->lastBuildDate) : strtotime($xml->channel->pubDate);
		}
		else
		{
			$feed_ts = strtotime($xml->updated);
		}

		//--- $feed_ts is last feed update timestamp(or last build for RSS) ---//

		if ($feed_ts === FALSE)
		{
			// issue error for ATOM but continued for RSS
			if ($is_rss)
			{
				$feed_ts = $now;	
			}
			else
			{
				$msg['err'][] = sprintf($user->lang['FEED_TS_INVALID'], $feedname);
				continue;
			}
		}

		// RSS ttl support
		$ttl = ( isset($xml->channel->ttl) ) ? $feed_ts + $xml->channel->ttl * 60 : 0;

		if ( $feed_ts <= $last_update || $ttl >= $now)
		{
			// source not updated, skip to next
			$msg['skip'][] = sprintf($user->lang['FEED_NO_UPDATES'], $feedname);
			continue;
		}

/* We use Bot user language from here */
		$user->lang_name = $row['user_lang'];
		$user->add_lang('find_posting');

		// namespace support
		$ns = $xml->getNameSpaces(true);
		$inc_ns = (empty($ns)) ? false : true;

		// message body vars
		$processed = $skipped = $latest_ts = 0;
		if (empty($post_mode))
		{
			$post_ary = array();
		}
		else
		{
			$contents = '';
		}

		// handle item/entry
		$i = 0;
		$no_post_ts = true;

		if ($is_rss)
		{
			$feed = $xml->xpath('//item');

			if (isset($feed[0]->pubDate))
			{
				usort($feed, function($a, $b)
				{
					return strcmp($b->pubDate, $a->pubDate);
				});

				$no_post_ts = false;
			}
		}
		else
		{
			$feed = array();
			foreach ($xml->entry as $entry)
			{
				$feed[] = $entry;
			}

			if (isset($feed[0]->updated))
			{
				usort($feed, function($a, $b)
				{
					return strcmp($b->updated, $a->updated);
				});

				$no_post_ts = false;
			}
			elseif (isset($feed[0]->published))
			{
				usort($feed, function($a, $b)
				{
					return strcmp($b->published, $a->published);
				});

				$no_post_ts = false;
			}
		}

		foreach ($feed as $post)
		{
			// Respect item limit setting
			if ($max_articles && $i++ === $max_articles)
			{
				break;
			}

			if ($no_post_ts)
			{
				$post_ts = $feed_ts;	// should issue timestamp error and exit this feed processing
			}
			else
			{
				// check item timestamp
				$post_ts = ($is_rss) ? strtotime($post->pubDate) : (isset($post->updated) ? strtotime($post->updated) : strtotime($post->published));

				if ($post_ts === FALSE)
				{
					$post_ts = $feed_ts;	// should issue timestamp error and exit this feed processing
				}
			}

			// skip to next item if outdated
			if ($post_ts <= $last_update)
			{
				$skipped++;
				continue;
			}

			// latest post timestamp
			if ($latest_ts < $post_ts)
			{
				$latest_ts = $post_ts;
			}

			// preprocess item values
			$title = fix_text($post->title);
			$desc = ($is_rss) ? $post->description : ( (isset($post->content)) ? $post->content : $post->summary );

			if (empty($title) && empty($desc))
			{
				// Not validate, issue error
				$msg['err'][] = sprintf($user->lang['NO_POST_INFO'], $feedname);
				continue;
			}

			$post_title = truncate_string($title, 60, 255, false, $user->lang['TRUNCATE']);
			
			// prepare the message text
/* bb_message() */
			$message = '';

			// no timestamp
			if ($post_ts != $feed_ts)
			{
				$message .= sprintf($user->lang['BB_POST_TS'], $user->format_date($post_ts));
			}

			if ($inc_cat && isset($post->category))
			{
				$post_cat = fix_text($post->category);
				$message .= (!empty($post_cat)) ? sprintf($user->lang['BB_CAT'], $post_cat) : '';
			}

			if (isset($post->source))
			{
				$post_source = fix_text($post->source);
				$message .= (!empty($post_source)) ? sprintf($user->lang['BB_POST_SRC'], $post_source) : '';
			}

			if (isset($post->author->name))
			{
				$author	= fix_text($post->author->name);
				$author	.= (isset($post->author->email)) ? $user->lang['TAB'] . fix_text($post->author->email) : '';
			}
			else
			{
				$author	= fix_text($post->author);
			}

			$message .= (!empty($author)) ? sprintf($user->lang['BB_AUTHOR'], $author) : '';

			if (isset($post->enclosure))
			{
				$enc_link = fix_url($post->enclosure['url']);
				$message .= ($post->enclosure['type'] == 'image/jpeg') ? "\n[img]${enc_link}[/img]\n" : "\n[url]${enc_link}[/url]\n";
			}

			// Now we add the content
			if (!empty($desc))
			{
				if ($inc_html)
				{
					html2bb($desc);
				}

				$desc = strip_tags($desc);

				$desc = fix_text($desc, $inc_html, true);

				if ($is_cjk && function_exists('cjk_tidy'))
				{
					$desc = cjk_tidy($desc);
					$post_title = cjk_tidy($post_title);
				}

				if ($max_contents)
				{
					$desc = truncate_string($desc, $max_contents, 255, FALSE, $user->lang['TRUNCATE']);
				}
			}

			if ($is_rss)
			{
				$message .= "\n" . $desc . "\n";

				$link	= fix_url($post->link);

				$comments = (isset($post->comments)) ? fix_url($post->comments) : '';

				if (!empty($link))
				{
					$message .= (!empty($desc)) ? "\n" : '';
					$message .= sprintf($user->lang['BB_URL'], $link, $user->lang['READ_MORE']);
					$message .= (empty($comments)) ? "\n" : $message .= $user->lang['TAB'];
				}
			
				if (!empty($comments))
				{
					$message .= (empty($link) && !empty($desc)) ? "\n" : '';
					$message .= sprintf($user->lang['BB_URL'], $comments, $user->lang['COMMENTS']);
				}
			}
			else
			{
				// atom multi links support
				$links = $post->link;
				$link_rel = array();

				foreach ($links as $link)
				{
					$link_url = fix_url($link['href']);
					$thumb_img = '';

					//--- namespace support ---//
					if ($inc_ns)
					{
						// media:thumbnail
						if (@count($link->children($ns['media'])))
						{
							$media = $link->children($ns['media']);
							$media_thumb = $media->content->thumbnail;
							$j = 0;	// for BBC Chinese, use the 2nd thumbnail data
							foreach ($media_thumb as $thumb)
							{
								$j++;			// for BBC Chinese
								if ($j%2)	// for BBC Chinese
								{
									$thumb_img = fix_url($thumb->attributes()->url);
								}
							}
						}
					}

					if ($link['rel'] == 'related')
					{
						if (!empty($link_url))
						{
							$link_rel[] = array($link_url, $thumb_img);
						}
					}
					else
					{
						$link_self = array($link_url, $thumb_img);
					}
				}

				if (!empty($link_rel))
				{
					$desc .= $user->lang['RELATED'];
					foreach ($link_rel as $link)
					{
						list($link_url, $thumb_img) = $link;
						$desc .= (!empty($thumb_img)) ? $user->lang['TAB'] . sprintf($user->lang['BB_URL'], $link_url, '[img]' . $thumb_img . '[/img]') : $user->lang['TAB'] . '[url]' . $link_url . '[/url]';
					}
				}

				list($link_url, $thumb_img) = $link_self;
				if (!empty($link_url))
				{
					if (!empty($thumb_img))
					{
						$desc = sprintf($user->lang['BB_URL'], $link_url, '[img]' . $thumb_img . '[/img]') . $user->lang['TAB'] . $desc;
					}

					$desc .= "\n\n" . sprintf($user->lang['BB_URL'], $link_url, $user->lang['READ_MORE']);
				}

				$message .= "\n" . $desc . "\n";
			}
/* end bb_message() */
			
			if (empty($post_mode))
			{
				$post_ary[] = array($post_title, $message);
			}
			else
			{
				$contents = sprintf($user->lang['BB_TITLE'], $post_title) . $message . "\n\n" . $contents;
			}

			$processed++;
		} // end process items
			
		if ($processed)
		{
			unset($feed);
			$heading = $feed_info = '';

			// should we include feed info
			if ($inc_info)
			{
				$source_title	= ($is_rss) ? fix_text($xml->channel->title) : fix_text($xml->title);
				$source_desc	= ($is_rss) ? fix_text($xml->channel->description) : fix_text($xml->subtitle);
				$source_link	= ($is_rss) ? fix_url((isset($xml->channel->link) ? $xml->channel->link : $xml->channel->image->link)) : fix_url($xml->link['href']);
				$source_img_url= ($is_rss) ? fix_url($xml->channel->image->url) : fix_url($xml->logo);
				$source_cat		= ($is_rss) ? fix_text($xml->channel->category) : fix_text($xml->category);

				if (!empty($source_link))
				{
					$heading .= (!empty($source_img_url)) ? sprintf($user->lang['BB_URL'], $source_link, "[img]${source_img_url}[/img]") : sprintf($user->lang['BB_URL'], $source_link, sprintf($user->lang['BB_SOURCE_TITLE'], $source_title));
				}
				else
				{
					$heading .= $source_title;
				}

				$heading .= "\n";
				
				if (!empty($source_desc) && $source_desc != $source_title)
				{
					$heading .= sprintf($user->lang['BB_SOURCE_DESC'], $source_desc);
				}
				
				$heading .= (!empty($source_cat)) ? sprintf($user->lang['BB_CAT'], $source_cat) : '';

				// clean up \n
				$heading = preg_replace("#\n+#", "\n", $heading);
			}

			// Always show the copyright notice if provided
			$source_rights	= ($is_rss) ? $xml->channel->copyright : $xml->rights;
			$source_rights	= fix_text(str_replace("©", "&#169;", $source_rights));

			if (!empty($source_rights))
			{
				$feed_info .= sprintf($user->lang['BB_COPYRIGHT'], $source_rights);
			}
			
			if ($feed_ts != $now)
			{
				$feed_info .= sprintf($user->lang['BB_SOURCE_DATE'], $user->format_date($feed_ts));
			}

			// clean up
			$feed_info = str_replace("\n\n", "\n", $feed_info) . $user->lang['HR'];
			
			unset($xml);
			
			if (!$latest_ts)
			{
				$latest_ts = $now; 
			}

			// submit each item as new post or reply
			if (empty($post_mode))
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
					$subject = (!empty($source_title)) ? $source_title : ((!empty($source_desc)) ? $source_desc : $feedname);
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
					if ($post_mode == 1)	// new topic on each day
					{
						$mode = ($user->format_date($latest_ts, 'd', true) == $user->format_date($topic_time, 'd', true)) ? 'reply' : 'post';
					}
					else	// new topic on each week/month
					{
						$format = ($post_mode == 30) ? 'm' : 'W';
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
				SET last_update = ' . $latest_ts . "
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
* Convert CR to LF, strip extra LFs and spaces, multiple newlines to 2
*
* @param $text
* @param $is_html (booleans), option to preserve html tags
* @param $newline (booleans), option to preserve newline character
* @return string
*/
function fix_text($text, $is_html = false, $newline = false)
{
	global $text_filter;

	$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");

	if ($is_html)
	{
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&#32', ' ', $text);
	}
	else
	{
		$text = strip_tags($text);

		// Apply custom filters
		foreach ($text_filter as $filter)
		{
			if (!empty($filter[0]))
			{
				$text = preg_replace($filter[0], $filter[1], $text);
			}
		}
	}

	$text = str_replace("\r", "\n", $text);
	$text = preg_replace("/(\p{Zs}|\t)+/u", '\\1', $text);
	$text = preg_replace("#(^|\n)(?:\p{Zs}|\t)+?#u", '\\1', $text);
	$text = ($newline) ? preg_replace("/\n{3,}/", "\n\n", $text) : str_replace("\n", '', $text);

	return trim($text);
}



/**
*	URL filter
*/
function fix_url($url)
{
	global $url_filter;
	
	$url = htmlspecialchars_decode($url);
	
	// apply custom filters
	foreach ($url_filter as $filter)
	{
		if (!empty($filter[0]))
		{
			$url = preg_replace($filter[0], $filter[1], $url);
		}
	}

	// validate url is prefixed with (ht|f)tp(s)?
	if (!preg_match('#^https?://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i', $url))
	{
		return;
	}

	return trim($url);
}


/**
*	Convert html tags to BBCode, supported tags are: strong,b,u,em,i,ul,ol,li,img,a,p (11)
*/
function html2bb(&$html, $html_filter = array())
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
	// apply custom html filters
	foreach ($html_filter as $filter)
	{
		if (!empty($filter))
		{
			$html = preg_replace($filter[0], $filter[1], $html);
		}
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
	//FIXME:  trim full-width spaces
	//$text = preg_replace('/^\p{Zs}+/u', '', $text);	// not works
	//$text = preg_replace('/\p{Zs}+$/u', '', $text);

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
