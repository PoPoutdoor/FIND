<?php
/**
*
* @author PoPoutdoor
*
* @package FIND
* @version $Id:$
* @copyright (c) 2008-2013 PoPoutdoor
* @license http://opensource.org/licenses/GPL-2.0
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
*	Fetch and post feed
*
*	@param	array	value is feed id
*
*	@return	array	feed operation status
*/
function post_feed( $ids = array() )
{
	global $db, $user;
	// This pass local vars to called functions
	global $is_cjk, $html_filter, $text_filter, $url_filter;

	// init return messages
	$msg = array('ok' => '', 'skip' => '', 'err' => '');

	$user->add_lang('find');

	if (!function_exists('simplexml_load_file') || !ini_get('allow_url_fopen'))
	{
		$msg['err'][] = $user->lang['NO_PHP_SUPPORT'];
		return $msg;
	}

	if (empty($ids))
	{
		$msg['err'][] = $user->lang['NO_IDS'];
		return $msg;
	}

	// current custom filter keys
	$filter_keys = array('URL', 'TEXT', 'HTML', 'SRC');
	// optional CJK support
	$is_cjk = false;
	if (defined('FIND_CJK') && function_exists('cjk_tidy'))
	{
		$cjk_ary = explode(',', FIND_CJK);
	}

	$sql_ids = (sizeof($ids) > 1) ? ' IN (' . implode(",", $ids) . ')' : ' = ' . implode(",", $ids);
	$sql = 'SELECT f.*, u.user_colour, u.user_lang, b.bot_active, b.bot_name, b.bot_ip, n.forum_name
		FROM ' . FIND_TABLE . ' f, '  . USERS_TABLE . ' u, ' . BOTS_TABLE . ' b,' . FORUMS_TABLE . " n 
		WHERE f.feed_id $sql_ids
			AND u.user_id = b.user_id
			AND f.bot_id = b.user_id
			AND n.forum_id = f.post_forum 
		ORDER BY f.post_forum ASC";
	$result = $db->sql_query($sql);

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

		// Bot not active, skipped to next feed
		if (!$row['bot_active'])
		{
			$msg['skip'][] = sprintf($user->lang['BOT_NOT_ACTIVE'], $row['bot_name']);
			continue;
		}

		// Set CJK flag by bot language
		if (defined('FIND_CJK') && isset($cjk_ary))
		{
			$is_cjk = (in_array($row['user_lang'] , $cjk_ary)) ? true : false;
		}

		// prepare some vars
		$feed_id				= (int) $row['feed_id'];
		$feed_url			= (string) htmlspecialchars_decode($row['feed_url']);
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
		$user->data['username']			= trim(str_replace(FIND_BOT_ID, '', $row['bot_name']));
		$user->data['user_colour']		= $row['user_colour'];
		$user->data['is_registered']	= 1;	// also used for update forum tracking

		// try fixing server-side issues
		$opts = array(
			'http' => array(
				'method'			=> 'GET',
				'user_agent'	=> 'FIND - news feed parser; +https://github.com/PoPoutdoor/FIND',
				'header'  		=> "Accept: application/rss+xml,application/atom+xml,text/xml:q=0.9,text/html:q=0.8\n"
			)
		);

		$context = stream_context_create($opts);
		libxml_set_streams_context($context);
		libxml_use_internal_errors(true);
		$xml = @simplexml_load_file('compress.zlib://' . $row['feed_url'], 'SimpleXMLElement', LIBXML_NOCDATA);

		if ($xml === false)
		{
			$msg['err'][] = sprintf($user->lang['FEED_FETCH_ERR'], $feedname, $row['feed_url']);
			$msg['err'][] = xml_fetch_error($http_response_header);
			continue;
		}

		// build feed filter
		//	php >= 5.4 : $feed_filters = json_decode($row['feed_filters'], true);
		$feed_filters = unserialize($row['feed_filters']);

		// init/set each filter
		foreach ($filter_keys as $key)
		{
			$key = strtolower($key);

			${$key . '_filter'} = array();

			$vals = $feed_filters[${key}];
			if (!empty($vals))
			{
				foreach ($vals as $value)
				{
					${$key . '_filter'}[] = $value;
				}
			}
		}

		// check compliance
		$is_rss = ( isset($xml->channel) ) ? TRUE : FALSE;

		// check source timestamp
		$now = time();

		// $feed_ts is last feed update timestamp(or last build for RSS)
		if ($is_rss)
		{
			$feed_ts = ( isset($xml->channel->lastBuildDate) ) ? strtotime($xml->channel->lastBuildDate) : strtotime($xml->channel->pubDate);
		}
		else
		{
			$feed_ts = strtotime($xml->updated);
		}

		if ($feed_ts === false)
		{
			// issue error and skip for ATOM
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

		if ( $feed_ts != $now )
		{
			// RSS: TTL support
			$ttl = ( isset($xml->channel->ttl) ) ? $feed_ts + $xml->channel->ttl * 60 : 0;

			// source not updated, fetch next feed
			if ( $feed_ts <= $last_update || $ttl >= $now)
			{
				$msg['skip'][] = sprintf($user->lang['FEED_OLD'], $feedname);
				continue;
			}
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
		$no_post_ts = false;

		if ($is_rss)
		{
			$feed = $xml->xpath('//item');
			// version < 2.0?
			if (empty($feed))
			{
				foreach ($xml->item as $item)
				{
					$feed[] = $item;
				}

				$no_post_ts = true;
			}
			else
			{
				if (isset($feed[0]->pubDate))
				{
					foreach($feed as $post)
					{
						$post->pubDate = strtotime(trim($post->pubDate));
					}

					usort($feed, function($a, $b)
					{
						return strcmp($b->pubDate, $a->pubDate);
					});
				}
				else
				{
					$no_post_ts = true;
				}
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
				// TODO: add posting lang:update flag
				foreach($feed as $post)
				{
					$post->updated = strtotime(trim($post->updated));
				}

				usort($feed, function($a, $b)
				{
					return strcmp($b->updated, $a->updated);
				});
			}
			elseif (isset($feed[0]->published))
			{
				// TODO: add posting lang:publish flag
				foreach($feed as $post)
				{
					$post->published = strtotime(trim($post->published));
				}

				usort($feed, function($a, $b)
				{
					return strcmp($b->published, $a->published);
				});
			}
			else
			{
				$no_post_ts = true;
			}
		}

		$i = 0;
		foreach ($feed as $post)
		{
			// Respect limit setting
			if ($max_articles && $i++ === $max_articles)
			{
				break;
			}

			// article timestamp
			if (!$no_post_ts)
			{
				$post_ts = ($is_rss) ? $post->pubDate : (isset($post->updated) ? $post->updated : $post->published);

				// skip to next article if outdated
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
			}

			// preprocess article data
			$title = fix_text($post->title);
			// optional CJK support
			if ($is_cjk)
			{
				$title = cjk_tidy($title);
			}

			$desc = ($is_rss) ? $post->description : ( (isset($post->content)) ? $post->content : $post->summary );
			// Not validate, issue error
			if (empty($title) && empty($desc))
			{
				$msg['err'][] = sprintf($user->lang['NO_POST_INFO'], $feedname);
				continue;
			}

			// prepare message body
			$message = '';

			// article time			
			if (!$no_post_ts)
			{
				$message .= sprintf($user->lang['BB_POST_TS'], $user->format_date($post_ts));
			}
			// article category
			if ($inc_cat && isset($post->category))
			{
				$post_cat = fix_text($post->category);
				$message .= (!empty($post_cat)) ? sprintf($user->lang['BB_CAT'], $post_cat) : '';
			}

			// RSS: article source
			if (isset($post->source))
			{
				$post_source = fix_text($post->source);
				// Apply source filters
				if (!empty($src_filter))
				{
					if (preg_match($src_filter[0][0], $post_source))
					{
						if ($max_articles)
						{
							$max_articles++;
						}
						continue;
					}
				}

				$message .= (!empty($post_source)) ? sprintf($user->lang['BB_POST_SRC'], $post_source) : '';
			}
			// article author
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
			// RSS: article enclosure
			// TODO: ATOM need namespace support code (link elements with rel="enclosure")
			if (isset($post->enclosure))
			{
				$enc_link = fix_url($post->enclosure['url']);
				$message .= ($post->enclosure['type'] == 'image/jpeg') ? "\n[img]${enc_link}[/img]\n" : "\n[url]${enc_link}[/url]\n";
			}

			// article contents
			if (!empty($desc))
			{
				if ($inc_html)
				{
					$desc = fix_text($desc, true, true);
					$desc = html2bb($desc);
				}

				$desc = fix_text($desc, false, true);

				// RSS+XML namespace support
				if ($inc_ns)
				{
					// media:content
					if (@count($post->children($ns['media'])))
					{
						$media = $post->children($ns['media']);

						if (isset($media->content))
						{
							$media_img = (string) $media->content->attributes()->url;
						}

						$img = fix_url($media_img[0]);
						if (!empty($img))
						{
							$desc = '[img]' . $img . '[/img]' . $desc;
						}
					}
				}
				// add \n\n if begin with an image
				$desc = preg_replace('@^(\[(url|img).+?\[/\\2\])\n{0,}@', "\\1\n\n", $desc);

				// optional CJK support
				if ($is_cjk)
				{
					$desc = cjk_tidy($desc);
				}

				// limit characters to post
				if ($max_contents)
				{
					$desc = truncate_string($desc, $max_contents, 255, FALSE, $user->lang['TRUNCATE']);
				}
			}

			// article related link(s)
			if ($is_rss)
			{
				$message .= "\n" . $desc . "\n";

				$link	= fix_url($post->link);

				$comments = (isset($post->comments)) ? fix_url($post->comments) : '';

				if (!empty($link))
				{
					$message .= (!empty($desc)) ? "\n" : '';
					$message .= sprintf($user->lang['BB_URL'], $link, $user->lang['READ_MORE']);
					$message .= (empty($comments)) ? "\n" : $user->lang['TAB'];
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
							if (@count($media_thumb))
							{
								foreach ($media_thumb as $thumb)
								{
									$thumb_img = fix_url($thumb->attributes()->url);
									// select first thumbnail only
									if (!empty($thumb_img))
									{
										break;
									}
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
						$desc = sprintf($user->lang['BB_URL'], $link_url, '[img]' . $thumb_img . '[/img]') . "\n\n" . $desc;
					}

					$desc .= "\n\n" . sprintf($user->lang['BB_URL'], $link_url, $user->lang['READ_MORE']);
				}

				$message .= "\n" . $desc . "\n";
			}	// end message body

			// store  message body
			if (empty($post_mode))
			{
				$post_ary[] = array(truncate_string($title, 60, 255, false, $user->lang['TRUNCATE']), $message);
			}
			else
			{
				$contents = sprintf($user->lang['BB_TITLE'], $title) . $message . "\n\n" . $contents;
			}

			$processed++;
		} // end article process 
			
		if ($processed)
		{
			unset($feed);
			$heading = $feed_info = '';

			// set post header
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

			// always show feed info
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

			// submit each article as new post or reply
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
					autopost($forum_id, $forum_name, $mode, $subject, $heading . $feed_info . $post_contents);
				}
			}
			// pack all article data with additional info in one post
			else
			{
				// set post subject
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

				autopost($forum_id, $forum_name, $mode, $subject, $contents, $topic_id);
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


function xml_fetch_error($http_response_header)
{
	global $user;

	$error = $user->lang['RESPONSE_HEADER'];

	foreach($http_response_header as $line)
	{
		if (preg_match('#^(?:HTTP|Content-Type|X-)#', $line))
		{
			$error .= "\t$line\n";
		}
	}

	if ($xml_error = libxml_get_errors())
	{
		$err = $xml_error[0];
		if ($err->file)
		{
			$error .= $user->lang['XML_ERROR'];

			switch ($err->level)
			{
				case LIBXML_ERR_WARNING:
					$error .= "Warning $err->code: ";
					break;
				case LIBXML_ERR_ERROR:
					$error .= "Error $err->code: ";
					break;
				case LIBXML_ERR_FATAL:
					$error .= "Fatal Error $err->code: ";
					break;
			}

			$error .= trim($err->message);
			$error .= sprintf($user->lang['LINE_COLUMN'], $err->line, $err->column);
		}

		libxml_clear_errors();
	}

	return $error;
}


/**
* Bot Submit Post
*
* $mode: post/reply, 'approve' always "true'.
* No edit, no poll, no attachment, no quote, no globalising, no indexing
*	no notifications. tracking/markread for poster.
*/
function autopost($forum_id, $forum_name, $mode, $subject, $message, $topic_id = 0)
{
	global $user, $phpEx, $phpbb_root_path;

	if (!function_exists('submit_post'))
	{
		include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
	}

	// variables to hold the parameters
	$uid = $bitfield = $options = '';

	$subject = htmlentities($subject);
	$message = htmlentities($message);
	generate_text_for_storage($subject, $uid, $bitfield, $options);
	generate_text_for_storage($message, $uid, $bitfield, $options, true, true, false);

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
		if (!empty($text_filter))
		{
			foreach ($text_filter as $filter)
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
	if (!empty($url_filter))
	{
		foreach ($url_filter as $filter)
		{
			$url = preg_replace($filter[0], $filter[1], $url);
		}
	}

	// yahoo link
	$pos = strrpos($url, '-/http');
	if ($pos !==false)
	{
		$url = substr($url, $pos + 2);
	}

	// validate url is prefixed with (ht|f)tp(s)?
	if (!preg_match('#^(ht|f)tps?://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i', $url))
	{
		return;
	}

	return trim($url);
}


/**
*	Convert html tags to BBCode, supported tags are: strong,b,u,em,i,ul,ol,li,img,a,p (11)
*/
function html2bb($html)
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
	// list
	$html = preg_replace(
		array('#<ul\s.*?>#is', '#<ol\s+?type="(\w+)">#is', '#<ol\s+?style=".*?decimal">#is', '#<li\s.*?>#is'),
		array('[list]', '[list=\\1]', '[list=1]', '[*]'),
		$html
	);

	$html = preg_replace('#<p\s.*?>#is', "\n\n", $html);	// <p ...>

	// br2nl
	$html = preg_replace('#<br.*?>#is', "\n", $html);	

	// strip in-line script/style
	$html = preg_replace('#<(script|style).*?\\1>#is', '', $html);	

	// apply custom html filters
	if (!empty($html_filter))
	{
		foreach ($html_filter as $filter)
		{
			$html = preg_replace($filter[0], $filter[1], $html);
		}
	}
	
	// process phpbb.com <blockquote>...<cite>@somebody wrote:</cite>...</blockquote>
	if (preg_match_all('#<blockquote.+?>(?:<cite>(.*?)</cite>)?(.*?)</blockquote>#is', $html, $tag, PREG_PATTERN_ORDER))
	{
		$i = 0;
		$bbcode = array();
		foreach ($tag[0] as $not_used)
		{
			$wrote = trim(str_replace(' wrote:', '', $tag[1][$i]));

			$bbcode[] = (!empty($wrote)) ? '[quote="' . $wrote . '"]' . $tag[2][$i] . '[/quote]' : '[quote]' . $tag[2][$i] . '[/quote]';

			$i++;
		}

		$html = str_replace($tag[0], $bbcode, $html);
	}

	// process <img> tags
	if (preg_match_all('#<img[^>]*(?:src="(http[^"]+\.(?:gif|jp[2g|eg]|png|xbm))).+?>#is', $html, $tag, PREG_PATTERN_ORDER))
	{
		global $config;

		$i = 0;
		$bbcode = array();
		foreach ($tag[0] as $not_used)
		{
			$url = fix_url($tag[1][$i]);

			if ($url)
			{
				$bbcode[] = '[img]' . $url . '[/img]';
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
							$bbcode[] = $url;
						}
					}
				}
			}

			$i++;
		}

		$html = str_replace($tag[0], $bbcode, $html);
	}
	// cleanup non-gfx link target
	else
	{
		$html = preg_replace('#<img[^>]*>#is', '', $html);
	}

	// process <a> tags
	if (preg_match_all('#<a[^>]*(?:href="((?:ht|f)tp[^"]+)").*?>(.+?)</a>#is', $html, $tag, PREG_PATTERN_ORDER))
	{
		$i = 0;
		$bbcode= array();

		foreach ($tag[0] as $not_used)
		{
			$url = fix_url($tag[1][$i]);

			if ($url)
			{
				$txt = fix_text($tag[2][$i]);

				if (empty($txt))	// [url]link[/url]
				{
					$bbcode[] = "[url]${url}[/url]";
				}
				else	// [url=link]text[/url]
				{
					$bbcode[] = "[url=${url}]${txt}[/url]";
				}
			}

			$i++;
		}

		$html = str_replace($tag[0], $bbcode, $html);
	}

	return trim($html);
}


?>
