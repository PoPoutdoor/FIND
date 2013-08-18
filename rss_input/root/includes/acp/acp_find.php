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

		$mark		= request_var('mark', array(0));
		$feed_id	= request_var('id', 0);
		$action	= request_var('action', '');
		$check	= (isset($_POST['feed_check'])) ? true : false;

		if (isset($_POST['add']))
		{
			$action = 'add';
			$url_checked = false;
		}
		else
		{
			$url_checked = true;
		}
		

		if ($feed_id)
		{
			$sql_ids = " = $feed_id";
		}
		else if (sizeof($mark))
		{
			$sql_ids = ' IN (' . implode(",", $mark) . ')';
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
			// fetch feedname
			$sql = 'SELECT feedname
				FROM ' . FIND_TABLE . "
				WHERE feed_id $sql_ids";
			$result = $db->sql_query($sql);

			$ary = array();
			while ($rss_row = $db->sql_fetchrow($result))
			{
				$ary[] = $rss_row['feedname'];
			}
			$rss_row = $db->sql_fetchrow($result);
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
					SET status = $activate
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

				$ret = get_rss_content($sql_ids);

				// restore current $user data
				$user->ip							= $user_ip;
				$user->data['user_id']			= $user_id;
				$user->data['username']			= $username;
				$user->data['user_colour']		= $colour;
				$user->data['is_registered']	= $registered;

				// process returned message
				$msg_ary = array('OK', 'SKIP', 'ERR');

				foreach ($msg_ary as $not_use => $msg)
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
			case 'edit':
				// init/normalise form values
				$rss_row = array(
					'feedname'		=> utf8_normalize_nfc(request_var('feedname', '', true)),
					'url'				=> request_var('url', ''),
					'post_forum'	=> request_var('post_forum', 0),
					'bot_id'			=> request_var('bot_id', 0),

					'topic_ttl'			=> request_var('topic_ttl', 1),
					'feedname_topic'	=> request_var('feedname_topic', 0),

					'post_items'		=> request_var('post_items', 0),
					'post_contents'	=> request_var('post_contents', 0),
					'inc_channel'		=> request_var('inc_channel', 1),
					'inc_cat'			=> request_var('inc_cat', 1),
					'feed_html'			=> request_var('feed_html', 1),
				);

				if ($action == 'add')
				{
					$select_id = false;
				}
				else
				{
					if (!$submit)
					{
						// prepare form values for edit
						$sql = 'SELECT *
							FROM ' . FIND_TABLE . "
							WHERE feed_id $sql_ids";
						$result = $db->sql_query($sql);
						$rss_row = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
					}

					$select_id = $rss_row['post_forum'];
				}

				if ($submit)
				{
					// validate url
					if (empty($rss_row['url']))
					{
						$error[] = $user->lang['NO_FEED_URL'];
					}
					else if (utf8_strlen(htmlspecialchars_decode($rss_row['url'])) < 12)
					{
						$error[] = $user->lang['URL_TOO_SHORT'];
					}
					else if (utf8_strlen(htmlspecialchars_decode($rss_row['url'])) > 255)
					{
						$error[] = $user->lang['URL_TOO_LONG'];
					}
					else if (!preg_match('#^https?://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i', htmlspecialchars_decode($rss_row['url'])))
					{
						$error[] = $user->lang['URL_NOT_VALID'];
					}

					if ($check && !sizeof($error))
					{
						// validate feed
						$url = 'http://validator.w3.org/feed/check.cgi?url=' . urlencode($rss_row['url']);

						$opts = array('http' =>
							array(
								'method'  => 'GET',
								'header'  => 'User-agent: FeedCheck'
							)
						);
		               
						$context = stream_context_create($opts);

						$ret = file_get_contents($url, false, $context);

						if (preg_match('/Congratulations!/i', $ret))
						{
							if (function_exists('simplexml_load_file') && ini_get('allow_url_fopen'))
							{
								@ini_set('user_agent', 'FeedCheck');
								@ini_set('session.use_cookies', 0);

								libxml_use_internal_errors(true);

								$xml = simplexml_load_file($rss_row['url'], 'SimpleXMLElement', LIBXML_NOCDATA);
							}
							else
							{
								$error[] = $user->lang['NO_PHP_SUPPORT'];
							}

							if ($xml === false)
							{
								libxml_clear_errors();
								trigger_error( 'BUG: Cookie used, access to source xml blocked!' . adm_back_link($this->u_action));
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

								$rss_row['feedname']			= (empty($source_title)) ? 'None' : utf8_normalize_nfc($source_title);
								$rss_row['feedname_topic'] = (empty($source_title)) ? 1 : 0;
								$rss_row['post_items']		= (sizeof($feed) < 30) ? 0 : 30;
								$rss_row['post_contents']	= (strlen(strip_tags($desc) < 2000)) ? 0 : 2000;
								$rss_row['inc_channel']		= (empty($source_title)) ? 0 : 1;
								$rss_row['inc_cat']			= (empty($source_cat)) ? 0 : 1;
								$rss_row['feed_html']		= ($desc == strip_tags($desc)) ? 0 : 1;

								$prompt[] = $user->lang['SELECT_FORUM_BOT'];
							}

						}
						else
						{
							$error[] = sprintf($user->lang['FEED_NOT_VALIDATE'], $url);
						}
					}
					else
					{
						if (!$rss_row['post_forum'])
						{
							$error[] = $user->lang['NO_FORUM'];
						}

						if (!$rss_row['bot_id'])
						{
							$error[] = $user->lang['NO_USER'];
						}

						if (empty($rss_row['feedname']))
						{
							$error[] = $user->lang['NO_FEEDNAME'];
						}
						else if (utf8_strlen(htmlspecialchars_decode($rss_row['feedname'])) < 3)
						{
							$error[] = $user->lang['NAME_TOO_SHORT'];
						}
						else if (utf8_strlen(htmlspecialchars_decode($rss_row['feedname'])) > 255)
						{
							$error[] = $user->lang['NAME_TOO_LONG'];
						}

						if (!sizeof($error))
						{
							$sql_ary = array(
								'post_forum'		=> (int) $rss_row['post_forum'],
								'bot_id'				=> (int) $rss_row['bot_id'],
								'feedname'			=> (string) $rss_row['feedname'],
								'url'					=> (string) $rss_row['url'],
								'topic_ttl'			=> (int) $rss_row['topic_ttl'],
								'post_items'		=> (int) $rss_row['post_items'],
								'post_contents'	=> (int) $rss_row['post_contents'],
								'feedname_topic'	=> (int) $rss_row['feedname_topic'],
								'inc_channel'		=> (int) $rss_row['inc_channel'],
								'inc_cat'			=> (int) $rss_row['inc_cat'],
								'feed_html'			=> (int) $rss_row['feed_html'],
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

							add_log('admin', 'LOG_FEED_' . $log, $rss_row['feedname']);
							trigger_error( $user->lang['FEED_' . $log] . adm_back_link($this->u_action));
						}
					}
				}

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

				$forum_list = make_forum_select($select_id, $ignore_id, true, false, false, false, true);
				$s_forum_options = '<option value="0">--- ' . $user->lang['POST_FORUM'] . ' ---</option>';
				foreach ($forum_list as $key => $row)
				{
					$s_forum_options .= ($row['disabled']) ? '<option disabled="disabled" class="disabled-option">' : '<option value="' . $row['forum_id'] . '"' . (($row['selected']) ? ' selected="selected"' : '') . '>';
					$s_forum_options .= $row['padding'] . $row['forum_name'] . '</option>';
				}

				// Build RSS bot options
				$sql = 'SELECT user_id, username
					FROM ' . USERS_TABLE . '
					WHERE username LIKE "%' . FIND_BOT_ID . '%"';
				$result = $db->sql_query($sql);

				$bot_list = array();
				while ($row = $db->sql_fetchrow($result))
				{
					$bot_list[$row['user_id']] = $row['username'];
				}
				$db->sql_freeresult($result);

				$s_bot_options = '<option value="0">--- ' . $user->lang['POST_BOT'] . ' ---</option>';
				foreach ($bot_list as $id => $name)
				{
					$s_bot_options .= '<option value="' . $id . (($id == $rss_row['bot_id']) ? '" selected="selected" >' : '" >') . $name . '</option>';
				}

				// Build radios
				$s_ = array();
				$toggle_ary = array('feedname_topic', 'inc_channel', 'inc_cat', 'feed_html');
				$_options = array('1' => 'YES', '0' => 'NO');
				foreach ($toggle_ary as $not_use => $form_key)
				{
					$s_options = '';
					foreach ($_options as $value => $lang)
					{
						$selected = ($rss_row[$form_key] == $value) ? ' checked="checked" id="' . $form_key . '"' : '';
						$s_options .= '<input type="radio" class="radio" name="' . $form_key . '" value="' . $value . '"' . $selected . ' />&nbsp;' . $user->lang[$lang] . '&nbsp;&nbsp;';
					}

					$s_[$form_key] = $s_options;
				}

				// Build post new topic radios
				$_options = array('1' => 'TOPIC_DAILY', '7' => 'TOPIC_WEEKLY', '30' => 'TOPIC_MONTHLY', '0' => 'TOPIC_ITEM');
				$s_topic_ttl = '';
				foreach ($_options as $value => $lang)
				{
					$selected = ($rss_row['topic_ttl'] == $value) ? ' checked="checked" id="topic_ttl"' : '';
					$s_topic_ttl .= ($value) ? '' : '------------<br />';
					$s_topic_ttl .= '<input type="radio" class="radio" name="topic_ttl" value="' . $value . '"' . $selected . ' />&nbsp;' . $user->lang[$lang] . '<br />';
				}

				$template->assign_vars(array(
					'L_TITLE'	=> $user->lang[strtoupper($action) . '_FEED'],

					'S_ERROR'	=> (sizeof($error)) ? true : false,
					'ERROR_MSG'	=> (sizeof($error)) ? implode('<br />', $error) : '',

					'S_PROMPT'		=> (sizeof($prompt)) ? true : false,
					'PROMPT_MSG'	=> (sizeof($prompt)) ? implode('<br />', $prompt) : '',

					'FEED_NAME'			=> $rss_row['feedname'],
					'FEED_URL'			=> $rss_row['url'],
					'S_FORUM_OPTIONS'	=> $s_forum_options,
					'S_BOT_OPTIONS'	=> $s_bot_options,

					'S_TOPIC_TTL'	=> $s_topic_ttl,
					'S_FEEDNAME'	=> $s_['feedname_topic'],

					'POST_ITEMS'	=> $rss_row['post_items'],
					'POST_LIMITS'	=> $rss_row['post_contents'],

					'S_FEEDINFO'		=> $s_['inc_channel'],
					'S_CAT'			=> $s_['inc_cat'],
					'S_HTML'			=> $s_['feed_html'],
					'S_EDIT_FEED'	=> true,
					'S_CHECKED'		=> $url_checked,

					'U_ACTION'	=> $this->u_action . "&amp;id=$feed_id&amp;action=$action",
					'U_BACK'		=> $this->u_action,
				));

				return;

			break;
		}

		// default page: feed list
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

			$active_lang = (!$row['status']) ? 'ACTIVATE' : 'DEACTIVATE';
			$active_value = strtolower($active_lang);

			$template->assign_block_vars('feeds', array(
				'S_TREE'	=> $s_tree,
				'FORUM'	=> $tree,

				'ID'				=> $row['feed_id'],
				'NAME'			=> $row['feedname'],
				'URL'				=> $row['url'],
				'LAST_IMPORT'	=> ($row['last_import']) ? $user->format_date($row['last_import']) : $user->lang['NEVER'],

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
			$_options = array('deactivate' => 'DEACTIVATE', 'activate' => 'ACTIVATE', 'import' => 'IMPORT', 'delete' => 'DELETE');
			foreach ($_options as $value => $lang)
			{
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
