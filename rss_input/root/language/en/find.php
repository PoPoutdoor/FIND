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
	'BOT_NOT_ACTIVE'	=> 'Bot [%s] is not activated!',
	'FEED_FETCH_ERR'	=> "\t[%s] Failed to fetch data from\n\t%s",
	'FEED_NOT_ACTIVE'	=> 'Feed [%s] is not activated!',
	'FEED_TS_INVALID'	=> '[%s] The last updated timestamp is not valid!',
	'NO_IDS'				=> 'No feed id passed!',
	'NO_PHP_SUPPORT'	=> 'Installed PHP does not have <strong>SimpleXML</strong> support or <strong>allow_url_fopen</strong> activated!<br />This Mod needs both to work.',
	'NO_POST_INFO'		=> '[%s] News does not provide title and content!',
	
	// acp add/edit/import error message
	'RESPONSE_HEADER'	=> "\nHTTP response header:\n\n",
	'XML_ERROR'			=> "\nXML parser error:\n\n\t",
	'LINE_COLUMN'		=> "\n\tLine: %s\n\tColumn: %s\n",

	// acp import results
	'FEED_NO_UPDATES'	=> '[%s] processed without updates.',
	'FEED_OK'		=> '[%s] processed with %d new item(s).',
	'FEED_SKIP'		=> '[%s] processed with %d old item(s)',

	# feed_import.php messages - text only
	'HACK_ATTEMPT'	=> 'Suspected hack attempts detected.' . "\n",
	'IMPORT_ERR'	=> "\nFeed(s) with error:",
	'NO_PARAMETER'	=> 'No parameter provided. Input is: [%s]' . "\n",
	'PARM_ERR'		=> 'Parameter must be numeric string.' . "\n" . 'Input is: [%s]' . "\n" . 'Examples: %s?feed=[1,2,...,999 | 999]' . "\n",
));

?>
