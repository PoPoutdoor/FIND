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
define('IN_PHPBB', true);

/**
*
*	Note: This script does not use any phpBB UI, is designed for scheduled import of feed(s)
*
*		-	Return text only message upon error. The error message can be used for mail notification,
*			for example *nix CRON program.
*		-	Any external program which supports http protocol can be used. 
*		-	Set 'USER_AGENT' below for the external program. You can check out the value from your
*			server access log. If the external program supports custom User-Agent value, you may set
*			this to something like encrypted string to avoid possible DoS attack.
*
*/

// User Agent check
//define('USER_AGENT', '');	// e.g. set 'lwp-request' for perl GET, 'curl' for curl.
// Set TRUE to show hack message
define('HACK_MSG', false);

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// update users last page entry
$user->session_begin(true);
$auth->acl($user->data);
$user->setup('common');
$user->add_lang('find');

$hack_msg = ( HACK_MSG ) ? $user->lang['HACK_ATTEMPT'] : '';

//	Check the User-Agent
if (defined('USER_AGENT'))
{
	if ( USER_AGENT && strpos($user->data['session_browser'], USER_AGENT) === false )
	{
		die($hack_msg);
	}
}

$info = $user->extract_current_page($phpbb_root_path);

$query = $info['query_string'];

if (empty($query))
{
	die(sprintf($user->lang['NO_PARAMETER'], $info['page']));
}

$pos = strpos($query, 'feed=');
if ( $pos === false)
{
	die(sprintf($user->lang['PARM_ERR'], $info['page'], $info['page_name']));
}

$feed = preg_replace('/[^0-9,]/', '', substr($query, $pos + 5));

$ids = explode(",", $feed);
$ids = array_diff($ids, array('' => 0), array('' => ''));
$ids = array_unique($ids);

if (!sizeof($ids))
{
	die(sprintf($user->lang['PARM_ERR'], $info['page'], $info['page_name']));
}

// import feed
include($phpbb_root_path . 'includes/functions_find.'.$phpEx);

$ret = post_feed($ids);

// Return error message, text only.
$message = '';
if (!empty($ret['err']))
{
	$message .= $user->lang['IMPORT_ERR'] . "\n\n";
	foreach ($ret['err'] as $not_used => $text)
	{
		$message .= "$text\n";
	}

	die($message);

}

?>
