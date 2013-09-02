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
* @language Traditional Chinese [zh_cmn_hant]
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
	'BB_AUTHOR'			=> '由 [color=brown]%s[/color] 發表' . "\n" ,
	'BB_CAT'				=> '[size=125]分類：[color=darkred]%s[/color][/size]' . "\n",
	'BB_COPYRIGHT'		=> '[size=85]版權訊息：[color=#808000]%s[/color][/size]' . "\n",
	'BB_POST_SRC'		=> '來自 [color=green]%s[/color]' . "\n",
	'BB_POST_TS'		=> '於 [color=green]%s[/color] 發佈' . "\n",
	'BB_SOURCE_TITLE'	=> '[size=125]%s[/size]',
	'BB_SOURCE_DESC'	=> '[size=85][color=indigo]%s[/color][/size]' . "\n",
	'BB_SOURCE_DATE'	=> '消息於 [color=green]%s[/color] 更新' . "\n",
	'BB_TITLE'			=> '[color=darkblue][size=150]%s[/size][/color]' . "\n",
	'BB_URL'				=> '[url=%s]%s[/url]',

	'COMMENTS'	=> '[i]發表意見[/i]' . "\n",
	'HR'			=> "\n--------\n",	// Horizontal line.
	'READ_MORE'	=> '[i]閱讀全文[/i]',
	'RELATED'	=> "\n\n[i]相關文章[/i]",
	'TAB'			=> ' - ',	// Tab.
	'TRUNCATE'	=> '﹍',		// Truncate string.
	// custom message below
	'IMG_RESTRICT'	=> "\n" . '[[i]受保護圖象[/i]]' . "\n",

));

?>
