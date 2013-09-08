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
* @language English [en]
* @translator (c) ( PoPoutdoor )
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	// Note: text only, no html
	// error message
	'BOT_NOT_ACTIVE'	=> 'Bot [%s] is not activated!',
	'FEED_FETCH_ERR'	=> "\t[%s] Failed to fetch data from\n\t%s",
	'FEED_NOT_ACTIVE'	=> '[%s] is not activated!',
	'LINE_COLUMN'		=> "\n\tLine: %s\n\tColumn: %s\n",
	'NO_IDS'				=> 'No feed id passed!',
	'NO_PHP_SUPPORT'	=> 'Installed PHP does not have "SimpleXML" support or "allow_url_fopen" activated!' ."\n" . 'This Mod needs both to work.',
	'RESPONSE_HEADER'	=> "\nHTTP response header:\n\n",
	'XML_ERROR'			=> "\nXML parser error:\n\n\t",
	// acp import UI message
	'FEED_OK'			=> '[%s] processed with %d new item(s).',
	'FEED_OLD'			=> '[%s] processed without updates.',
	'FEED_SKIP'			=> '[%s] processed with %d old item(s)',
	'FEED_TS_INVALID'	=> '[%s] Feed last updated timestamp is not valid!',
	'NO_POST_INFO'		=> '[%s] Feed article does not provide title and content!',
	// feed_import.php messages
	'HACK_ATTEMPT'	=> "Unauthorised access from: %s\n",
	'IMPORT_ERR'	=> "\nFeed(s) with error:",
	'NO_PARAMETER'	=> 'No parameter provided. Input is: [%s]' . "\n",
	'PARM_ERR'		=> 'Parameter must be numeric string.' . "\n" . 'Input is: [%s]' . "\n" . 'Examples: %s?feed=[1,2,...,999 | 999]' . "\n",
));

?>
