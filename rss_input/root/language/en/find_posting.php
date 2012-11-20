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
	'BB_AUTHOR'				=> 'Author: [color=brown]%s[/color]' . "\n" ,
	'BB_CAT'					=> '[size=125]Category: [color=darkred]%s[/color][/size]' . "\n",
	'BB_CHANNEL'			=> '[size=125]%s[/size]',
	'BB_COPYRIGHT'			=> '[size=85][color=#808000]%s[/color][/size]' . "\n",
	'BB_CHANNEL_DESC'		=> '[size=85][color=indigo]%s[/color][/size]' . "\n",
	'BB_CHANNEL_DATE'		=> 'Published at: [color=green]%s[/color]',
	'BB_CHANNEL_UPDATE'	=> 'Last Update: [color=green]%s[/color]',
	'BB_POST_AT'			=> 'Post at: [color=green]%s[/color]',
	'BB_TITLE'				=> '[color=darkblue][size=150]%s[/size][/color]' . "\n",
	'BB_UPDATE_AT'			=> 'Update at: [color=green]%s[/color]',
	'BB_URL'					=> '[url=%s]%s[/url]',

	'COMMENTS'	=> '[i]Post your comments[/i]',
	'HR'			=> "\n--------\n",	// Horizontal line.
	'READ_MORE'	=> '[i]Read more...[/i]',
	'TAB'			=> ' - ',	// Tab.
	'TRUNCATE'	=> '...',	// Truncate string.
));

?>
