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
	'BOT_NOT_ACTIVE'	=> 'Post bot [%s] is not active!',
	'CLOCK_ISSUE'		=> '[%s] Clock drift issue: time difference more than 30 seconds! Possible local server clock drift or source timestamps data contains error.',
	'CURL_ERR'			=> '[%s] Failed to fetch data from %s: HTTP status %s: %s',
	'FILE_NULL'			=> '[%s] Empty file returned from %s',
	'FOPEN_ERR'			=> '[%s] Failed to fetch data from %s',
	'NO_CHANNEL'		=> '[%s] Source is not compliance: missing Channel info!',
	'NO_ENCODINGS'		=> '[%s] No encoding info. Set "UTF-8" at Force Encoding setting may fix this source related issue.',
	'NO_IDS'				=> 'No feed id passed!',
	'NO_ITEM_INFO'		=> '[%s] Source is not compliance: missing item title and item description! Source should at least provide either one info.',
	'NO_ITEMS'			=> '[%s] No items found! Seems something wrong while reading the source.',
	'NO_PHP_SUPPORT'	=> 'The PHP installed on this server does not have <strong>cURL</strong> or <strong>allow_url_fopen</strong> active!<br />This Mod needs either to work.',
	'NO_XML_TAG'		=> '[%s] Force Encoding is set to fix missing source xml tag.',
	'NOT_ACTIVE'		=> '[%s] not active!',
	'NOT_VALID_XML'	=> '[%s] is not compliance: xml tag missing or not rss+xml document. Set Force Encoding with "UTF-8" may fix valid source with missing xml tag.',
	
	// acp import results
	'FEED_ERR'		=> '[%s] XML error: %s at line %d column %d byte %d.',
	'FEED_NONE'		=> '[%s] processed without updates.',
	'FEED_OK'		=> '[%s] processed with %d new item(s).',
	'FEED_SKIP'		=> '[%s] processed with %d old item(s)',

	# rss_import messages
	'HACK_ATTEMPT'	=> 'Suspected hack attempts detected.' . "\n",
	'IMPORT_ERR'	=> "\n" . 'Feed(s) with error: ',
	'NO_PARAMETER'	=> 'No parameter provided. Input is: [%s]' . "\n",
	'PARM_ERR'		=> 'Parameter must be numeric string. Input is: [%s]' . "\n" . 'Examples: %s?feed=[1,2,...,999 | 999]' . "\n",

));

?>
