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
*		-	Set 'FIND_USER_AGENT' at includes/constants.php for the external program's user-agent value.
*			You can check out the value from your server access log. 
*		-	External program which supports custom User-Agent value is *recommended*. You can set
*			this to encrypted string to prevent unauthorized feed import, thus avoid DoS attempt to
*			your site through this mod.
*
*/

define('HACK_MSG', false);

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// update users last page entry
$user->session_begin(true);
$auth->acl($user->data);
$user->setup('common');
$user->add_lang('find');

$hack_msg = ( HACK_MSG ) ? sprintf($user->lang['HACK_ATTEMPT'], $user->data['session_ip') : '';

//	Check the User-Agent
if (defined('FIND_USER_AGENT'))
{
	if ( FIND_USER_AGENT && strpos($user->data['session_browser'], FIND_USER_AGENT) === false )
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

// Return text only error message
$ret = post_feed($ids);

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
