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
* @package acp
*/
class acp_find
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $template, $cache, $phpbb_root_path, $phpEx;

		$error = array();
		$sql_ids = '';

		$submit	= (isset($_POST['submit'])) ? true : false;
		$action	= request_var('action', '');
		$mark		= request_var('mark', array(0));
		$feed_id	= request_var('id', 0);

		$user->add_lang('acp/find');
		$this->tpl_name = 'acp_find';
		$this->page_title = 'ACP_FIND';
		$form_key = 'acp_find';
		add_form_key($form_key);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}

		if (isset($_POST['add']))
		{
			$action = 'add';
		}

		if ($feed_id)
		{
			$sql_ids = " = $feed_id";
		}
		else if (sizeof($mark))
		{
			$sql_ids = ' IN (' . implode(",", $mark) . ')';
		}

		if ($action && $action != 'add' && empty($sql_ids))
		{
			trigger_error( $user->lang['NO_FEED'] . adm_back_link($this->u_action));
		}

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

		// User wants to do something, how inconsiderate of them!
		switch ($action)
		{
			case 'activate':
			case 'deactivate':
				$activate = ($action == 'activate') ? 1 : 0;
				$message = sprintf($user->lang[strtoupper($action) . '_FEED'], $feedname_list);

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
				$message = '';
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

					add_log('admin', 'LOG_FEED_DELETED', $feedname_list);
					trigger_error( $user->lang['FEED_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					$message = sprintf($user->lang['DELETE_FEED'], $feedname_list, $user->lang['CONFIRM_OPERATION']);
					confirm_box(false, $message, build_hidden_fields(array(
						'mark'	=> $mark,
						'id'		=> $feed_id,
						'mode'	=> $mode,
						'action'	=> $action))
					);
				}
			break;

			case 'edit':
			case 'add':
				// init/normalise form values
				$rss_row = array(
					'feedname'		=> utf8_normalize_nfc(request_var('feedname', '', true)),
					'url'				=> request_var('url', ''),
					'post_forum'	=> request_var('post_forum', 0),
					'bot_id'			=> request_var('bot_id', 0),
					'encodings'		=> utf8_normalize_nfc(request_var('encodings', '', true)),

					'topic_ttl'			=> request_var('topic_ttl', 1),
					'feedname_topic'	=> request_var('feedname_topic', 0),

					'post_items'		=> request_var('post_items', 10),
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

				if ($submit)
				{
					// validate form
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
					else if (!preg_match('#^http://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i', htmlspecialchars_decode($rss_row['url'])))
					{
						$error[] = $user->lang['URL_NOT_VALID'];
					}
					// Does anyone knows the maximum encoding string length?
					if (!empty($rss_row['encodings']) && utf8_strlen(htmlspecialchars_decode($rss_row['encodings'])) > 32)
					{
						$error[] = $user->lang['ENCODE_TOO_LONG'];
					}

					if (!sizeof($error))
					{
						$sql_ary = array(
							'post_forum'		=> (int) $rss_row['post_forum'],
							'bot_id'				=> (int) $rss_row['bot_id'],
							'feedname'			=> (string) $rss_row['feedname'],
							'url'					=> (string) $rss_row['url'],
							'encodings'			=> (string) strtolower($rss_row['encodings']),
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

				$template->assign_vars(array(
					'L_TITLE'	=> $user->lang[strtoupper($action) . '_FEED'],

					'S_ERROR'	=> (sizeof($error)) ? true : false,
					'ERROR_MSG'	=> (sizeof($error)) ? implode('<br />', $error) : '',

					'FEED_NAME'			=> $rss_row['feedname'],
					'FEED_URL'			=> $rss_row['url'],
					'S_FORUM_OPTIONS'	=> $s_forum_options,
					'S_BOT_OPTIONS'	=> $s_bot_options,
					'FEED_RECODE'		=> $rss_row['encodings'],

					'S_TOPIC_TTL'	=> $s_topic_ttl,
					'S_FEEDNAME'	=> $s_['feedname_topic'],

					'POST_ITEMS'	=> $rss_row['post_items'],
					'POST_LIMITS'	=> $rss_row['post_contents'],

					'S_CHANNEL'		=> $s_['inc_channel'],
					'S_CAT'			=> $s_['inc_cat'],
					'S_HTML'			=> $s_['feed_html'],
					'S_EDIT_FEED'	=> true,

					'U_ACTION'	=> $this->u_action . "&amp;id=$feed_id&amp;action=$action",
					'U_BACK'		=> $this->u_action,
				));

				return;

			break;
		}

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
				'U_DELETE'					=> $this->u_action . "&amp;id={$row['feed_id']}&amp;action=delete",
				'U_EDIT'						=> $this->u_action . "&amp;id={$row['feed_id']}&amp;action=edit",
			));

		}
		$db->sql_freeresult($result);

		if ($last_fid)
		{
			$s_options = '';
			$_options = array('activate' => 'ACTIVATE', 'deactivate' => 'DEACTIVATE', 'delete' => 'DELETE', 'import' => 'IMPORT');
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
