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
define('IN_PHPBB', true);

/**
*
* @This script always return null, except error message in plain text (for CRON's error mail notification).
*
* You can rename this filename to non-dictionary string pattern (e.g. somethingelse.php) to prevent
*  possible DOS attack.
*	
*/

// Set TRUE to show hack message
define('HACK_MSG', false);
// User Agent check
define('USER_AGENT', '');	// e.g. set 'lwp-request' for perl GET, 'curl' for curl.


$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// update users last page entry
$user->session_begin();
$auth->acl($user->data);
$user->setup('common');
$user->add_lang('find');

$hack_msg = ( HACK_MSG ) ? $user->lang['HACK_ATTEMPT'] : null;

// quick and nasty security check, it's not flawless but it should stop most attempts
// the request appears to be coming from this server (e.g. from cron), so we'll let it through
if ($_SERVER['SERVER_ADDR'] == $user->data['session_ip'])
{
	//	Check the User-Agent.
	//	Any external program supports http protocol can be used to call this script.
	//	If you call this with other external program, update 'USER_AGENT' setting above.
	if ( USER_AGENT && strpos($user->data['session_browser'], USER_AGENT) === false )
	{
		die($hack_msg);
	}

	// check if parameter there
	$current_page = $user->data['session_page'];

	if (strpos($current_page, '?') === false)
	{
		die(sprintf($user->lang['NO_PARAMETER'], $current_page));
	}

	$feed = request_var('feed', '');
	$feed = preg_replace('/[^0-9,]/', '', $feed);

	if (empty($feed))
	{
		$script_name = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
		if (!$script_name)
		{
			$script_name = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
		}
		$script_name = substr($script_name, 1);

		die(sprintf($user->lang['PARM_ERR'], $current_page, $script_name));
	}

	$ary = explode(",", $feed);
	$ary = array_diff($ary, array('' => 0), array('' => ''));
	$ary = array_unique($ary);

	if (!sizeof($ary))
	{
		$script_name = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
		if (!$script_name)
		{
			$script_name = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
		}
		$script_name = substr($script_name, 1);

		die(sprintf($user->lang['PARM_ERR'], $current_page, $script_name));
	}

	$sql_ids = (sizeof($ary) > 1) ? ' IN (' . implode(",", $ary) . ')' : ' = ' . implode(",", $ary);

	// post feed
	include($phpbb_root_path . 'includes/functions_find.'.$phpEx);
	$ret = get_rss_content($sql_ids);

	// Return error message, text only.
	$message = '';
	if (!empty($ret['err']))
	{
		$message .= $user->lang['IMPORT_ERR'] . "\n\n";
		foreach ($ret['err'] as $not_used => $text)
		{
			$message .= "    $text\n";
		}
	}

	die($message);
}
else
{
	die($hack_msg);
}

?>
