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
	'BOT_NOT_ACTIVE'	=> '發佈機器人 [%s] 已被停用！',
	'FEED_FETCH_ERR'	=> '[%s] 從 %s 讀取資料失敗！',
	'FEED_NOT_ACTIVE'	=> '消息來源 [%s] 已被停用！',
	'FEED_NOT_VALID'	=> '[%s] 來源不符合標準，請查看源 xml！<br />參閱：<a href="http://cyber.law.harvard.edu/rss/rss.html" />RSS 2.0 標準</a>, <a href="http://www.ietf.org/rfc/rfc4287.txt" />Atom 1.0 標準</a>',
	'FEED_TS_INVALID'	=> '[%s] 來源的更新時間標記錯誤！',
	'NO_IDS'				=> '沒有提供新聞 id。',
	'NO_PHP_SUPPORT'	=> '網站 php 沒有安裝這個外掛需要使用的 <strong>SimpleXML</strong> 程式庫 或 沒有啟用 <strong>allow_url_fopen</strong>！。',
	'NO_POST_INFO'		=> '[%s] 新聞訊息缺失！應該至少提供標題或內容其中一個的訊息！',

	// acp import results
	'FEED_NO_UPDATES'	=> '[%s] 沒有任何更新。',
	'FEED_OK'			=> '[%s] 有 %d 篇新文章。',
	'FEED_SKIP'			=> '[%s] 有 %d 篇過時文章。',

	# rss_import messages
	'HACK_ATTEMPT'	=> '偵測到可疑入侵。' . "\n",
	'IMPORT_ERR'	=> "\n" . '引進錯誤： ',
	'NO_PARAMETER'	=> '沒有參數。輸入為： [%s]' . "\n",
	'PARM_ERR'		=> '參數必需是數字符。輸入為： [%s]' . "\n" . '範例： %s?feed=[1,2,...,999 | 999]' . "\n",
));

?>
