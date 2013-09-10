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
* @package acp
*/
class acp_find
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $template, $cache, $phpbb_root_path, $phpEx;

		$this->tpl_name = 'acp_find';
		$this->page_title = 'ACP_FIND';
		$form_key = 'acp_find';
		add_form_key($form_key);

		$error = $prompt = array();

		$submit	= (isset($_POST['submit'])) ? true : false;

		if (($submit) && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}

		$user->add_lang('acp/find');

		if (!function_exists('simplexml_load_file') || !ini_get('allow_url_fopen'))
		{
			trigger_error('NO_PHP_SUPPORT');
		}

		$mark		= request_var('mark', array(0));
		$feed_id	= request_var('id', 0);
		$action	= request_var('action', '');
		$check	= request_var('feed_check', false);

		$url_checked = true;

		if (isset($_POST['add']))
		{
			$action = 'add';
			$url_checked = false;
			$prompt[] = $user->lang['CHECK_URL_EXPLAIN'];
		}

		if ($feed_id)
		{
			$sql_ids = " = $feed_id";
			$ids = array($feed_id);
		}
		else if (sizeof($mark))
		{
			$sql_ids = ' IN (' . implode(",", $mark) . ')';
			$ids = $mark;
		}
		else
		{
			if ($action && $action != 'add')
			{
				trigger_error( $user->lang['NO_FEED'] . adm_back_link($this->u_action));
			}
		}

		// get feedname list for batch action message
		if ($action == 'activate' || $action == 'deactivate' || $action == 'delete')
		{
			// fetch feed_name
			$sql = 'SELECT feed_name
				FROM ' . FIND_TABLE . "
				WHERE feed_id $sql_ids";
			$result = $db->sql_query($sql);

			$ary = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$ary[] = $row['feed_name'];
			}

			$db->sql_freeresult($result);

			$feedname_list = implode('<br />', $ary);
		}

		// action
		$message = '';
		switch ($action)
		{
			case 'activate':
			case 'deactivate':
				$activate = ($action == 'activate') ? 1 : 0;
				$message .= sprintf($user->lang[strtoupper($action) . '_FEED'], $feedname_list);

				$sql = 'UPDATE ' . FIND_TABLE . "
					SET feed_state = $activate
					WHERE feed_id $sql_ids";
				$db->sql_query($sql);

				$db->sql_transaction('commit');

				$cache->destroy('_find');

				trigger_error( $message . adm_back_link($this->u_action));
			break;

			case 'import':
				include($phpbb_root_path . 'includes/functions_find.' . $phpEx);

				// backup $user data
				$user_ip		= $user->ip;
				$user_id		= $user->data['user_id'];
				$username	= $user->data['username'];
				$colour		= $user->data['user_colour'];
				$registered = $user->data['is_registered'];

				$ret = post_feed($ids);

				// restore current $user data
				$user->ip							= $user_ip;
				$user->data['user_id']			= $user_id;
				$user->data['username']			= $username;
				$user->data['user_colour']		= $colour;
				$user->data['is_registered']	= $registered;

				// process returned message
				$msg_ary = array('OK', 'SKIP', 'ERR');

				foreach ($msg_ary as $msg)
				{
					$message .= $user->lang['IMPORT_' . $msg];
					$msg = strtolower($msg);
					if (empty($ret[$msg]))
					{
						$message .= $user->lang['NONE'];
					}
					else
					{
						$message .= "\n\n";
						foreach ($ret[$msg] as $key => $text)
						{
							$message .= "    $text\n";
						}
					}
				}

				trigger_error(generate_text_for_display($message, null,null, null) . adm_back_link($this->u_action));
			break;

			case 'delete':
				if (confirm_box(true))
				{
					// delete data from db
					$db->sql_transaction('begin');

					$sql = 'DELETE FROM ' . FIND_TABLE . "
						WHERE feed_id $sql_ids";
					$db->sql_query($sql);

					$db->sql_transaction('commit');

					$cache->destroy('_find');

					$message .= sprintf($user->lang[strtoupper($action) . '_FEED'], $feedname_list, '');

					add_log('admin', 'LOG_FEED_DELETED', $feedname_list);
					trigger_error( $message . adm_back_link($this->u_action));
				}
				else
				{
					$message .= sprintf($user->lang[strtoupper($action) . '_FEED'], $feedname_list, $user->lang['CONFIRM_OPERATION']);
					confirm_box(false, $message, build_hidden_fields(array(
						'mark'	=> $mark,
						'id'		=> $feed_id,
						'mode'	=> $mode,
						'action'	=> $action))
					);
				}
			break;

			case 'add':
				$select_id = false;
			case 'edit':
				// current custom filter keys
				$filter_keys = array('URL', 'TEXT', 'HTML', 'SRC');

				$feed_filters = array();

				if ($submit)
				{
					// generate feed filters
					foreach ($filter_keys as $key)
					{
						$key = strtolower($key);
						$search_ary = request_var($key . '_search', array(''), true);
						$replace_ary = request_var($key . '_replace', array(''), true);

						if (empty($search_ary[0]))
						{
							$feed_filters[${key}] = array();
						}
						else
						{
							$i = 0;
							$search_ary = utf8_normalize_nfc($search_ary);
							$replace_ary = utf8_normalize_nfc($replace_ary);
							foreach ($search_ary as $search)
							{
								if (!empty($search))
								{
									$feed_filters[${key}][$i] = array(
										html_entity_decode($search, ENT_QUOTES, 'UTF-8'),
										html_entity_decode($replace_ary[$i], ENT_QUOTES, 'UTF-8'),
									);
									$i++;
								}
							}
						}
					}

					// normalise form values
					$feed_data = array(
						'feed_url'		=> htmlspecialchars_decode(request_var('feed_url', '')),
						'feed_state'	=> request_var('feed_state', 0),
						'post_forum'	=> request_var('post_forum', 0),
						'bot_id'			=> request_var('bot_id', 0),
						'feed_name'		=> utf8_normalize_nfc(htmlspecialchars_decode(request_var('feed_name', '', true))),
						'feed_name_subject'	=> request_var('feed_name_subject', 0),
						'post_mode'		=> request_var('post_mode', 1),
						'max_articles'	=> request_var('max_articles', 0),
						'max_contents'	=> request_var('max_contents', 0),
						'feed_info'		=> request_var('feed_info', 1),
						'article_cat'	=> request_var('article_cat', 1),
						'article_html'	=> request_var('article_html', 1),
						'feed_filters'	=> $feed_filters,
					);

					// validate url
					if (empty($feed_data['feed_url']))
					{
						$error[] = $user->lang['NO_FEED_URL'];
					}
					else if (utf8_strlen($feed_data['feed_url']) < 12)
					{
						$error[] = $user->lang['URL_TOO_SHORT'];
					}
					else if (utf8_strlen($feed_data['feed_url']) > 255)
					{
						$error[] = $user->lang['URL_TOO_LONG'];
					}
					else if (!preg_match('#^https?://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i', $feed_data['feed_url']))
					{
						$error[] = $user->lang['URL_NOT_VALID'];
					}

					// validate feed and detect feed properties
					if ($check && !sizeof($error))
					{
						$url = 'http://validator.w3.org/feed/check.cgi?url=' . urlencode($feed_data['feed_url']);

						$opts = array('http' =>
							array(
								'method'  => 'GET',
								'header'  => "User-agent: FIND - news feed parser; +https://github.com/PoPoutdoor/FIND;\n" .
												 "Accept: text/xml;\n"
							)
						);

						$context = stream_context_create($opts);

						if (preg_match('/Congratulations!/i', file_get_contents($url, false, $context)))
						{
							libxml_set_streams_context($context);
							libxml_use_internal_errors(true);
							$xml = @simplexml_load_file("compress.zlib://" . $feed_data['feed_url'], 'SimpleXMLElement', LIBXML_NOCDATA);

							if ($xml === false)
							{
								include($phpbb_root_path . 'includes/functions_find.' . $phpEx);

								$error[] = $user->lang['FEED_VALIDATED'];
								$error[] = xml_fetch_error($http_response_header);
							}
							else
							{
								$prompt[] = $user->lang['FEED_VALIDATED'];

								$is_rss = ( isset($xml->channel) ) ? TRUE : FALSE;

								$source_title	= ($is_rss) ? trim($xml->channel->title) : trim($xml->title);
								$source_cat		= ($is_rss) ? trim($xml->channel->category) : trim($xml->category);
								
								if (empty($source_title))
								{
									$prompt[] = $user->lang['FEEDNAME_NOT_PROVIDED'];
								}

								if ($is_rss)
								{
									$feed = $xml->xpath('//item');
								}
								else
								{
									$feed = array();
									foreach ($xml->entry as $entry)
									{
										$feed[] = $entry;
									}
								}

								$desc = ($is_rss) ? $feed[0]->description : ( (isset($feed[0]->content)) ? $feed[0]->content : $feed[0]->summary );
								$desc = html_entity_decode($desc, ENT_QUOTES, "UTF-8");
								
								if (empty($feed_data['feed_name']))
								{
									$feed_data['feed_name']		= (empty($source_title)) ? $user->lang['NONE'] : utf8_normalize_nfc($source_title);
									$feed_data['feed_name_subject']	= (empty($source_title)) ? 1 : 0;
								}
								$feed_data['max_articles']	= (sizeof($feed) < 30) ? 0 : 30;
								$feed_data['max_contents']	= (strlen(strip_tags($desc) < 2000)) ? 0 : 2000;
								$feed_data['feed_info']		= (empty($source_title)) ? 0 : 1;
								$feed_data['article_cat']	= (empty($source_cat)) ? 0 : 1;
								$feed_data['article_html']	= ($desc == strip_tags($desc)) ? 0 : 1;
								$feed_data['feed_state']	= 1;
								
								if ($action == 'add')
								{
									$prompt[] = $user->lang['SELECT_FORUM_BOT'];
								}

								$prompt[] = $user->lang['REVIEW_SUBMIT'];
							}
						}
						else
						{
							$error[] = sprintf($user->lang['FEED_NOT_VALIDATE'], $url);
						}
					}
					else
					{
						if (!$feed_data['post_forum'])
						{
							$error[] = $user->lang['NO_FORUM'];
						}

						if (!$feed_data['bot_id'])
						{
							$error[] = $user->lang['NO_USER'];
						}

						if (empty($feed_data['feed_name']))
						{
							$error[] = $user->lang['NO_FEEDNAME'];
						}
						else if (utf8_strlen($feed_data['feed_name']) < 3)
						{
							$error[] = $user->lang['NAME_TOO_SHORT'];
						}
						else if (utf8_strlen($feed_data['feed_name']) > 255)
						{
							$error[] = $user->lang['NAME_TOO_LONG'];
						}

						if (!sizeof($error))
						{
							$sql_ary = array(
								'feed_url'		=> (string) $feed_data['feed_url'],
								'feed_state'	=>	(int) $feed_data['feed_state'],
								'post_forum'	=> (int) $feed_data['post_forum'],
								'bot_id'			=> (int) $feed_data['bot_id'],
								'feed_name'		=> (string) $feed_data['feed_name'],
								'feed_name_subject'	=> (int) $feed_data['feed_name_subject'],
								'post_mode'		=> (int) $feed_data['post_mode'],
								'max_articles'	=> (int) $feed_data['max_articles'],
								'max_contents'	=> (int) $feed_data['max_contents'],
								'feed_info'		=> (int) $feed_data['feed_info'],
								'article_cat'	=> (int) $feed_data['article_cat'],
								'article_html'	=> (int) $feed_data['article_html'],
								'feed_filters'	=> serialize($feed_filters),
							//	php >= 5.4 : 'feed_filters'	=> json_encode($feed_filters, JSON_UNESCAPED_UNICODE),
							);

							// New feed? Create a new entry
							if ($action == 'add')
							{
								$sql = 'INSERT INTO ' . FIND_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
								$db->sql_query($sql);

								$log = 'ADDED';
							}
							else
							{
								$sql = 'UPDATE ' . FIND_TABLE . '
									SET ' . $db->sql_build_array('UPDATE', $sql_ary) . "
									WHERE feed_id $sql_ids";
								$db->sql_query($sql);

								$log = 'UPDATED';
							}

							$db->sql_transaction('commit');

							$cache->destroy('_find');

							add_log('admin', 'LOG_FEED_' . $log, $feed_data['feed_name']);
							trigger_error( $user->lang['FEED_' . $log] . adm_back_link($this->u_action));
						}
					}
				}

				if (!$submit || $action == 'add' || $check)
				{
					if ($action == 'edit' && !$check)
					{
						// prepare form values for edit
						$sql = 'SELECT *
							FROM ' . FIND_TABLE . "
							WHERE feed_id $sql_ids";
						$result = $db->sql_query($sql);
						$feed_data = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);

					//	php >= 5.4 : $feed_filters = json_decode($feed_data['feed_filters'], true);
						$feed_filters = unserialize($feed_data['feed_filters']);
					}

					$select_id = $feed_data['post_forum'];

					// Build forum options
					$sql = 'SELECT forum_id, forum_type
						FROM ' . FORUMS_TABLE . '
						ORDER BY left_id';
					$result = $db->sql_query($sql);

					$ignore_id = array();
					while ($row = $db->sql_fetchrow($result))
					{
						if ($row['forum_type'] != FORUM_POST)
						{
							$ignore_id[] = $row['forum_id'];
						}
					}
					$db->sql_freeresult($result);

					$s_forum_options = '<option value="0">--- ' . $user->lang['POST_FORUM'] . ' ---</option>';

					$forum_list = make_forum_select($select_id, $ignore_id, true, false, false, false, true);
					foreach ($forum_list as $key => $opt)
					{
						$s_forum_options .= ($opt['disabled']) ? '<option disabled="disabled" class="disabled-option">' : '<option value="' . $opt['forum_id'] . '"' . (($opt['selected']) ? ' selected="selected"' : '') . '>';
						$s_forum_options .= $opt['padding'] . $opt['forum_name'] . '</option>';
					}

					// Build post bot options
					$sql = 'SELECT user_id, username
						FROM ' . USERS_TABLE . '
						WHERE username LIKE "%' . FIND_BOT_ID . '%"';
					$result = $db->sql_query($sql);

					$bot_list = array();
					while ($bot = $db->sql_fetchrow($result))
					{
						$bot_list[$bot['user_id']] = $bot['username'];
					}
					$db->sql_freeresult($result);

					$s_bot_options = '<option value="0">--- ' . $user->lang['POST_BOT'] . ' ---</option>';
					foreach ($bot_list as $id => $name)
					{
						$s_bot_options .= '<option value="' . $id . (($id == $feed_data['bot_id']) ? '" selected="selected" >' : '" >') . $name . '</option>';
					}

					// Build feed properties
					$_options = array('1' => 'YES', '0' => 'NO');
					$feed_properties = array('feed_name_subject', 'feed_info', 'article_cat', 'article_html');
					foreach ($feed_properties as $key)
					{
						$s_options = '';
						foreach ($_options as $value => $lang)
						{
							$selected = ($feed_data[$key] == $value) ? ' checked="checked" id="' . $key . '"' : '';
							$s_options .= '<input type="radio" class="radio" name="' . $key . '" value="' . $value . '"' . $selected . ' />&nbsp;' . $user->lang[$lang] . '&nbsp;&nbsp;';
						}

						$lang = strtoupper($key);
						$template->assign_block_vars('properties', array(
							'TYPE'			=> $user->lang[$lang],
							'TYPE_EXPLAIN'	=> $user->lang[$lang . '_EXPLAIN'],
							'KEY'				=> $key,
							'S_TYPE'			=> $s_options,
						));
					}
					
					// Build post new topic radios
					$_options = array('1' => 'TOPIC_DAILY', '7' => 'TOPIC_WEEKLY', '30' => 'TOPIC_MONTHLY', '0' => 'TOPIC_ARTICLE');
					$s_post_mode = '';
					foreach ($_options as $value => $lang)
					{
						$selected = ($feed_data['post_mode'] == $value) ? ' checked="checked" id="post_mode"' : '';
						$s_post_mode .= ($value) ? '' : '<hr style="border: 1px solid #808080; margin: 5px 0 7px 0; padding: 0px; width: 80px"/>';
						$s_post_mode .= '<input type="radio" class="radio" name="post_mode" value="' . $value . '"' . $selected . ' />&nbsp;' . $user->lang[$lang] . '<br />';
					}

					// Build feed filters
					foreach ($filter_keys as $lang)
					{
						$key = strtolower($lang);
						$show = ($key == 'src') ? false : true;

						$template->assign_block_vars('filters', array(
							'TYPE'			=> $user->lang[$lang . '_FILTER'],
							'TYPE_EXPLAIN'	=> $user->lang[$lang . '_FILTER_EXPLAIN'],
							'KEY'				=> $key,
							'S_SHOW'			=> $show,
						));

						$vals = $feed_filters[${key}];

						if (empty($vals))
						{
							$template->assign_block_vars('filters.entries', array(
								'KEY_S'	=> '',
								'KEY_R'	=> '',
								'S_CODE'	=> false,
							));

						}
						else
						{
							$i = 0;
							foreach ($vals as $value)
							{
								$hide = (!$i) ? false : true;
								list($search, $replace) = $value;

								$template->assign_block_vars('filters.entries', array(
									'KEY_S'	=> htmlentities($search, ENT_QUOTES, "UTF-8"),
									'KEY_R'	=> htmlentities($replace, ENT_QUOTES, "UTF-8"),
									'S_CODE'	=> $hide,
								));

								$i++;
							}
						}
					}
				}

				$template->assign_vars(array(
					'L_TITLE'	=> $user->lang['FEED_' . strtoupper($action)],

					'S_ERROR'	=> (sizeof($error)) ? true : false,
					'ERROR_MSG'	=> (sizeof($error)) ? implode('<br />', $error) : '',

					'S_PROMPT'		=> (sizeof($prompt)) ? true : false,
					'PROMPT_MSG'	=> (sizeof($prompt)) ? implode('<br />', $prompt) : '',

					'FEED_NAME'			=> $feed_data['feed_name'],
					'FEED_URL'			=> $feed_data['feed_url'],
					'FEED_STATE'		=> $feed_data['feed_state'],
					'S_CHECK'			=>	!$check,
					'S_FORUM_OPTIONS'	=> $s_forum_options,
					'S_BOT_OPTIONS'	=> $s_bot_options,

					'S_POST_MODE'	=> $s_post_mode,

					'MAX_ARTICLES'	=> $feed_data['max_articles'],
					'MAX_CONTENTS'	=> $feed_data['max_contents'],

					'S_EDIT_FEED'	=> true,
					'S_CHECKED'		=> $url_checked,

					'U_ACTION'	=> $this->u_action . "&amp;id=$feed_id&amp;action=$action",
					'U_BACK'		=> $this->u_action,
				));

				return;

			break;
		}

		// default: feed list
		$sql = 'SELECT r.*, f.forum_name, f.forum_parents 
			FROM ' . FIND_TABLE . ' r, ' . FORUMS_TABLE . ' f 
			WHERE r.post_forum = f.forum_id
			ORDER BY r.post_forum, r.feed_id ASC';
		$result = $db->sql_query($sql);

		$last_fid = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			$tree = '';
			$s_tree = false;

			if ($last_fid != $row['post_forum'])
			{
				$s_tree = true;
				$last_fid = $row['post_forum'];

				$tree .= $row['forum_name'];
			}

			$active_lang = (!$row['feed_state']) ? 'ACTIVATE' : 'DEACTIVATE';
			$active_value = strtolower($active_lang);

			$w3_url = 'http://validator.w3.org/feed/check.cgi?url=' . urlencode($row['feed_url']);

			$template->assign_block_vars('feeds', array(
				'S_TREE'	=> $s_tree,
				'FORUM'	=> $tree,

				'ID'				=> $row['feed_id'],
				'NAME'			=> $row['feed_name'],
				'URL'				=> $w3_url,
				'LAST_UPDATE'	=> ($row['last_update']) ? $user->format_date($row['last_update']) : $user->lang['NEVER'],

				'L_ACTIVATE_DEACTIVATE'	=> $user->lang[$active_lang],
				'U_ACTIVATE_DEACTIVATE'	=> $this->u_action . "&amp;id={$row['feed_id']}&amp;action=$active_value",
				'U_IMPORT'					=> $this->u_action . "&amp;id={$row['feed_id']}&amp;action=import",
				'U_EDIT'						=> $this->u_action . "&amp;id={$row['feed_id']}&amp;action=edit",
			));

		}

		$db->sql_freeresult($result);

		if ($last_fid)
		{
			$s_options = '';
			$_options = array('deactivate', 'activate', 'import', 'delete');
			foreach ($_options as $value)
			{
				$lang = strtoupper($value);
				$s_options .= '<option value="' . $value . '">' . $user->lang[$lang] . '</option>';
			}

			$template->assign_vars(array(
				'S_FEED_OPTIONS'	=> $s_options,
				'U_ACTION'			=> $this->u_action,
			));
		}
		else
		{
			$template->assign_var('S_NO_ITEMS', true);
		}
	}
}

?>
