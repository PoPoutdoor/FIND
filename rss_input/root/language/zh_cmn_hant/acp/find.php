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
	'ACTIVATE_FEED'	=> '啟用來源：<br /><br />%s',
	'DEACTIVATE_FEED'	=> '停用來源：<br /><br />%s',
	'DELETE_FEED'		=> '<br />刪除來源：<br /><br />%s<br /><br />%s',
	'FEED_ADDED'		=> '已經加入新聞來源。',
	'FEED_DELETED'		=> '新聞來源已經刪除。',
	'FEED_UPDATED'		=> '新聞來源已經更新。',
	'IMPORT_ERR'		=> "\n" . '引進錯誤： ',
	'IMPORT_OK'			=> '引進成功：',
	'IMPORT_SKIP'		=> "\n" . '沒有更新： ',
	'NO_FEED'			=> '您必須選擇至少一個來源。',
	'NONE'				=> "沒有。\n",

	// form messages
	'IMPORT'				=> '引進',
	'POST_BOT'			=> '選擇發佈機器人',
	'POST_FORUM'		=> '選擇發佈版面',
	'TOPIC_DAILY'		=> '每天',
	'TOPIC_ITEM'		=> '項目',
	'TOPIC_MONTHLY'	=> '每月',
	'TOPIC_WEEKLY'		=> '每週',

	// form error
//	'ENCODE_TOO_LONG'	=> '強制篇碼字數太長：至多 32 個字符！',
	'NAME_TOO_LONG'	=> '新聞來源名稱太長：至多 255 個字符！',
	'NAME_TOO_SHORT'	=> '新聞來源名稱太短：至少需要 3 個字符！',
	'NO_FEED_URL'		=> '你必需設定新聞來源地址！',
	'NO_FEEDNAME'		=> '你必需設定新聞來源名稱！',
	'NO_FORUM'			=> '你必需選擇新聞發佈版面！',
	'NO_USER'			=> '你必需選擇新聞發佈人！',
	'URL_NOT_VALID'	=> '新聞來源地址輸入錯誤！',
	'URL_TOO_LONG'		=> '新聞來源地址太長：至多 255 個字符！',
	'URL_TOO_SHORT'	=> '新聞來源地址太短：至少需要 12 個字符！',

	// main form
	'ADD_FEED'		=> '新增來源',
	'FEED_ID'		=> 'ID',
	'FEEDNAME'		=> '來源名稱',
	'FIND'			=> 'RSS/Atom 新聞引進',
	'FIND_EXPLAIN'	=> '綜合 RSS/Atom 新聞引進外掛',
	'NO_ENTRIES'	=> '沒有設定新聞來源，請點擊‘新增來源’來設定一個！',
	'FEED_CHECK'	=> '驗證',

	// add/edit form
	'EDIT_FEED'				=> '編輯來源',
	'EDIT_FEED_EXPLAIN'	=> '這個表單用來設定引進特性。',

	'FEED_CAT'				=> '分類訊息',
	'FEED_CAT_EXPLAIN'	=> '設定是否在文章顯示分類訊息（如果來源包含）。',
	'FEED_INFO'				=> '來源訊息',
	'FEED_INFO_EXPLAIN'	=> '設定是否在文章顯示來源訊息（如果來源包含）。',
	'FEED_HTML'				=> 'HTML 支援',
	'FEED_HTML_EXPLAIN'	=> '如果啟用，來源包含的 HTML 就會在發表的文章中轉換成支援的 BBCode。',
	'FEED_NAME'				=> '新聞來源名稱',
	'FEED_NAME_EXPLAIN'	=> 'ACP 操作顯示的名稱。若引進時來源沒提供主題，文章主題自動使用這個名稱。',
//	'FEED_RECODE'					=> '強制編碼',
//	'FEED_RECODE_EXPLAIN'		=> '正常不需設定(留空)！這個設定是解決來源編碼表頭決失或錯誤的問題。',
	'FEED_URL'				=> '新聞來源地址',
	'FEED_URL_EXPLAIN'	=> '請先到<a href="http://validator.w3.org/feed/" onclick="window.open(this.href); return false"><em>這裡</em></a>確認來源是否符合相關標準！<br />來源的 URL 地址 (12-255 字符)。',
	'FEEDNAME_TOPIC'				=> '來源名稱做主題',
	'FEEDNAME_TOPIC_EXPLAIN'	=> '如果來源包含主題訊息，發表的主題是自動產生的。如果選擇“是”，就會採用上面設定的‘來源名稱’做發表主題。',

	'POST_FORUM_BOT'				=> '發佈版面和機器人',
	'POST_FORUM_BOT_EXPLAIN'	=> '選擇引進內容的發佈版面和發佈機器人。',
	'POST_ITEMS'					=> '文章上限',
	'POST_ITEMS_EXPLAIN'			=> '設定從來源引進的文章上限。例如：設為 5 只會從來源引進最新的 5 篇文章，設為 0 引進來源的所有文章。',
	'POST_LIMITS'					=> '發表字數',
	'POST_LIMITS_EXPLAIN'		=> '設定每篇文章引進的字數上限。設為 300 來顯示幾行，0 是引進所有提供的內容。',

	'TOPIC_MODE'			=> '發佈模式',
	'TOPIC_TTL'				=> '新主題時限',
	'TOPIC_TTL_EXPLAIN'	=> '設定發佈新主題的期限。<br /><em>注意：</em>選擇“項目”表示永遠以項目標題作為新主題發表，同時令到下面<strong>名稱做主題</strong>的設置無效！',
));

?>
